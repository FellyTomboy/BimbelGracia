# Rencana Perubahan: Dukungan Enrollment Tipe "Kelas" (Paket Bulanan)

## Latar Belakang

Saat ini sistem hanya mendukung **privat** (per-session pricing). Skenario baru membutuhkan:

1. **Les Kelas dengan pembayaran paket bulanan** untuk orang tua (flat rate, bukan per pertemuan)
2. **Guru kelas dibayar per-sesi** (berbeda dengan tagihan ortu yang paketan)
3. **Guru kelas bisa berganti-ganti** setiap sesi (tidak tetap seperti privat)
4. **Satu guru bisa mengajar banyak program kelas** (misal: Senin kelas SD, Rabu kelas SMP)
5. **Tagihan ortu tetap digabung** dalam 1 invoice/WA (privat + kelas jadi 1)
6. **Slip gaji guru tetap digabung** (privat + kelas jadi 1)

---

## Ringkasan Perubahan

| Area | Perubahan |
|------|-----------|
| **Database** | 2 migration: tambah kolom di `enrollments` & `enrollment_attendances` |
| **Model** | `Enrollment` + `MonthlyAttendance` update |
| **Service** | `CalculationService` - logika baru untuk kelas |
| **Controller** | `AnalysisController` - penyesuaian grouping & WA message |
| **Views** | Penyesuaian tampilan invoice & WA |

---

## 1. Database Migration

### Migration 1: `add_type_to_enrollments_table`

```php
Schema::table('enrollments', function (Blueprint $table) {
    // 1. Tambah kolom type
    $table->string('type')->default('privat')->after('program_id');
    // 'privat' = per-session pricing (ortu & guru)
    // 'kelas'  = paket bulanan (ortu), per-session (guru)

    // 2. Buat teacher_id nullable (kelas tidak punya guru tetap)
    $table->foreignId('teacher_id')->nullable()->change();
});
```

### Migration 2: `add_session_teacher_to_enrollment_attendances_table`

```php
Schema::table('enrollment_attendances', function (Blueprint $table) {
    // Guru yang mengajar sesi ini (khusus kelas, karena bisa ganti-ganti)
    $table->foreignId('session_teacher_id')->nullable()->after('enrollment_id')
          ->constrained('teachers')->nullOnDelete();
});
```

---

## 2. Model Changes

### `app/Models/Enrollment.php`

```php
// Tambah ke $casts
'type' => 'string',

// Tambah method helper
public function isKelas(): bool
{
    return $this->type === 'kelas';
}

public function isPrivat(): bool
{
    return $this->type === 'privat';
}

// Ubah relasi teacher() jadi nullable
public function teacher(): BelongsTo
{
    return $this->belongsTo(Teacher::class)->withTrashed();
    // NOTE: untuk kelas, teacher_id = null
    // Guru di-track via session_teacher_id di attendance
}
```

### `app/Models/MonthlyAttendance.php`

```php
// Tambah ke $fillable
'session_teacher_id',

// Tambah relasi
public function sessionTeacher(): BelongsTo
{
    return $this->belongsTo(Teacher::class, 'session_teacher_id')->withTrashed();
}

// Ubah booted() - snapshot rate untuk kelas:
// - parent_rate: ambil dari enrollment->parent_rate (harga paket)
// - teacher_rate: ambil dari enrollment->teacher_rate (rate per-sesi guru)
// Tidak perlu pricing_tiers untuk kelas
```

---

## 3. CalculationService Changes

### `app/Services/CalculationService.php`

#### `calculateStudentBilling()` - Tambah logika untuk kelas

```php
public function calculateStudentBilling(Student $student, int $month, int $year, Collection $attendances): array
{
    // Pisahkan attendance berdasarkan tipe enrollment
    $privatAttendances = $attendances->filter(fn($a) => $a->enrollment?->isPrivat());
    $kelasAttendances  = $attendances->filter(fn($a) => $a->enrollment?->isKelas());

    $rows = collect();

    // --- LOGIKA PRIVAT (sama seperti sekarang) ---
    // Group by (enrollment_id, rate, present_count)
    // Subtotal = total_present × rate
    // Penalty jika < 50% agreed_sessions → +Rp5.000/sesi

    // --- LOGIKA KELAS (BARU) ---
    // Group by enrollment_id
    foreach ($kelasAttendances->groupBy('enrollment_id') as $enrollmentId => $group) {
        $enrollment = $group->first()->enrollment;
        $agreedSessions = $enrollment->agreed_sessions_per_month ?? 4;
        $paketRate = $enrollment->parent_rate; // harga paket per bulan

        // Hitung total kehadiran siswa ini
        $studentTotalPresent = $group->sum(function ($attendance) use ($student) {
            $s = $attendance->students->firstWhere('id', $student->id);
            return (int) ($s?->pivot?->total_present ?? 0);
        });

        // Hitung persentase kehadiran
        $attendancePercent = $agreedSessions > 0 ? ($studentTotalPresent / $agreedSessions) * 100 : 0;

        // Tentukan harga paket
        if ($attendancePercent <= 50) {
            $finalRate = (int) round($paketRate * 0.5); // bayar setengah
        } else {
            $finalRate = $paketRate; // bayar penuh
        }

        $rows->push([
            'enrollment_id' => $enrollmentId,
            'program' => $enrollment->program?->name ?? '-',
            'teacher' => '-', // kelas tidak punya guru tetap
            'count' => 1, // paket = 1 baris
            'rate' => $finalRate,
            'subtotal' => $finalRate,
            'discount' => 0,
            'penalty' => 0,
            'total' => $finalRate,
            'detail' => sprintf(
                'Paket %dx/minggu - Hadir %d/%d sesi (%d%%)',
                $agreedSessions,
                $studentTotalPresent,
                $agreedSessions,
                (int) $attendancePercent
            ),
            'present_count' => 1,
            'type' => 'kelas',
        ]);
    }

    // Gabung rows privat + kelas, lalu hitung grand_total
    // ... (sisa logika sama)
}
```

#### `calculateTeacherSalary()` - Gunakan `session_teacher_id` untuk kelas

```php
public function calculateTeacherSalary(int $teacherId, int $month, int $year, Collection $attendances): array
{
    // Filter: attendance yang diajar oleh teacher ini
    // Untuk privat: enrollment->teacher_id == $teacherId
    // Untuk kelas: session_teacher_id == $teacherId
    $attendances = $attendances->filter(function ($attendance) use ($teacherId) {
        if ($attendance->enrollment?->isPrivat()) {
            return $attendance->enrollment->teacher_id == $teacherId;
        }
        // Kelas: cek session_teacher_id
        return $attendance->session_teacher_id == $teacherId;
    });

    // Sisa logika sama: per-session × teacher_rate
    // Group by (enrollment_id, rate, present_count)
    // Subtotal = count × rate
    // Potongan 10% untuk terlambat
}
```

---

## 4. AnalysisController Changes

### `app/Http/Controllers/Admin/AnalysisController.php`

#### `attendanceRows()` - Tambah session_teacher_id

```php
private function attendanceRows(Collection $attendances): Collection
{
    // ... (pre-compute monthly totals sama seperti sekarang)

    return $attendances->flatMap(function (MonthlyAttendance $attendance) use (...) {
        $enrollment = $attendance->enrollment;
        $program = $enrollment?->program;

        // Untuk kelas: teacher diambil dari session_teacher_id
        // Untuk privat: teacher diambil dari enrollment->teacher
        $teacher = $enrollment?->isKelas()
            ? $attendance->sessionTeacher
            : $enrollment?->teacher;

        // ... (sisa logika sama)
    });
}
```

#### `ortu()` - Gabung privat + kelas dalam 1 parent

Tidak perlu perubahan besar karena sudah di-group by parent. Yang berubah adalah isi barisnya (ada type 'kelas' dengan format berbeda).

#### `guru()` - Group by teacher (privat & kelas)

```php
// Perubahan: attendance sekarang bisa berasal dari:
// 1. enrollment->teacher_id (privat)
// 2. session_teacher_id (kelas)
// Keduanya sudah di-handle di attendanceRows()
```

#### `buildPrivateParentMessage()` - Format untuk kelas

```php
private function buildPrivateParentMessage(...): string
{
    // Untuk baris type 'kelas':
    //   - Tentor: "-" (atau tidak ditampilkan)
    //   - Format: "Paket Kelas [Program]: 1 x Rp [harga_paket] = Rp [harga_paket]"
    //   - Detail: "(Hadir X/Y sesi, bayar Z%)"
    
    // Untuk baris type 'privat': format tetap sama
}
```

---

## 5. Views Changes

### `resources/views/admin/analysis/ortu.blade.php`

- Kolom "Tarif" untuk kelas: tampilkan harga paket (bukan per-session)
- Kolom "Jumlah" untuk kelas: selalu 1
- Tambah kolom/indikator tipe (privat/kelas)

### `resources/views/admin/analysis/guru.blade.php`

- Tidak perlu perubahan besar, karena guru tetap dibayar per-session
- Pastikan attendance kelas muncul dengan nama program yang benar

### PDF Invoice (`app/Services/Pdf/InvoiceService.php`)

- Untuk baris kelas: tampilkan "Paket Kelas [Program]" dengan tarif hasil kalkulasi
- Jumlah = 1, subtotal = tarif paket
- Detail: "(Hadir X dari Y sesi)"

---

## 6. Alur Data Lengkap

### Flow Tagihan Orang Tua (per bulan)

```
1. Ambil semua attendance bulan ini untuk semua anak parent tersebut
2. Pisahkan: privat vs kelas
3. Untuk setiap enrollment PRIVAT:
   - Hitung per-session: total_present × rate (dengan pricing_tiers jika multi-siswa)
   - Cek penalty jika < 50% agreed_sessions
4. Untuk setiap enrollment KELAS:
   - Hitung total kehadiran siswa
   - Bandingkan dengan agreed_sessions_per_month
   - Jika ≤ 50% → 50% dari parent_rate
   - Jika > 50% → 100% dari parent_rate
   - Tampilkan sebagai 1 baris paket
5. Gabung semua baris → grand total
6. Generate WA message & PDF invoice (gabung privat + kelas)
```

### Flow Gaji Guru (per bulan)

```
1. Ambil semua attendance bulan ini untuk guru tersebut
   - privat: enrollment->teacher_id == guru
   - kelas: session_teacher_id == guru
2. Hitung per-session: count × teacher_rate
3. Potongan 10% untuk sesi terlambat
4. Generate WA message & PDF slip gaji (gabung privat + kelas)
```

---

## 7. File yang Perlu Diubah/Dibuat

### Migration (2 file baru)
- `database/migrations/YYYY_MM_DD_HHMMSS_add_type_to_enrollments_table.php`
- `database/migrations/YYYY_MM_DD_HHMMSS_add_session_teacher_to_enrollment_attendances_table.php`

### Model (2 file diubah)
- `app/Models/Enrollment.php`
- `app/Models/MonthlyAttendance.php`

### Service (1 file diubah)
- `app/Services/CalculationService.php`

### Controller (1 file diubah)
- `app/Http/Controllers/Admin/AnalysisController.php`

### Views (2-3 file diubah)
- `resources/views/admin/analysis/ortu.blade.php`
- `resources/views/admin/analysis/guru.blade.php`
- `app/Services/Pdf/InvoiceService.php` (jika ada)

---

## 8. Urutan Implementasi

1. **Migration**: Buat & jalankan 2 migration
2. **Model**: Update `Enrollment` & `MonthlyAttendance`
3. **Service**: Update `CalculationService` dengan logika kelas
4. **Controller**: Update `AnalysisController`
5. **Views**: Update tampilan ortu & guru
6. **Testing**: Verifikasi dengan skenario:
   - Ortu A punya anak B (privat) + anak C (kelas) → 1 invoice gabung
   - Guru E ngajar privat B + kelas SD + kelas SMP → 1 slip gaji gabung
   - Kelas: hadir 2 dari 12 sesi → bayar 50%
   - Kelas: hadir 7 dari 12 sesi → bayar penuh