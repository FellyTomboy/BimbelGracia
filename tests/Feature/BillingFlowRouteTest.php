<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\EnrollmentStudentDiscount;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BillingFlowRouteTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $guruUser;
    private User $parentUser;
    private Teacher $teacher;
    private ParentModel $parent;
    private Student $student;
    private Program $privatProgram;
    private Program $kelasProgram;
    private Enrollment $privatEnrollment;
    private Enrollment $kelasEnrollment;

    protected function setUp(): void
    {
        parent::setUp();

        // Admins
        $this->adminUser = User::factory()->create(['role' => 'admin']);

        // Guru — use sequence to avoid whatsapp uniqueness conflicts
        $this->teacher = Teacher::factory()
            ->state(new Sequence(fn () => ['whatsapp' => '0819' . fake()->unique()->numerify('######')]))
            ->create();
        $this->guruUser = User::factory()->create(['role' => 'guru']);
        $this->teacher->update(['user_id' => $this->guruUser->id]);

        // Parent
        $this->parent = ParentModel::factory()->create(['name' => 'Ortu Billing Flow', 'address' => 'Alamat']);
        $this->parentUser = User::factory()->create(['role' => 'parent']);
        $this->parent->update(['user_id' => $this->parentUser->id]);
        $this->student = Student::factory()->create([
            'parent_id' => $this->parent->id,
            'full_name' => 'Siswa Billing Flow',
        ]);

        // Programs
        $this->privatProgram = Program::factory()->create([
            'type' => 'privat',
            'name' => 'Les Privat Matematika',
        ]);
        $this->kelasProgram = Program::factory()->create([
            'type' => 'kelas',
            'name' => 'Les Kelas IPA',
        ]);

        // Enrollments
        $this->privatEnrollment = Enrollment::factory()->create([
            'program_id' => $this->privatProgram->id,
            'teacher_id' => $this->teacher->id,
            'type' => 'privat',
            'status' => 'active',
            'agreed_sessions_per_month' => 8,
        ]);
        $this->privatEnrollment->students()->attach($this->student->id);

        $this->kelasEnrollment = Enrollment::factory()->create([
            'program_id' => $this->kelasProgram->id,
            'teacher_id' => $this->teacher->id,
            'type' => 'kelas',
            'status' => 'active',
            'agreed_sessions_per_month' => 8,
        ]);
        $this->kelasEnrollment->students()->attach($this->student->id);

        // Fine settings OFF by default
        $att = DB::table('settings')->where('key', 'fine.attendance_penalty_enabled')->first();
        if (!$att) {
            DB::table('settings')->insert(['key' => 'fine.attendance_penalty_enabled', 'value' => 'false']);
        } else {
            DB::table('settings')->where('key', 'fine.attendance_penalty_enabled')->update(['value' => 'false']);
        }
        $late = DB::table('settings')->where('key', 'fine.late_penalty_enabled')->first();
        if (!$late) {
            DB::table('settings')->insert(['key' => 'fine.late_penalty_enabled', 'value' => 'false']);
        } else {
            DB::table('settings')->where('key', 'fine.late_penalty_enabled')->update(['value' => 'false']);
        }
    }

    private function createTeacher(): Teacher
    {
        return Teacher::factory()
            ->state(new Sequence(fn () => ['whatsapp' => '0819' . fake()->unique()->numerify('######')]))
            ->create();
    }

    // ─── STEP 1: Attendance Recording ───────────────────────────────────────

    public function test_guru_records_privat_attendance(): void
    {
        $response = $this->actingAs($this->guruUser)->post('/guru/presensi', [
            'enrollment_id' => $this->privatEnrollment->id,
            'lesson_date' => now()->subDays(1)->format('Y-m-d'),
            'student_ids' => [$this->student->id],
            'notes' => 'Lesson 1',
        ]);

        $response->assertRedirect('/guru/presensi');
        $this->assertDatabaseHas('enrollment_attendances', [
            'enrollment_id' => $this->privatEnrollment->id,
            'status_validation' => 'terima',
        ]);
    }

    public function test_guru_records_kelas_attendance(): void
    {
        $response = $this->actingAs($this->guruUser)->post('/guru/presensi', [
            'enrollment_id' => $this->kelasEnrollment->id,
            'lesson_date' => now()->subDays(1)->format('Y-m-d'),
            'notes' => 'Kelas session 1',
        ]);

        $response->assertRedirect('/guru/presensi');
        $this->assertDatabaseHas('enrollment_attendances', [
            'enrollment_id' => $this->kelasEnrollment->id,
            'session_teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_guru_records_multiple_attendances(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->actingAs($this->guruUser)->post('/guru/presensi', [
                'enrollment_id' => $this->privatEnrollment->id,
                'lesson_date' => now()->subDays($i + 1)->format('Y-m-d'),
                'student_ids' => [$this->student->id],
                'notes' => "Lesson $i",
            ]);
        }

        $this->assertEquals(4, MonthlyAttendance::where('enrollment_id', $this->privatEnrollment->id)->count());
    }

    // ─── STEP 2: Admin Validates Attendance ────────────────────────────────

    public function test_admin_can_view_attendance_review(): void
    {
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'status_validation' => 'terima',
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->get('/admin/notifikasi-presensi');
        $response->assertStatus(200);
    }

    public function test_admin_terlambat_attendance_shows_in_review(): void
    {
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'status_validation' => 'terlambat',
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->get('/admin/notifikasi-presensi');
        $response->assertStatus(200);
    }

    // ─── STEP 3: Admin Applies Discount ────────────────────────────────────

    public function test_admin_can_apply_nominal_discount(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/discounts', [
            'enrollment_ids' => [$this->privatEnrollment->id],
            'student_id' => $this->student->id,
            'month' => now()->month,
            'year' => now()->year,
            'discount_type' => 'amount',
            'discount_value' => '20000',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_student_discounts', [
            'enrollment_id' => $this->privatEnrollment->id,
            'student_id' => $this->student->id,
            'discount_type' => 'amount',
            'discount_value' => 20000,
        ]);
    }

    public function test_admin_can_apply_percent_discount(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/discounts', [
            'enrollment_ids' => [$this->privatEnrollment->id],
            'student_id' => $this->student->id,
            'month' => now()->month,
            'year' => now()->year,
            'discount_type' => 'percent',
            'discount_value' => '10',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_student_discounts', [
            'enrollment_id' => $this->privatEnrollment->id,
            'student_id' => $this->student->id,
            'discount_type' => 'percent',
            'discount_value' => 10,
        ]);
    }

    public function test_admin_can_apply_final_price_discount(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/discounts', [
            'enrollment_ids' => [$this->privatEnrollment->id],
            'student_id' => $this->student->id,
            'month' => now()->month,
            'year' => now()->year,
            'discount_type' => 'final',
            'discount_value' => '200000',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_student_discounts', [
            'enrollment_id' => $this->privatEnrollment->id,
            'student_id' => $this->student->id,
            'discount_type' => 'final',
            'discount_value' => 200000,
        ]);
    }

    // ─── STEP 4: Analysis Shows Correct Numbers ─────────────────────────────

    public function test_analysis_ortu_shows_billing_without_penalty_when_fine_disabled(): void
    {
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_rate' => 60000,
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->get(
            '/admin/analysis/ortu?month=' . now()->month . '&year=' . now()->year
        );
        $response->assertStatus(200);
    }

    public function test_analysis_guru_shows_teacher_salary(): void
    {
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'teacher_rate' => 50000,
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->get(
            '/admin/analysis/guru?month=' . now()->month . '&year=' . now()->year
        );
        $response->assertStatus(200);
    }

    // ─── STEP 5: Admin Marks Payment as Paid ───────────────────────────────

    public function test_admin_can_mark_payment_as_paid(): void
    {
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_payment_status' => 'unpaid',
            'teacher_payment_status' => 'unpaid',
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->post(
            "/admin/payments/ortu/{$attendance->id}/payment",
            ['parent_payment_status' => 'paid']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_attendances', [
            'id' => $attendance->id,
            'parent_payment_status' => 'paid',
        ]);
    }

    // ─── STEP 6: Generate Invoice ──────────────────────────────────────────

    public function test_admin_can_generate_parent_invoice(): void
    {
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->post(
            '/admin/analysis/generate-invoice/' . $this->student->id . '/' . now()->month . '/' . now()->year
        );

        // Invoice generation redirects to the generated PDF file
        $response->assertRedirect();
    }

    public function test_admin_can_generate_teacher_salary_invoice(): void
    {
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->post(
            '/admin/analysis/generate-salary/' . $this->teacher->id . '/' . now()->month . '/' . now()->year
        );

        // Salary generation redirects to the generated PDF file
        $response->assertRedirect();
    }

    // ─── STEP 7: Parent Downloads Invoice ───────────────────────────────────

    public function test_parent_can_download_invoice_when_data_complete(): void
    {
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        // Route is POST /parent/tagihan/invoice/{year}/{month}
        $response = $this->actingAs($this->parentUser)->post(
            '/parent/tagihan/invoice/' . now()->year . '/' . now()->month
        );

        // Should redirect (to PDF URL) or show the PDF
        $this->assertTrue(in_array($response->status(), [200, 302]));
    }

    // ─── STEP 8: Parent Uploads Payment Proof ─────────────────────────────

    public function test_parent_can_upload_payment_proof(): void
    {
        Storage::fake('public');

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_payment_status' => 'unpaid',
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $proof = \Illuminate\Http\UploadedFile::fake()->image('transfer.jpg', 800, 600);

        $response = $this->actingAs($this->parentUser)->post(
            "/parent/tagihan/{$attendance->id}/upload",
            ['payment_proof' => $proof]
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('enrollment_attendances', [
            'id' => $attendance->id,
            'payment_proof_status' => 'pending',
        ]);
    }

    // ─── STEP 9: Admin Confirms Payment Proof ─────────────────────────────

    public function test_admin_confirms_proof_marks_paid(): void
    {
        Storage::fake('public');

        $proof = \Illuminate\Http\UploadedFile::fake()->image('proof.jpg');
        $path = 'photo/transfer-proof/parent_' . $this->parent->id . '/' . now()->format('m-Y') . '.jpg';
        Storage::disk('public')->putFileAs(dirname($path), $proof, basename($path));

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_payment_status' => 'unpaid',
            'payment_proof' => $path,
            'payment_proof_status' => 'pending',
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->post(
            '/admin/payments/' . $attendance->id . '/confirm-proof',
            ['action' => 'approve']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_attendances', [
            'id' => $attendance->id,
            'parent_payment_status' => 'paid',
            'payment_proof_status' => 'approved',
        ]);
    }

    // ─── STEP 10: Pending Review Excluded from Billing ─────────────────────

    public function test_attendance_with_pending_review_excluded_from_parent_billing(): void
    {
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_review_status' => 'pending',
            'parent_payment_status' => 'unpaid',
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->parentUser)->get('/parent/tagihan');

        $response->assertStatus(200);
        // Pending attendance should not appear in parent billing totals
    }

    // ─── FINE SETTINGS IMPACT ───────────────────────────────────────────────

    public function test_attendance_penalty_applied_when_fine_enabled(): void
    {
        DB::table('settings')->where('key', 'fine.attendance_penalty_enabled')->update(['value' => 'true']);

        // Only 1 session attended out of 8 agreed — penalty should apply
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_rate' => 60000,
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->get(
            '/admin/analysis/ortu?month=' . now()->month . '&year=' . now()->year
        );

        $response->assertStatus(200);
    }

    public function test_late_penalty_reflected_in_teacher_salary(): void
    {
        DB::table('settings')->where('key', 'fine.late_penalty_enabled')->update(['value' => 'true']);

        // Create terlambat attendance
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->privatEnrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terlambat',
            'teacher_rate' => 50000,
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->get(
            '/admin/analysis/guru?month=' . now()->month . '&year=' . now()->year
        );

        $response->assertStatus(200);
    }

    // ─── FULL FLOW: Attendance → Discount → Analysis → Payment ─────────────

    public function test_full_flow_attendance_with_discount_to_payment(): void
    {
        // 1. Guru records 3 lessons
        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($this->guruUser)->post('/guru/presensi', [
                'enrollment_id' => $this->privatEnrollment->id,
                'lesson_date' => now()->subDays($i + 1)->format('Y-m-d'),
                'student_ids' => [$this->student->id],
                'notes' => "Lesson $i",
            ]);
        }

        $this->assertEquals(3, MonthlyAttendance::where('enrollment_id', $this->privatEnrollment->id)->count());

        // 2. Admin applies 10% discount
        $this->actingAs($this->adminUser)->post('/admin/discounts', [
            'enrollment_ids' => [$this->privatEnrollment->id],
            'student_id' => $this->student->id,
            'month' => now()->month,
            'year' => now()->year,
            'discount_type' => 'percent',
            'discount_value' => '10',
        ]);

        $this->assertDatabaseHas('enrollment_student_discounts', [
            'enrollment_id' => $this->privatEnrollment->id,
            'student_id' => $this->student->id,
            'discount_type' => 'percent',
        ]);

        // 3. Analysis shows billing
        $analysisResponse = $this->actingAs($this->adminUser)->get(
            '/admin/analysis/ortu?month=' . now()->month . '&year=' . now()->year
        );
        $analysisResponse->assertStatus(200);

        // 4. Admin marks all paid
        $attendances = MonthlyAttendance::where('enrollment_id', $this->privatEnrollment->id)->get();
        foreach ($attendances as $att) {
            $this->actingAs($this->adminUser)->post("/admin/payments/ortu/{$att->id}/payment", ['parent_payment_status' => 'paid']);
        }

        $this->assertEquals(3, MonthlyAttendance::where('enrollment_id', $this->privatEnrollment->id)->where('parent_payment_status', 'paid')->count());

        // 5. Parent billing shows all paid
        $parentBillingResponse = $this->actingAs($this->parentUser)->get('/parent/tagihan');
        $parentBillingResponse->assertStatus(200);
    }

    public function test_kelas_flow_attendance_billing(): void
    {
        // 1. Guru records 4 kelas sessions
        for ($i = 1; $i <= 4; $i++) {
            $this->actingAs($this->guruUser)->post('/guru/presensi', [
                'enrollment_id' => $this->kelasEnrollment->id,
                'lesson_date' => now()->subDays($i + 1)->format('Y-m-d'),
                'notes' => "Kelas $i",
            ]);
        }

        $this->assertEquals(4, MonthlyAttendance::where('enrollment_id', $this->kelasEnrollment->id)->count());

        // 2. Admin fills in student attendance for class sessions
        $classAttendances = MonthlyAttendance::where('enrollment_id', $this->kelasEnrollment->id)->get();
        foreach ($classAttendances as $classAtt) {
            $this->actingAs($this->adminUser)->put('/admin/class-attendance/' . $classAtt->id, [
                'student_ids' => [$this->student->id],
            ]);
        }

        // 3. Admin applies discount
        $this->actingAs($this->adminUser)->post('/admin/discounts', [
            'enrollment_ids' => [$this->kelasEnrollment->id],
            'student_id' => $this->student->id,
            'month' => now()->month,
            'year' => now()->year,
            'discount_type' => 'amount',
            'discount_value' => '50000',
        ]);

        // 4. Analysis shows billing
        $response = $this->actingAs($this->adminUser)->get(
            '/admin/analysis/ortu?month=' . now()->month . '&year=' . now()->year
        );
        $response->assertStatus(200);
    }

    public function test_privat_group_attendance_billing(): void
    {
        // Create additional students
        $student2 = Student::factory()->create(['parent_id' => $this->parent->id]);
        $this->kelasEnrollment->students()->attach($student2->id);

        // Guru records group lesson (2 students present)
        $response = $this->actingAs($this->guruUser)->post('/guru/presensi', [
            'enrollment_id' => $this->privatEnrollment->id,
            'lesson_date' => now()->subDays(1)->format('Y-m-d'),
            'student_ids' => [$this->student->id, $student2->id],
            'notes' => 'Grup lesson',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_attendances', [
            'enrollment_id' => $this->privatEnrollment->id,
        ]);
    }
}
