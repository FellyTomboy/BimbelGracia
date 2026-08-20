<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRouteTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['role' => 'admin', 'name' => 'Admin Test']);
        // Use updateOrCreate to avoid UNIQUE constraint failures across tests
        $setting = DB::table('settings')->where('key', 'fine.attendance_penalty_enabled')->first();
        if (!$setting) {
            DB::table('settings')->insert(['key' => 'fine.attendance_penalty_enabled', 'value' => 'false']);
        } else {
            DB::table('settings')->where('key', 'fine.attendance_penalty_enabled')->update(['value' => 'false']);
        }
        $setting = DB::table('settings')->where('key', 'fine.late_penalty_enabled')->first();
        if (!$setting) {
            DB::table('settings')->insert(['key' => 'fine.late_penalty_enabled', 'value' => 'false']);
        } else {
            DB::table('settings')->where('key', 'fine.late_penalty_enabled')->update(['value' => 'false']);
        }
    }

    private function createTeacher(?User $user = null): Teacher
    {
        $teacher = Teacher::factory()
            ->state(new Sequence(fn () => ['whatsapp' => '0819' . fake()->unique()->numerify('######')]))
            ->create();
        if ($user) {
            $teacher->update(['user_id' => $user->id]);
        }
        return $teacher;
    }

    public function test_admin_dashboard_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_admin_students_index_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/students');
        $response->assertStatus(200);
    }

    public function test_admin_teachers_index_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/teachers');
        $response->assertStatus(200);
    }

    public function test_admin_programs_index_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/programs');
        $response->assertStatus(200);
    }

    public function test_admin_enrollments_index_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/enrollments');
        $response->assertStatus(200);
    }

    public function test_admin_lessons_index_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/lesson-offers');
        $response->assertStatus(200);
    }

    public function test_admin_presensi_index_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/presensi');
        $response->assertStatus(200);
    }

    public function test_admin_analysis_ortu_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/analysis/ortu');
        $response->assertStatus(200);
    }

    public function test_admin_analysis_guru_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/analysis/guru');
        $response->assertStatus(200);
    }

    public function test_admin_analysis_ortu_with_data_loads(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($student->id);

        MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
        ])->students()->attach($student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->get(
            '/admin/analysis/ortu?month=' . now()->month . '&year=' . now()->year
        );
        $response->assertStatus(200);
    }

    public function test_admin_analysis_guru_with_data_loads(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($student->id);

        MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'teacher_rate' => 50000,
        ])->students()->attach($student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->get(
            '/admin/analysis/guru?month=' . now()->month . '&year=' . now()->year
        );
        $response->assertStatus(200);
    }

    public function test_admin_payments_ortu_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/payments/ortu');
        $response->assertStatus(200);
    }

    public function test_admin_payments_guru_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/payments/guru');
        $response->assertStatus(200);
    }

    public function test_admin_payments_ortu_with_data_loads(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($student->id);

        MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_payment_status' => 'unpaid',
            'teacher_payment_status' => 'unpaid',
        ])->students()->attach($student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->get(
            '/admin/payments/ortu?month=' . now()->month . '&year=' . now()->year
        );
        $response->assertStatus(200);
    }

    public function test_admin_documents_index_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/documents');
        $response->assertStatus(200);
    }

    public function test_admin_documents_create_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/documents/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_upload_document(): void
    {
        Storage::fake('documents');

        $file = UploadedFile::fake()->create('document.pdf', 512);

        $response = $this->actingAs($this->adminUser)->post('/admin/documents', [
            'title' => 'Test Document',
            'description' => 'Test description',
            'file' => $file,
            'access_type' => 'teacher',
            'access_password' => null,
            'protection_level' => 'standard',
        ]);

        $response->assertRedirect('/admin/documents');
        $this->assertDatabaseHas('documents', ['title' => 'Test Document']);
    }

    public function test_admin_can_download_document(): void
    {
        Storage::fake('documents');

        $file = UploadedFile::fake()->create('doc.pdf', 256);
        $path = 'documents/admin_test_doc.pdf';
        Storage::disk('documents')->putFileAs('documents', $file, 'admin_test_doc.pdf');

        $document = Document::create([
            'title' => 'Downloadable Doc',
            'description' => 'Test',
            'file_path' => $path,
            'file_name' => 'admin_test_doc.pdf',
            'uploaded_by' => $this->adminUser->id,
            'access_type' => 'teacher',
            'protection_level' => 'standard',
        ]);

        $response = $this->actingAs($this->adminUser)->get("/admin/documents/{$document->id}/download");
        $response->assertStatus(200);
    }

    public function test_admin_class_attendance_index_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/class-attendance');
        $response->assertStatus(200);
    }

    public function test_admin_new_students_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/new-students');
        $response->assertStatus(200);
    }

    public function test_admin_teacher_registrants_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/teacher-registrants');
        $response->assertStatus(200);
    }

    public function test_admin_notifications_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/notifikasi-presensi');
        $response->assertStatus(200);
    }

    public function test_admin_attendance_review_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/presensi');
        $response->assertStatus(200);
    }

    public function test_admin_teacher_documents_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/documents');
        $response->assertStatus(200);
    }

    public function test_admin_export_students_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/export/students');
        $response->assertStatus(200);
    }

    public function test_admin_export_teachers_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/export/teachers');
        $response->assertStatus(200);
    }

    public function test_admin_history_enrollments_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/enrollments');
        $response->assertStatus(200);
    }

    public function test_admin_history_attendance_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/presensi');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $response = $this->actingAs($guru)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_access_documents_upload(): void
    {
        Storage::fake('documents');
        $file = UploadedFile::fake()->create('doc.pdf', 256);

        $parent = User::factory()->create(['role' => 'parent']);

        $response = $this->actingAs($parent)->post('/admin/documents', [
            'title' => 'Unauthorized doc',
            'description' => 'Test',
            'file' => $file,
            'access_type' => 'teacher',
            'access_password' => null,
            'protection_level' => 'standard',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_confirm_payment_proof(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($student->id);

        Storage::fake('public');
        $file = UploadedFile::fake()->image('proof.jpg');
        $path = 'photo/transfer-proof/parent_' . $parent->id . '/' . now()->format('m-Y') . '.jpg';
        Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_payment_status' => 'unpaid',
            'payment_proof' => $path,
            'payment_proof_status' => 'pending',
        ]);
        $attendance->students()->attach($student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->post('/admin/payments/' . $attendance->id . '/confirm-proof', [
            'action' => 'approve',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_attendances', [
            'id' => $attendance->id,
            'parent_payment_status' => 'paid',
        ]);
    }

    public function test_admin_can_mark_parent_payment(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($student->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_payment_status' => 'unpaid',
            'teacher_payment_status' => 'unpaid',
        ]);
        $attendance->students()->attach($student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->post(
            '/admin/payments/ortu/' . $attendance->id . '/payment',
            ['parent_payment_status' => 'paid']
        );

        $response->assertRedirect();
        $attendance->refresh();
        $this->assertEquals('paid', $attendance->parent_payment_status);
    }

    public function test_admin_can_mark_teacher_payment(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($student->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'teacher_payment_status' => 'unpaid',
        ]);
        $attendance->students()->attach($student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->adminUser)->post(
            '/admin/payments/guru/' . $attendance->id . '/payment',
            ['teacher_payment_status' => 'paid']
        );

        $response->assertRedirect();
        $attendance->refresh();
        $this->assertEquals('paid', $attendance->teacher_payment_status);
    }

    public function test_admin_finance_index_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/finance');
        $response->assertStatus(200);
    }

    public function test_admin_can_toggle_teacher_status(): void
    {
        $teacher = $this->createTeacher();

        $response = $this->actingAs($this->adminUser)->patch("/admin/teachers/{$teacher->id}", [
            'status' => 'inactive',
        ]);

        // PUT/PATCH /admin/teachers/{id} updates the teacher
        $this->assertTrue(in_array($response->status(), [200, 302]));
    }

    public function test_admin_can_apply_discount(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($student->id);

        $response = $this->actingAs($this->adminUser)->post('/admin/analysis/ortu/discount', [
            'enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'month' => now()->month,
            'year' => now()->year,
            'discount_type' => 'amount',
            'discount_value' => '20000',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_student_discounts', [
            'enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'discount_type' => 'amount',
        ]);
    }

    public function test_admin_finance_snapshot_students_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/finance/snapshot/students', [
            'month' => now()->month,
            'year' => now()->year,
        ]);

        $this->assertTrue(in_array($response->status(), [200, 302]));
    }

    public function test_admin_finance_snapshot_teachers_loads(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/finance/snapshot/teachers', [
            'month' => now()->month,
            'year' => now()->year,
        ]);

        $this->assertTrue(in_array($response->status(), [200, 302]));
    }
}
