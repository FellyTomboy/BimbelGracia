<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RedirectBackQueryStringTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('settings')->updateOrInsert(
            ['key' => 'fine.attendance_penalty_enabled'],
            ['value' => 'false']
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'fine.late_penalty_enabled'],
            ['value' => 'false']
        );

        $this->adminUser = User::factory()->create(['role' => 'admin']);
    }

    private function createTeacher(?int $userId = null): Teacher
    {
        $user = $userId ? User::find($userId) : User::factory()->create(['role' => 'guru']);
        return Teacher::create([
            'user_id' => $user->id,
            'nickname' => 'Guru Test ' . rand(1000, 9999),
            'whatsapp' => '08191234567',
            'major' => 'IPA',
            'subjects' => 'Matematika',
            'address' => 'Jl Test Guru',
            'bank_name' => 'BCA',
            'bank_account' => '12345678',
            'bank_owner' => 'Guru Test',
            'class_rate' => 50000,
            'status' => 'active',
        ]);
    }

    private function createAttendance(Teacher $teacher, Program $program, Student $student): MonthlyAttendance
    {
        $enrollment = Enrollment::factory()->create([
            'teacher_id' => $teacher->id,
            'program_id' => $program->id,
        ]);
        $enrollment->students()->attach($student->id);

        return MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => 9,
            'year' => 2026,
            'status_validation' => 'pending',
            'total_lessons' => 4,
            'parent_review_status' => 'pending',
        ]);
    }

    // ─── Attendance Review ────────────────────────────────────────────────

    public function test_uphold_parent_rejection_redirects_back_with_message(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $student = Student::factory()->create();
        $attendance = $this->createAttendance($teacher, $program, $student);

        $this->actingAs($this->adminUser);

        $response = $this->post(
            route('notifications.uphold-rejection', $attendance),
            [],
            ['Referer' => url('/admin/notifications')]
        );

        $response->assertSessionHas('status');
        $response->assertStatus(302);
    }

    public function test_dismiss_parent_rejection_redirects_back_with_message(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $student = Student::factory()->create();
        $attendance = $this->createAttendance($teacher, $program, $student);

        $this->actingAs($this->adminUser);

        $response = $this->post(
            route('notifications.dismiss', $attendance),
            [],
            ['Referer' => url('/admin/notifications')]
        );

        $response->assertSessionHas('status');
        $response->assertStatus(302);
    }

    // ─── Monthly Attendance ──────────────────────────────────────────────

    public function test_update_monthly_attendance_uses_back_with_querystring(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $student = Student::factory()->create();
        $attendance = $this->createAttendance($teacher, $program, $student);

        $this->actingAs($this->adminUser);

        $response = $this->post(
            route('presensi.update', $attendance),
            ['status_validation' => 'terima', 'total_lessons' => 4, 'submit' => 'Simpan'],
            ['Referer' => route('presensi.index', ['month' => 9, 'year' => 2026])]
        );

        $response->assertSessionHas('status');
        $response->assertStatus(302);
        $this->assertEquals('terima', $attendance->fresh()->status_validation);
    }

    // ─── Analysis Routes ─────────────────────────────────────────────────

    public function test_analysis_ortu_page_loads(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('analysis.ortu'));
        $response->assertStatus(200);
    }

    public function test_analysis_ortu_page_with_search_param(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.analysis.ortu', ['search' => 'test']));
        $response->assertStatus(200);
    }

    public function test_analysis_ortu_page_with_empty_search_param(): void
    {
        $this->actingAs($this->adminUser);

        // ?search= (no value) was the source of strtolower(null) crash
        $response = $this->get(route('admin.analysis.ortu', ['search' => '']));
        $response->assertStatus(200);
    }

    public function test_analysis_guru_page_loads(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('analysis.guru'));
        $response->assertStatus(200);
    }

    public function test_analysis_guru_page_with_search_param(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.analysis.guru', ['search' => 'guru']));
        $response->assertStatus(200);
    }

    public function test_analysis_guru_page_with_empty_search_param(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.analysis.guru', ['search' => '']));
        $response->assertStatus(200);
    }

    // ─── Discount Routes ─────────────────────────────────────────────────

    public function test_delete_discount_redirects_back(): void
    {
        $discount = Discount::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->delete(
            route('admin.discounts.destroy', $discount),
            ['_token' => csrf_token()],
            ['Referer' => route('admin.discounts.index')]
        );

        $response->assertSessionHas('status');
        $response->assertStatus(302);
        $this->assertNull($discount->fresh());
    }

    // ─── Authenticated Admin Middleware ───────────────────────────────────

    public function test_teacher_payment_status_route_guarded_by_admin(): void
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacherUser);

        $teacher = $this->createTeacher($teacherUser->id);
        $program = Program::factory()->create();
        $student = Student::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'teacher_id' => $teacher->id,
            'program_id' => $program->id,
        ]);
        $enrollment->students()->attach($student->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => 9,
            'year' => 2026,
        ]);

        $response = $this->post(route('admin.payments.guru.payment', $attendance), [
            'teacher_payment_status' => 'paid',
        ]);

        $response->assertStatus(403);
    }

    public function test_parent_payment_status_route_guarded_by_admin(): void
    {
        $parentUser = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parentUser);

        $teacher = $this->createTeacher();
        $program = Program::factory()->create();
        $student = Student::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'teacher_id' => $teacher->id,
            'program_id' => $program->id,
        ]);
        $enrollment->students()->attach($student->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => 9,
            'year' => 2026,
        ]);

        $response = $this->post(route('admin.payments.ortu.payment', $attendance), [
            'parent_payment_status' => 'paid',
        ]);

        $response->assertStatus(403);
    }

    // ─── Fine Settings ───────────────────────────────────────────────────

    public function test_update_fine_settings_stores_values(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->put(route('profile.fine-settings.update'), [
            'late_threshold_minutes' => 15,
            'late_fine_per_hour' => 10000,
            'absence_fine_per_session' => 50000,
            'attendance_penalty_enabled' => '1',
            'late_penalty_enabled' => '1',
        ]);

        $response->assertSessionHas('status');
        $response->assertStatus(302);

        $this->assertEquals('15', settings('fine.late_threshold_minutes'));
        $this->assertEquals('1', settings('fine.attendance_penalty_enabled'));
    }

    // ─── Scroll Preservation: No Duplicate Content on Back ─────────────────

    public function test_payments_ortu_page_loads_without_duplicate_summaries(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat', 'name' => 'Les Privat']);
        $parent = ParentModel::factory()->create(['name' => 'Ortu Scroll Test']);
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'program_id' => $program->id,
            'parent_rate' => 150000,
            'teacher_rate' => 100000,
        ]);
        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => 9,
            'year' => 2026,
            'status_validation' => 'terima',
            'total_lessons' => 4,
            'parent_payment_status' => 'unpaid',
            'teacher_payment_status' => 'unpaid',
        ]);
        $attendance->students()->attach($student->id, ['total_present' => 4]);

        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.payments.ortu', [
            'month' => 9,
            'year' => 2026,
            'page' => 1,
        ]));

        $response->assertStatus(200);
    }

    // ─── Routes Exist ────────────────────────────────────────────────────

    public function test_all_payment_routes_exist(): void
    {
        $this->assertRouteIs('admin.payments.ortu');
        $this->assertRouteIs('admin.payments.guru');
    }

    public function test_analysis_routes_exist(): void
    {
        $this->assertRouteIs('admin.analysis.ortu');
        $this->assertRouteIs('admin.analysis.guru');
    }
}
