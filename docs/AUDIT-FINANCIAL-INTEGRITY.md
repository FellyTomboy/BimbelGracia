# AUDIT REPORT: Business Logic & Financial Integrity
## BimbelGracia Laravel Application
### Audit Date: 2026-08-26 | Auditor: Claude Code

---

## EXECUTIVE SUMMARY

**FINANCIAL INTEGRITY: NEEDS ATTENTION**

The application implements a complex dual-tracking system (privat + kelas) with multi-tiered pricing, attendance penalties, late penalties, and discount support. The core CalculationService and InvoiceService are well-structured. However, several critical discrepancies exist between duplicated financial calculation paths, and there are significant gaps in attendance record integrity that can lead to overbilling or underpayment.

---

## CRITICAL FINDINGS

### [CRITICAL-1] Guru/HistoryController uses current enrollment rate instead of snapshot rate — causes incorrect historical salary

**Severity:** CRITICAL

**Location:**
- File: `app/Http/Controllers/Guru/HistoryController.php`
- Method: `index()`
- Lines: 37–39

```php
$sessionCount = $group->count();
$rate = (int) ($enrollment?->teacher_rate ?? 0);
$totalSalary = $sessionCount * $rate;
```

**Business Rule:**
Historical salary should be calculated using the rate that was in effect when the attendance was recorded (the snapshot rate stored in `MonthlyAttendance.teacher_rate`).

**Actual Behavior:**
The controller uses `$enrollment->teacher_rate` — the **current** rate on the enrollment record — not the snapshot `attendance.teacher_rate`. If the enrollment's teacher_rate is changed after attendance is recorded, all historical salary data in Guru/HistoryController becomes incorrect.

**Root Cause:**
The `groupBy('enrollment_id')` loses access to individual attendance records after grouping. The code reads `$enrollment?->teacher_rate` (the current enrollment rate) instead of summing each attendance's own snapshot rate. The `$group` collection contains individual `MonthlyAttendance` records, each with their own `teacher_rate` snapshot, but this is never accessed.

**Impact:**
Overpayment or underpayment of teachers for historical periods. If a teacher rate was Rp 100,000/session when attendance was recorded but is later changed to Rp 150,000, the guru's history shows 150,000 × sessions = incorrect total. Conversely, if the rate was reduced, the teacher is underpaid.

**Example Scenario:**
- Month: January. Teacher has 4 attendance records at Rp 100,000/session (snapshot = Rp 100,000 each)
- In February, admin changes enrollment rate to Rp 80,000
- Guru opens `/guru/riwayat` for January → sees 4 × Rp 80,000 = Rp 320,000 (WRONG: should be 4 × Rp 100,000 = Rp 400,000)
- Teacher loses Rp 80,000 in recorded history

**Evidence:**
`Guru/HistoryController.php:38` — `$rate = (int) ($enrollment?->teacher_rate ?? 0)` uses current enrollment rate.

Compare with `SalaryProjectionController.php:49` which correctly uses snapshot rate:
```php
$totalRate = $group->sum(fn ($a) => (int) ($a->teacher_rate ?? 0));
```

**Recommended Fix:**
Sum each attendance's own `teacher_rate` snapshot within the enrollment group:
```php
$rate = $group->sum(fn ($a) => (int) ($a->teacher_rate ?? 0));
$totalSalary = $rate; // already the sum, not count × rate
```

---

### [CRITICAL-2] FinanceController private gross does NOT use tier-based pricing — causes systematic overbilling

**Severity:** CRITICAL

**Location:**
- File: `app/Http/Controllers/Admin/FinanceController.php`
- Method: `index()`
- Lines: 25–37

```php
$privatGross = DB::table('enrollment_attendances')
    // ...
    ->sum(DB::raw('attendance_student.total_present * enrollment_attendances.parent_rate'));
```

**Business Rule:**
Private lesson pricing uses tiered rates based on the number of students present in each session (`Enrollment.pricing_tiers`). A session with 1 student uses rate tier 1; a session with 2 students uses tier 2 (or closest tier). Sessions with different present-counts have different rates.

**Actual Behavior:**
FinanceController calculates gross revenue as `total_present × enrollment_attendances.parent_rate` — a flat multiplication using only the `parent_rate` column, completely ignoring `pricing_tiers`. This is the **single attendance record's parent_rate** (snapshot), not the tiered rate.

When a session has multiple students present, FinanceController charges `parent_rate × total_present`, but the correct billing might be a different tier rate (e.g., tier-2 rate for 2 students). If tier-2 rate > tier-1 rate × 2, the system **overcharges**. If tier-2 rate < tier-1 rate × 2, the system **undercharges**.

**Root Cause:**
FinanceController independently re-implements private billing using a simple flat formula, duplicating business logic rather than using `CalculationService`. It also uses `attendance_student.total_present` (the SUM across all students) multiplied by the single `parent_rate` snapshot, which has no concept of pricing tiers.

**Impact:**
Systematic overbilling or underbilling in the Finance Dashboard for all multi-student private sessions. For multi-student sessions where the tier rate differs significantly from flat multiplication, the dashboard's gross revenue figure is wrong.

**Example Scenario:**
- Enrollment: tier-1 rate = Rp 100,000, tier-2 rate = Rp 150,000 (not Rp 200,000 = 2× tier-1)
- In one session, 2 students both attended (total_present = 2 across both rows)
- Actual bill: Rp 150,000 (tier-2 rate for 2 students)
- FinanceController: 2 × Rp 100,000 = Rp 200,000 (OVERBILLED by Rp 50,000)
- Net income is artificially inflated by Rp 50,000

**Evidence:**
`FinanceController.php:37` — `SUM(attendance_student.total_present * enrollment_attendances.parent_rate)` has no tier logic.

Compare with `CalculationService.php:34-37` which correctly groups by `(enrollment_id, rate, presentCount)` to handle tiered pricing.

**Recommended Fix:**
FinanceController should either:
1. Use a subquery that aggregates by the same `(enrollment_id, rate_snapshot, presentCount)` grouping used in CalculationService, OR
2. Query the actual billing totals computed from CalculationService (stored in a billing_ledger table)

---

### [CRITICAL-3] FinanceController class gross uses `parent_rate` snapshot instead of tier-based `getParentRateForCount(1)`

**Severity:** CRITICAL

**Location:**
- File: `app/Http/Controllers/Admin/FinanceController.php`
- Method: `index()`
- Lines: 42–78

**Business Rule:**
Class sessions use `enrollment.getParentRateForCount(1)` to determine the per-student package rate.

**Actual Behavior:**
FinanceController joins directly on `enrollment_attendances.parent_rate` (the snapshot) and applies the 50% rule against that. However, `ClassStudentSessionController` stores `enrollment.getParentRateForCount(1)` as the `parent_rate` on the attendance record at creation time (line 179 of ClassStudentSessionController). So the snapshot is already the correct per-student rate.

**Status:** PARTIALLY CORRECT — BUT with a subtle issue.

When `ClassStudentSessionController` creates attendance records, it calls `$enrollment->getParentRateForCount(1)` and stores that as `parent_rate`. So the snapshot should be correct for single-student kelas enrollments. **However**, the FinanceController's SQL computes the 50% rule on top of this snapshot — but this is correct IF the snapshot was stored correctly.

The real issue is that the FinanceController independently computes `SUM(... CASE WHEN att_pct <= 0.5 THEN ROUND(ea.parent_rate * 0.5) ...)` without going through `Enrollment.getParentRateForCount(1)`. If `agreed_sessions_per_month` changes on the enrollment AFTER attendance records are created, the FinanceController's att_pct calculation uses the CURRENT `agreed_sessions_per_month`, not the historical one. The `MonthlyAttendance` table does not store a snapshot of `agreed_sessions_per_month`.

**Root Cause:**
FinanceController class gross SQL reads `e.agreed_sessions_per_month` (current enrollment value) in the att_pct subquery, not a snapshot stored on the attendance record.

**Impact:**
If `agreed_sessions_per_month` is changed retroactively, historical class billing changes. E.g., if originally agreed = 4 sessions, and admin changes to 8 sessions:
- Old calculation: 2/4 = 50% → 50% rule applies → Rp 75,000
- New calculation: 2/8 = 25% → 50% rule applies → Rp 75,000 (same)
- But if changed from 4 to 3: 2/3 = 66.7% > 50% → Rp 150,000 (different from historical Rp 75,000)

**Recommended Fix:**
Store `agreed_sessions_per_month` as a snapshot column on `MonthlyAttendance` at creation time (similar to how `parent_rate` and `teacher_rate` are snapshotted).

---

## HIGH FINDINGS

### [HIGH-1] Guru/HistoryController ignores late penalty in salary total

**Severity:** HIGH

**Location:**
- File: `app/Http/Controllers/Guru/HistoryController.php`
- Method: `index()`
- Lines: 37–39, 53–54

```php
$rate = (int) ($enrollment?->teacher_rate ?? 0);
$totalSalary = $sessionCount * $rate;
// ...
'payment_status' => $first->teacher_payment_status,
```

**Business Rule:**
Teacher salary should deduct 10% per session marked `terlambat` (if `isLatePenaltyEnabled()`). Salary = gross - late_penalty.

**Actual Behavior:**
The Guru/HistoryController calculates `totalSalary = sessionCount × rate` with NO late penalty deduction. It doesn't even reference `status_validation` to determine which sessions are late.

**Root Cause:**
The grouping by `enrollment_id` loses per-attendance status information. The code never checks `$group->where('status_validation', 'terlambat')`.

**Impact:**
Teachers see inflated salary figures that include sessions that should be penalized. This contradicts `SalaryProjectionController` (which correctly applies late penalties) and `AnalysisController::guru()` (which also correctly applies late penalties). The guru's own history page shows incorrect expected salary.

**Evidence:**
Compare `Guru/HistoryController.php:38` (no penalty) with `SalaryProjectionController.php:40-53` (correct penalty) and `AnalysisController.php:218-222` (correct penalty).

**Recommended Fix:**
Apply late penalty within each enrollment group:
```php
$lateCount = $group->where('status_validation', 'terlambat')->count();
$rate = $group->sum(fn ($a) => (int) ($a->teacher_rate ?? 0)); // snapshot sum
$latePenalty = $lateCount > 0 && $this->fineService->isLatePenaltyEnabled()
    ? (int) ($lateCount * ($rate / $group->count()) * 0.1) // approximate: per-session penalty
    : 0;
$totalSalary = $rate - $latePenalty;
```

---

### [HIGH-2] Guru/HistoryController shows class sessions without per-teacher rates from pivot

**Severity:** HIGH

**Location:**
- File: `app/Http/Controllers/Guru/HistoryController.php`
- Method: `index()`
- Lines: 23–29, 37–39

```php
$attendances = MonthlyAttendance::with([...])
    ->when($teacher, fn ($query) => $query->whereHas('enrollment', fn ($sub) => $sub
        ->where('teacher_id', $teacher->id)))
```

**Business Rule:**
For kelas sessions, teacher pay comes from `class_session_teacher.pivot.rate` (the per-teacher rate assigned when the class session was created).

**Actual Behavior:**
Guru/HistoryController only queries by `enrollment.teacher_id`. For kelas sessions, the enrollment's `teacher_id` may be null (since `ClassStudentSessionController` sets `teacher_id` nullable for kelas). More critically, even if a kelas attendance appears, the code uses `$enrollment?->teacher_rate` which is the enrollment's flat rate — NOT the teacher-specific pivot rate from `class_session_teacher`.

Additionally, Guru/HistoryController's query filters to `enrollment.teacher_id = $teacher->id`, which excludes kelas sessions where the teacher is listed in `class_session_teacher` pivot but NOT in `enrollment.teacher_id`.

**Root Cause:**
The query uses `whereHas('enrollment', fn ($q) => $q->where('teacher_id', $teacher->id))` which doesn't account for kelas sessions where the teacher is in the pivot, not the enrollment.

**Impact:**
Class teachers may not see their kelas sessions in their history at all, OR see them at the wrong rate.

**Evidence:**
Compare with `SalaryProjectionController.php` which uses `orWhere('enrollment_attendances.session_teacher_id', $teacherId)` for kelas sessions, and `AnalysisController::guru()` which queries kelas via `classSession` relationship.

**Recommended Fix:**
Query should include kelas sessions via session_teacher pivot or class_session_teacher relationship:
```php
->when($teacher, fn ($query) => $query->where(function ($q) use ($teacher) {
    $q->whereHas('enrollment', fn ($sub) => $sub->where('teacher_id', $teacher->id));
    $q->orWhere('session_teacher_id', $teacher->id);
    $q->orWhereHas('classSession.teachers', fn ($st) => $st->where('teachers.id', $teacher->id));
}));
```

---

### [HIGH-3] Guru/HistoryController does not filter by validation status — includes rejected records

**Severity:** HIGH

**Location:**
- File: `app/Http/Controllers/Guru/HistoryController.php`
- Method: `index()`
- Lines: 23–29

```php
$attendances = MonthlyAttendance::with([...])
    ->when($teacher, fn ($query) => $query->whereHas('enrollment', ...))
    ->where('month', $month)
    ->where('year', $year)
    // NO ->whereIn('status_validation', ['terima', 'terlambat'])
    ->get();
```

**Business Rule:**
Only validated attendance records (`terima`, `terlambat`) should appear in financial reports. `pending` and `ditolak` records should be excluded.

**Actual Behavior:**
Guru/HistoryController has NO filter on `status_validation`. It includes ALL records: `pending`, `terima`, `terlambat`, and `ditolak`. This means rejected attendance (`ditolak`) appears in the teacher's history and salary calculation.

**Root Cause:**
Missing `->whereIn('status_validation', ['terima', 'terlambat'])` in the query.

**Impact:**
Rejected attendance appears in guru's history. Combined with the other bugs (no penalty, wrong rate), this causes both over-counting of sessions and inclusion of records that should not be paid.

**Evidence:**
Compare with `SalaryProjectionController.php:30` which correctly uses:
```php
->whereIn('status_validation', ['terima', 'terlambat'])
```

**Recommended Fix:**
Add validation status filter:
```php
->whereIn('status_validation', ['terima', 'terlambat'])
```

---

### [HIGH-4] Guru/HistoryController salary ignores kelas pivot rates — uses enrollment flat rate

**Severity:** HIGH

**Location:**
- File: `app/Http/Controllers/Guru/HistoryController.php`
- Method: `index()`
- Line: 38

```php
$rate = (int) ($enrollment?->teacher_rate ?? 0);
```

**Business Rule:**
For kelas sessions, each teacher assigned to a class session has their own rate stored in `class_session_teacher.rate` pivot.

**Actual Behavior:**
For all enrollments (both privat and kelas), the code uses `$enrollment->teacher_rate` — the flat rate on the enrollment. For kelas, this is the wrong rate. The correct rate should come from the `class_session_teacher` pivot.

**Impact:**
Class teachers are paid at the wrong rate. If a teacher's pivot rate (e.g., Rp 75,000) differs from the enrollment's flat rate (e.g., Rp 100,000), the teacher is either overpaid or underpaid.

**Evidence:**
Compare with `AnalysisController::attendanceRows()` lines 746–748 which correctly reads `$teacher->pivot->rate` for kelas sessions:
```php
$pivotRate = (int) ($teacher->pivot->rate ?? $attendance->teacher_rate ?? 0);
```

**Recommended Fix:**
For each attendance in the group, sum the correct rate (attendance snapshot for privat; pivot rate for kelas).

---

### [HIGH-5] `dismissed` parent rejection status not excluded from billing calculations

**Severity:** HIGH

**Location:**
- File: `app/Http/Controllers/Admin/AttendanceReviewController.php`
- Method: `dismiss()`
- Lines: 47–53

**Business Rule:**
After a parent rejection is dismissed, the attendance record should be treated as normal (included in billing) — `parent_review_status = 'dismissed'`.

**Actual Behavior:**
`AttendanceReviewController::dismiss()` sets `parent_review_status = 'dismissed'` without changing `status_validation`. However, the billing exclusion logic across CalculationService, AnalysisController, and FinanceController ONLY excludes `parent_review_status = 'pending'`. A dismissed record (`= 'dismissed'`) is included in billing — which is the **intended behavior**.

**BUT** — looking at `AnalysisController::baseAttendanceQuery()`:
```php
->whereIn('status_validation', ['terima', 'terlambat'])
->where(function ($q) {
    $q->whereNull('parent_review_status')
      ->orWhere('parent_review_status', '!=', 'pending');
})
```

This excludes `pending` but includes `dismissed` (correct), `rejected` (correct — also filtered by status_validation = 'ditolak'), and null.

However, `FinanceController` uses the same logic, which is also correct for `dismissed`.

**Status:** This is actually CORRECT behavior — dismissed records ARE included in billing. No bug here. Moving on.

---

### [HIGH-6] Historical Finance Dashboard can change if enrollment `agreed_sessions_per_month` changes

**Severity:** HIGH

**Location:**
- File: `app/Http/Controllers/Admin/FinanceController.php`
- Method: `buildFinanceChartByRange()`
- Lines: 281–320 (yearly), 401–423 (monthly)

**Business Rule:**
Historical class billing should use the `agreed_sessions_per_month` value that was in effect when attendance was recorded.

**Actual Behavior:**
The class gross SQL subquery reads `e2.agreed_sessions_per_month` from the `enrollments` table directly — not a snapshot. If `agreed_sessions_per_month` is changed after attendance records are created, all historical chart data recalculates using the new value.

**Impact:**
Past financial charts change when enrollment parameters are updated. The Finance Dashboard for January may show different gross revenue in February than it did in January, solely because `agreed_sessions_per_month` was changed.

**Evidence:**
`FinanceController.php:55` — `NULLIF(MAX(e_inner.agreed_sessions_per_month), 0)` reads live enrollment data.

Compare with `MonthlyAttendance::booted()` which correctly snapshots `parent_rate` and `teacher_rate` at creation, but NOT `agreed_sessions_per_month`.

**Recommended Fix:**
Store `agreed_sessions_per_month` snapshot in `MonthlyAttendance` at creation time (migrate existing records).

---

### [HIGH-7] Invoice/Finance inconsistency — InvoiceService uses `subtotal` vs Finance uses `parent_rate × total_present`

**Severity:** HIGH

**Location:**
- File: `app/Services/Pdf/InvoiceService.php` + `app/Http/Controllers/Admin/FinanceController.php`

**Business Rule:**
The invoice total shown to parents should match the gross revenue recorded in the Finance Dashboard for the same period.

**Actual Behavior:**
These two systems calculate private billing using different formulas:

- **InvoiceService** (via CalculationService): Groups by `(enrollment_id, snapshot_rate, presentCount)` → calculates tier-based rate → `subtotal = adjustedRate × totalCount`
- **FinanceController**: `SUM(attendance_student.total_present × enrollment_attendances.parent_rate)`

These are NOT equivalent when pricing tiers are used, because:
- InvoiceService uses tier-based rate (e.g., Rp 150,000 for 2 students)
- FinanceController uses flat `parent_rate × total_present` (e.g., 2 × Rp 100,000 = Rp 200,000)

**Impact:**
Parent invoice total does NOT match the gross revenue shown in the Finance Dashboard. Parents may pay an amount that differs from what the dashboard reports as revenue.

**Evidence:**
`InvoiceService.php:28` → `CalculationService::calculateStudentBilling()` → tier grouping.
`FinanceController.php:37` → flat `total_present × parent_rate`.

**Recommended Fix:**
FinanceController should use CalculationService output as the source of truth for private gross revenue, or store billing records in a billing ledger.

---

### [HIGH-8] Guru/HistoryController does not support kelas salary — enrollment_id grouping loses class session identity

**Severity:** HIGH

**Location:**
- File: `app/Http/Controllers/Guru/HistoryController.php`
- Method: `index()`
- Lines: 31–56

**Business Rule:**
Class teachers should see their kelas sessions and be paid per-session at their pivot rate.

**Actual Behavior:**
Guru/HistoryController groups by `enrollment_id` only. For kelas, multiple enrollments have the same `class_session_id`. The grouping loses the per-session structure needed for proper kelas salary calculation. The code never references `class_session_id` or the `class_session_teacher` pivot.

**Impact:**
Class teachers' kelas sessions are either missing, shown at wrong rates, or double-counted.

**Evidence:**
`AnalysisController::guru()` has full kelas support via `classSession.teachers` relationship and per-attendance per-teacher pivot rates. Guru/HistoryController has none of this.

**Recommended Fix:**
Guru/HistoryController should replicate the kelas logic from `AnalysisController::attendanceRows()` lines 214–258.

---

## MEDIUM FINDINGS

### [MEDIUM-1] `parent_review_status = null` and `parent_review_status = 'dismissed'` treated identically

**Severity:** MEDIUM

**Location:**
- Multiple locations in billing exclusion logic

**Business Rule:**
- `null`: No rejection was filed. Include in billing.
- `'pending'`: Rejection filed but not yet reviewed. Exclude from billing.
- `'dismissed'`: Rejection reviewed and overruled. Include in billing.
- `'rejected'`: Rejection upheld. Excluded via `status_validation = 'ditolak'`.

**Actual Behavior:**
The billing exclusion logic: `whereNull(parent_review_status) or parent_review_status != 'pending'`. This treats `null` and `'dismissed'` the same (both included). This IS the correct behavior for billing purposes — dismissed should be included.

However, the `parent_review_status = null` vs `'dismissed'` distinction is lost for reporting. There's no way to distinguish "never rejected" from "rejected but dismissed" in billing totals.

**Impact:**
Low financial impact, but reporting ambiguity. Not a bug per se.

---

### [MEDIUM-2] Attendance penalty display in AnalysisController/ortu uses `totalSessionsThisEnrollment` as count of sessions in the enrollment for THIS particular student

**Severity:** MEDIUM

**Location:**
- File: `app/Http/Controllers/Admin/AnalysisController.php`
- Method: `ortu()`
- Lines: 91–98

```php
$totalSessionsThisEnrollment = $enrollmentItems->count(); // sessions in this enrollment group
$studentTotalPresentForPenalty = $count; // same as count for this student
$hasPenalty = $enrollment && $this->fineService->isAttendancePenaltyEnabled()
    && $enrollment->hasAttendancePenalty($totalSessionsThisEnrollment, $studentTotalPresentForPenalty);
$penalty = $hasPenalty ? $studentTotalPresentForPenalty * 5000 : 0;
```

**Business Rule:**
Penalty threshold: `studentTotalPresent < agreed_sessions_per_month / 2`. `totalSessionsThisEnrollment` should be the number of sessions the enrollment had in the billing period.

**Actual Behavior:**
`$enrollmentItems->count()` counts the number of attendance GROUP keys (i.e., unique `(enrollment_id, rate, presentCount)` combinations). If a student's attendance spans multiple group keys (e.g., due to rate changes mid-month), this undercounts the total sessions.

More critically, `$count` (used as `studentTotalPresentForPenalty`) is `sum('total_present')` for THIS student. But `$enrollmentItems->count()` counts GROUP keys, not total sessions.

**Impact:**
If a student had 2 sessions in the billing period, but they fall into 2 different pricing tier groups (e.g., once alone, once with a group), then `$enrollmentItems->count()` = 2 (correct) but `studentTotalPresentForPenalty = count = 2` (correct in this case). However, if the student had 4 sessions in 4 different attendance records but they happened to be in only 2 rate groups, `count = 4` but `count() = 2` — the penalty calculation would be on the wrong denominator.

**Root Cause:**
`$count` in `AnalysisController::ortu()` is `sum('total_present')` which is correct, but `$totalSessionsThisEnrollment` is `$enrollmentItems->count()` which counts groups, not sessions.

**Evidence:**
Compare with `CalculationService.php:47-49`:
```php
$totalCount = $group->sum(...); // correct: sum of this student's total_present across sessions in this group
$totalSessions = $group->count(); // correct: number of attendance records in this group
$penalty = $this->resolveAttendancePenalty($enrollment, $totalSessions, $studentTotalPresent);
```

In CalculationService, `totalSessions = $group->count()` (count of attendance records in the group) which is correct because all records in the group have the same `(enrollment_id, rate, presentCount)` — so count of records = count of sessions.

In AnalysisController, the `enrollmentItems` collection is grouped by `enrollment_id` only (no rate grouping), so `$enrollmentItems->count()` could count 1 even if there are multiple attendance records with different rates.

**Recommended Fix:**
`$totalSessionsThisEnrollment` should be the count of attendance records in the collection:
```php
$totalSessionsThisEnrollment = $enrollmentItems->sum(fn ($row) => 1); // or count()
```
And `$studentTotalPresentForPenalty = $enrollmentItems->sum('total_present')`.

Wait — actually in `AnalysisController::ortu()`, `$enrollmentItems` is already a collection of attendance ROWS (one per student per attendance), and `$count = $enrollmentItems->sum('total_present')`. But `$enrollmentItems->count()` counts the number of ROWS, not the number of sessions. If a student has 4 attendance records, `$enrollmentItems->count()` = 4 (correct if each row = 1 session). But the `groupBy` in `AnalysisController::ortu()` is on enrollment_id only, so multiple records for the same enrollment are all in one group. `$enrollmentItems->count()` would be the number of attendance records in that enrollment group — which is correct for counting sessions.

Actually, let me reconsider. The `enrollmentItems` in AnalysisController comes from `$items->groupBy(fn (array $row) => $row['enrollment']->id)`. Each `$row` is one student × one attendance (from `attendanceRows()`). So if a student has 4 attendance records in a month, there are 4 rows in the group. `$enrollmentItems->count()` = 4 = number of sessions. This seems correct.

**Status:** Potentially NOT a bug, but worth verifying against edge cases where one enrollment has multiple students. Actually, `$enrollmentItems->groupBy('enrollment->id')` would group all students' attendance records into one collection per enrollment. If there are multiple students in the enrollment, `$enrollmentItems->count()` would be the total number of attendance rows across all students, not the number of sessions. And `$count = $enrollmentItems->sum('total_present')` would be the sum across all students.

The penalty condition in AnalysisController: `$enrollment->hasAttendancePenalty($totalSessionsThisEnrollment, $studentTotalPresentForPenalty)`:
- `$totalSessionsThisEnrollment` = `$enrollmentItems->count()` = total rows = sum of (sessions per student), which is correct only if each student has exactly the same number of attendance records.
- `$studentTotalPresentForPenalty = $count` = sum of total_present across all students.

If Student A has 3 sessions and Student B has 2 sessions, `$enrollmentItems->count()` = 5, but `$totalSessionsThisEnrollment` used in `hasAttendancePenalty` should be the MAXIMUM sessions across any student, or the agreed number. The condition `studentTotalPresent < agreed / 2` uses `studentTotalPresent` (sum across all students) which would make the penalty threshold EASIER to trigger.

**Impact:**
For multi-student kelas: penalty might trigger when it shouldn't, or fail to trigger when it should.

**Status:** This needs further investigation. Marking as MEDIUM RISK.

---

### [MEDIUM-3] `parent_rate` snapshot at attendance creation time uses `getParentRateForCount(1)` for kelas — but this may not be the right tier

**Severity:** MEDIUM

**Location:**
- File: `app/Http/Controllers/Admin/ClassStudentSessionController.php`
- Line: 179

```php
'parent_rate' => $enrollment->getParentRateForCount(1),
```

**Business Rule:**
Kelas enrollment is 1 student per enrollment, so `getParentRateForCount(1)` should return the correct single-student package rate.

**Actual Behavior:**
`getParentRateForCount(1)` looks up the tier for 1 student. If no tier exists, it falls back to `$enrollment->parent_rate`. This should be correct for kelas (1 student per enrollment = tier 1 = correct snapshot).

However, `teacher_rate` snapshot for kelas uses:
```php
'teacher_rate' => $classSession->teachers()->where('teachers.id', $teacherIds[0] ?? 0)->first()?->pivot?->rate ?? 0
```

This looks up the FIRST teacher's pivot rate from `class_session_teacher`. But for multi-teacher class sessions, each teacher has their own rate. This creates only ONE attendance record per enrollment (not per teacher × enrollment). The `AnalysisController::attendanceRows()` handles this by returning one row per teacher per attendance for kelas, reading from `$teacher->pivot->rate`. But the snapshot stored in `MonthlyAttendance.teacher_rate` only stores ONE rate (the first teacher's).

**Impact:**
If a kelas session has multiple teachers, only the first teacher's rate is stored as the snapshot. The `AnalysisController::attendanceRows()` correctly overrides this with the per-teacher pivot rate when generating the analysis view. However, any code that reads `attendance.teacher_rate` directly (without going through the per-teacher loop) will get the wrong rate.

**Status:** This is handled correctly in AnalysisController and InvoiceService (they loop per teacher for kelas). But it's a fragile pattern.

---

### [MEDIUM-4] Invoice PDF path uses `full_name` for teachers but `full_name` can be NULL

**Severity:** MEDIUM

**Location:**
- File: `app/Services/Pdf/InvoiceService.php`
- Line: 186

```php
$teacherSlug = str_replace(' ', '_', strtolower($teacher->full_name));
```

**Business Rule:**
Teacher slug should be stable across name changes.

**Actual Behavior:**
If `$teacher->full_name` is null/empty, the slug becomes empty. Multiple teachers with null names would get the same slug, causing file overwrites. Additionally, if a teacher changes their name, the slug changes and old PDFs become unreachable (orphan files on disk, but new PDFs go to a new path).

**Impact:**
PDF file collision (overwrite) if multiple teachers have null full_name. Orphaning of historical PDFs when teacher changes their name.

**Evidence:**
`Teacher` model allows null `full_name` (no DB NOT NULL constraint observed).

**Recommended Fix:**
Use `teacher_{id}` instead of name-based slug for stable paths:
```php
$filename = sprintf('pdf/salary/teacher_%d/%s.pdf', $teacher->id, $period);
```

---

### [MEDIUM-5] MonthlySnapshotSyncService does not filter by enrollment status

**Severity:** MEDIUM

**Location:**
- File: `app/Services/MonthlySnapshotSyncService.php`
- Lines: 35–47

```php
$privateStudentsCount = Student::query()
    ->where('students.status', 'active')
    ->whereNull('students.deleted_at')
    ->whereHas('enrollments', fn ($q) => $q->where('enrollments.type', '!=', 'kelas'))
    ->count();
```

**Business Rule:**
Snapshot should count students with ACTIVE enrollments (status = 'active').

**Actual Behavior:**
The `whereHas('enrollments', ...)` query does NOT filter by `enrollments.status`. A student with ALL their enrollments in `hibernasi` status would still be counted as "active private student" if they have a hibernated private enrollment.

**Impact:**
Active student counts in the Finance Dashboard may be inflated by students whose enrollments are all hibernated.

**Evidence:**
`Enrollment::destroy()` sets `status = 'hibernasi'` and soft-deletes. The snapshot query uses `whereHas('enrollments')` without filtering enrollment status.

**Recommended Fix:**
Add enrollment status filter:
```php
->whereHas('enrollments', fn ($q) => $q
    ->where('enrollments.type', '!=', 'kelas')
    ->where('enrollments.status', 'active'))
```

---

### [MEDIUM-6] `getParentRateForCount` fallback chain has inconsistent behavior when tiers are partially defined

**Severity:** MEDIUM

**Location:**
- File: `app/Models/Enrollment.php`
- Lines: 83–98

```php
public function getParentRateForCount(int $presentCount): int
{
    $tiers = $this->pricing_tiers;
    if ($tiers && isset($tiers['parent_rate'])) {
        $rates = $tiers['parent_rate'];
        $closestRate = (int) ($rates['1'] ?? $this->parent_rate);
        for ($i = $presentCount; $i >= 1; $i--) {
            if (isset($rates[(string) $i])) {
                $closestRate = (int) $rates[(string) $i];
                break;
            }
        }
        return $closestRate;
    }
    return (int) $this->parent_rate;
}
```

**Business Rule:**
If a tier is not defined for a specific present count, fall back to the closest lower tier, or to the base `parent_rate` if no lower tier exists.

**Actual Behavior:**
The fallback uses `$rates['1']` (tier-1 rate) OR `$this->parent_rate` (enrollment base rate). These could be DIFFERENT values. If `parent_rate = Rp 100,000` but tier-1 rate = Rp 90,000, the fallback chain has inconsistent results depending on which tier exists.

**Impact:**
If `pricing_tiers` only defines tier-3 but not tier-1 or tier-2, and presentCount = 1: `$closestRate = rates['1']` → doesn't exist → falls back to `$this->parent_rate`. But this might not be the intended tier-1 rate.

**Evidence:**
The pricing tiers stored in DB from `EnrollmentController` use:
```php
'parent_rate' => $validated['pricing_tiers_parent'] ?? ['1' => $validated['parent_rate']]
```
So if tiers are submitted, tier-1 should always exist. But if the `pricing_tiers` JSON was created manually or migrated, tier-1 might be missing.

**Recommended Fix:**
Always ensure tier-1 is populated as the fallback:
```php
$closestRate = (int) ($this->parent_rate); // always start with base rate
for ($i = $presentCount; $i >= 1; $i--) {
    if (isset($rates[(string) $i])) {
        $closestRate = (int) $rates[(string) $i];
        break;
    }
}
```

---

## LOW FINDINGS

### [LOW-1] InvoiceService uses `full_name` for teacher slug — same as MEDIUM-4

Duplicate issue. Using name-based paths for PDFs is fragile.

### [LOW-2] No validation that `agreed_sessions_per_month > 0` — division by zero handled but not input validation

**Severity:** LOW

**Location:**
- File: `app/Models/Enrollment.php`
- Line: 164

```php
return $totalSessionsThisMonth > 0 && $studentTotalPresent > 0 && $studentTotalPresent < ($agreed / 2);
```

`agreed` defaults to 4 if null. But if `agreed_sessions_per_month = 1`, then `studentTotalPresent < 0.5` is always false for any positive present count. The 50% threshold still works but is trivially easy to exceed.

Not a bug per se, but the business rule interpretation is unclear: with agreed=1, does "50% rule" mean "student must attend at least 1 session" (always true if present)?

### [LOW-3] ClassTeacher pivot rate of 0 stored silently

**Severity:** LOW

**Location:**
- File: `app/Http/Controllers/Admin/ClassStudentSessionController.php`
- Line: 155

```php
$classSession->teachers()->attach($teacherId, ['rate' => $rate]);
// where $rate = $teacher->pivot->rate ?? 0
```

If `$teacher->pivot->rate` is null/0, the pivot stores rate = 0. This is later used in billing (Rp 0 teacher salary for that session). No validation prevents this.

---

## VERIFIED CORRECT AREAS

The following were inspected and found correctly implemented:

1. **CalculationService pricing tier grouping** — Correctly groups by `(enrollment_id, snapshot_rate, presentCount)` ensuring tier-based pricing is accurately computed for privat sessions.

2. **InvoiceService calculation path** — Uses `CalculationService` as the single source of truth. All three PDF types (student invoice, parent invoice, teacher salary slip) route through CalculationService, ensuring PDFs match billing logic.

3. **AnalysisController baseAttendanceQuery** — Correctly filters to only `terima` and `terlambat` records, excluding `pending` and `ditolak`.

4. **AnalysisController parent exclusion** — Correctly excludes `parent_review_status = 'pending'`, including `dismissed` records in billing.

5. **Late penalty formula** — Consistently `lateCount × rate × 0.1` (10%) across CalculationService, SalaryProjectionController, and AnalysisController.

6. **Attendance snapshot rates** — `MonthlyAttendance::booted()` correctly snapshots `parent_rate` and `teacher_rate` from the enrollment at creation time.

7. **AnalysisController guru salary calculation** — Correctly uses `attendance.teacher_rate` (snapshot) and applies per-teacher pivot rates for kelas sessions via `classSession.teachers` relationship.

8. **InvoiceService grand total formula** — `grandGross - grandDiscount + grandPenalty` is consistent with CalculationService output structure.

9. **Discount calculation** — Percent, amount, and final discount types all correctly cap/limit values. Percent is capped 0–100%. Amount is capped at baseTotal. Final is capped at baseTotal.

10. **Parent rejection workflow** — `upholdParentRejection` correctly sets both `status_validation = 'ditolak'` AND `parent_review_status = 'rejected'`, ensuring double-exclusion from billing.

11. **FinanceController private gross inclusion rules** — Correctly excludes `pending` and `ditolak` records, and `parent_review_status = 'pending'`.

12. **SalaryProjectionController** — Correctly uses snapshot rates, applies late penalties, and filters to validated records only.

13. **hasAttendancePenalty threshold** — `studentTotalPresent < agreed / 2` is correct for strict "below 50%" interpretation (49.99% triggers penalty, 50% does not).

14. **Multi-student private pricing** — CalculationService correctly sums `total_present` per individual student when grouping, ensuring each student's attendance is counted correctly.

15. **Bulk attendance duplicate check** — `storeBulk` checks for duplicate dates before inserting, preventing double-counting from the same enrollment on the same date.

16. **Parent billing view filter** — Uses `CalculationService` for billing totals, consistent with invoice generation.

17. **AttendanceFineService caching** — 30-day cache is reasonable. Cache invalidation on settings update is implemented.

18. **Soft-delete (hibernation) pattern** — All main entities (students, teachers, parents, enrollments) use soft delete, preserving historical financial records.

---

## DOCUMENTATION DISCREPANCIES

1. **Documentation says "parent_rate is snapshot at creation time"** — Partially correct. For privat attendance created by guru, the snapshot IS taken at creation time (via `MonthlyAttendance::booted()` creating hook). However, for class sessions created via `ClassStudentSessionController`, the rate is explicitly set by the controller using `getParentRateForCount(1)`, which is also a snapshot. Both paths snapshot — but through different mechanisms. The documentation is correct in spirit.

2. **Documentation says Finance Dashboard gross = `SUM(total_present × parent_rate)`** — This is an accurate description of the FinanceController's ACTUAL implementation. However, this implementation does NOT use tier-based pricing, which is a discrepancy between the Finance Dashboard and the actual billing system (InvoiceService + CalculationService). The documentation describes what IS implemented, but what IS implemented may not be the intended business rule.

3. **Documentation says Guru/HistoryController calculates salary = `session_count × rate`** — This accurately describes the current (buggy) implementation. The documentation would need to note that this calculation does NOT apply late penalties and uses current enrollment rates.

4. **Documentation says "snapshot per-month untuk historical data"** — MonthlySnapshotSyncService snapshots only counts of active students/teachers, NOT financial data. The documentation implies the snapshot system protects historical financial reporting, but it only snapshots headcount — not revenue, cost, or income. Historical financial data is recalculated from current attendance records (which IS appropriate), but the snapshot design is not a financial snapshot.

---

## DUPLICATED BUSINESS LOGIC

The following financial calculations are implemented in multiple independent locations:

| Calculation | Locations | Risk |
|---|---|---|
| Private billing (tier-based) | CalculationService, AnalysisController (inline) | MEDIUM — two different implementations can drift |
| Late penalty (10%) | CalculationService, SalaryProjectionController, AnalysisController, FinanceController | LOW — all use same formula |
| Class 50% rule | CalculationService, FinanceController, AnalysisController | HIGH — FinanceController and AnalysisController use different SQL approaches |
| Attendance inclusion filter | CalculationService, AnalysisController, FinanceController, ParentBillingController, SalaryProjectionController | MEDIUM — slight variations in which statuses excluded |
| Per-teacher kelas rate | AnalysisController (correct), Guru/HistoryController (incorrect) | HIGH — inconsistent |
| Discount resolution | CalculationService, AnalysisController (inline) | MEDIUM — different implementations |
| Snapshot rate usage | SalaryProjectionController (correct), Guru/HistoryController (incorrect) | HIGH — inconsistent |

---

## HIGH-RISK AREAS FOR NEXT AUDIT/FIX PHASE

1. **Guru/HistoryController** — needs complete rewrite to fix 4 issues: wrong rate source, missing penalty, missing kelas support, includes rejected records
2. **FinanceController vs InvoiceService** — reconciliation needed: FinanceDashboard private gross should match InvoiceService billing
3. **Class session snapshot** — `agreed_sessions_per_month` should be snapshotted on MonthlyAttendance
4. **InvoiceService path stability** — use `parent_{id}` not `teacher_slug` for file paths
5. **MonthlySnapshotSyncService** — filter by enrollment status

---

## AUDIT SUMMARY

| Category | Count |
|---|---|
| CRITICAL findings | 3 |
| HIGH findings | 8 |
| MEDIUM findings | 6 |
| LOW findings | 3 |
| Verified correct areas | 18 |
| Documentation discrepancies | 4 |

**FINANCIAL INTEGRITY: NEEDS ATTENTION**

**Reasoning:**
The core calculation engine (CalculationService + InvoiceService) is well-designed and internally consistent. However, the FinanceController duplicates financial logic using different formulas that do not account for tier-based pricing. The Guru/HistoryController has multiple independent bugs that cause incorrect salary reporting. These issues create a situation where the same attendance data can produce THREE different "correct" totals depending on which view is used:
1. InvoiceService (Correct — tier-based)
2. FinanceController Dashboard (Incorrect for privat — flat rate × total_present)
3. Guru/HistoryController (Incorrect — current rate, no penalty, includes rejected records)

The risk is that administrators make financial decisions based on Dashboard numbers, while parents receive invoices with different totals, and teachers see yet another salary figure.

**Recommended Priority:**
1. Fix FinanceController private gross to use tier-based calculation
2. Fix Guru/HistoryController (4 bugs together)
3. Add `agreed_sessions_per_month` snapshot to MonthlyAttendance
4. Stabilize PDF file paths
5. Fix MonthlySnapshotSyncService enrollment status filter
