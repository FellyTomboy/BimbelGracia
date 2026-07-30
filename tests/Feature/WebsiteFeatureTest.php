<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BankAccount;
use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\ClassStudent;
use App\Models\ClassStudentSession;
use App\Models\Enrollment;
use App\Models\LessonOffer;
use App\Models\MonthlyAttendance;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteFeatureTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────

    private function createUserWithRole(UserRole $role, bool $mustChangePassword = false): User
    {
        return User::factory()->create([
            'role' => $role,
            'must_change_password' => $mustChangePassword,
        ]);
    }

    private function createAdmin(): User
    {
        return $this->createUserWithRole(UserRole::Admin);
    }

    private function createTeacherUser(): User
    {
        $user = $this->createUserWithRole(UserRole::Guru);
        Teacher::factory()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'class_rate' => 50000,
            'status' => 'active',
        ]);
        return $user;
    }

    private function createStudentUser(): User
    {
        $user = $this->createUserWithRole(UserRole::Murid);
        Student::factory()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'status' => 'active',
        ]);
        return $user;
    }

    private function seedBasicData(): array
    {
        $admin = $this->createAdmin();

        $program = Program::factory()->create([
            'name' => 'Program Test',
            'type' => 'privat',
            'default_parent_rate' => 200000,
            'default_teacher_rate' => 100000,
            'status' => 'active',
        ]);

        $teacherUser = $this->createTeacherUser();
        $teacher = Teacher::where('user_id', $teacherUser->id)->first();

        $studentUser = $this->createStudentUser();
        $student = Student::where('user_id', $studentUser->id)->first();

        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'parent_rate' => 200000,
            'teacher_rate' => 100000,
            'validation_status' => 1,
            'status' => 'active',
        ]);

        $enrollment->students()->attach($student->id);

        $classStudent = ClassStudent::factory()->create([
            'name' => 'Class Student Test',
            'rate_per_meeting' => 30000,
            'status' => 'active',
        ]);

        $classGroup = ClassGroup::factory()->create([
            'name' => 'Kelas Test',
            'subject' => 'Matematika',
            'teacher_id' => $teacher->id,
        ]);

        $classGroup->students()->attach($classStudent->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'lesson_date' => now()->subDays(5),
            'status_validation' => 'terima',
            'parent_payment_status' => 'paid',
            'teacher_payment_status' => 'paid',
            'created_by' => $admin->id,
            'parent_rate' => 200000,
            'teacher_rate' => 100000,
        ]);

        $attendance->students()->attach($student->id, ['total_present' => 2]);

        $classSession = ClassSession::factory()->create([
            'class_group_id' => $classGroup->id,
            'teacher_id' => $teacher->id,
            'session_date' => now()->addDays(1)->toDateString(),
            'session_time' => '15:00:00',
            'subject' => 'Matematika',
        ]);

        $classSession->students()->attach($student->id, ['is_present' => true]);

        $classStudentSession = ClassStudentSession::factory()->create([
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'notes' => 'Sesi test',
        ]);

        $classStudentSession->students()->attach($classStudent->id);

        BankAccount::factory()->create([
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Bimbel Test',
            'status' => 'active',
        ]);

        LessonOffer::factory()->create([
            'education_level' => 'SMA',
            'subject' => 'Matematika',
            'schedules' => [['day' => 'Sabtu', 'time' => '15:00']],
            'status' => 'open',
            'created_by' => $admin->id,
        ]);

        return compact(
            'admin', 'program', 'teacherUser', 'teacher', 'studentUser', 'student',
            'enrollment', 'classStudent', 'classGroup', 'attendance',
            'classSession', 'classStudentSession'
        );
    }

    // ──────────────────────────────────────────────
    // 1. GUEST (UNAUTHENTICATED) TESTS
    // ──────────────────────────────────────────────

    public function test_guest_can_access_landing_page(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_guest_can_access_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_admin_routes(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_guru_routes(): void
    {
        $response = $this->get('/guru');
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_murid_routes(): void
    {
        $response = $this->get('/murid');
        $response->assertRedirect('/login');
    }

    public function test_guest_can_login(): void
    {
        $user = $this->createAdmin();

        $response = $this->post('/login', [
            'phone' => $user->phone,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_guest_cannot_login_with_invalid_credentials(): void
    {
        $this->createAdmin();

        $response = $this->post('/login', [
            'phone' => '6289999999999',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    // ──────────────────────────────────────────────
    // 2. STUDENT (MURID) TESTS
    // ──────────────────────────────────────────────

    public function test_student_can_access_murid_dashboard(): void
    {
        $user = $this->createStudentUser();
        $response = $this->actingAs($user)->get('/murid');
        $response->assertStatus(200);
    }

    public function test_student_can_access_history(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['studentUser'])->get(route('murid.history.index'));
        $response->assertStatus(200);
    }

    public function test_student_can_access_billing(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['studentUser'])->get(route('murid.billing.index'));
        $response->assertStatus(200);
    }

    public function test_student_cannot_access_admin_routes(): void
    {
        $user = $this->createStudentUser();
        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_guru_routes(): void
    {
        $user = $this->createStudentUser();
        $response = $this->actingAs($user)->get('/guru');
        $response->assertStatus(403);
    }

    public function test_student_billing_returns_ok(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['studentUser'])->get(route('murid.billing.index'));
        $response->assertStatus(200);
    }

    // ──────────────────────────────────────────────
    // 3. TEACHER (GURU) TESTS
    // ──────────────────────────────────────────────

    public function test_teacher_can_access_guru_dashboard(): void
    {
        $user = $this->createTeacherUser();
        $response = $this->actingAs($user)->get('/guru');
        $response->assertStatus(200);
    }

    public function test_teacher_can_access_presensi_index(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['teacherUser'])->get(route('guru.presensi.index'));
        $response->assertStatus(200);
    }

    public function test_teacher_can_access_presensi_create(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['teacherUser'])->get(route('guru.presensi.create'));
        $response->assertStatus(200);
    }

    public function test_teacher_can_store_presensi(): void
    {
        $data = $this->seedBasicData();
        $enrollment = $data['enrollment'];
        $student = $data['student'];

        $response = $this->actingAs($data['teacherUser'])->post(route('guru.presensi.store'), [
            'enrollment_id' => $enrollment->id,
            'lesson_date' => now()->subDays(2)->format('Y-m-d'),
            'notes' => 'Test presensi',
            'student_totals' => [$student->id => 1],
        ]);

        $response->assertRedirect(route('guru.presensi.index'));
        $this->assertDatabaseHas('enrollment_attendances', [
            'enrollment_id' => $enrollment->id,
            'notes' => 'Test presensi',
        ]);
    }

    public function test_teacher_can_access_lesson_offers(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['teacherUser'])->get(route('guru.tawaran.index'));
        $response->assertStatus(200);
    }

    public function test_teacher_can_access_history(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['teacherUser'])->get(route('guru.history.index'));
        $response->assertStatus(200);
    }

    public function test_teacher_can_access_salary_projection(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['teacherUser'])->get(route('guru.salary-projection.index'));
        $response->assertStatus(200);
    }

    public function test_teacher_cannot_access_admin_routes(): void
    {
        $user = $this->createTeacherUser();
        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_teacher_cannot_access_murid_routes(): void
    {
        $user = $this->createTeacherUser();
        $response = $this->actingAs($user)->get('/murid');
        $response->assertStatus(403);
    }

    // ──────────────────────────────────────────────
    // 4. ADMIN TESTS
    // ──────────────────────────────────────────────

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
    }

    // ── 4a. Students CRUD ──

    public function test_admin_can_list_students(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.students.index'));
        $response->assertStatus(200);
        $response->assertSee($data['student']->name);
    }

    public function test_admin_can_create_student(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Student Baru',
            'whatsapp' => '081234567890',
            'whatsapp_primary' => '',
            'whatsapp_secondary' => '',
            'address' => 'Alamat test',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseHas('users', ['phone' => '081234567890']);
        $this->assertDatabaseHas('students', ['name' => 'Student Baru']);
    }

    public function test_admin_can_edit_student(): void
    {
        $data = $this->seedBasicData();
        $student = $data['student'];
        $response = $this->actingAs($data['admin'])->put(route('admin.students.update', $student->id), [
            'name' => 'Student Updated',
            'whatsapp' => $student->user->phone,
            'whatsapp_primary' => '',
            'whatsapp_secondary' => '',
            'address' => 'Alamat updated',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseHas('students', ['name' => 'Student Updated']);
    }

    public function test_admin_can_hibernate_student(): void
    {
        $data = $this->seedBasicData();
        $student = $data['student'];
        $response = $this->actingAs($data['admin'])->delete(route('admin.students.destroy', $student->id));

        $response->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'hibernasi']);
    }

    public function test_admin_can_restore_student(): void
    {
        $data = $this->seedBasicData();
        $student = $data['student'];
        $student->update(['status' => 'hibernasi']);
        $student->delete();

        $response = $this->actingAs($data['admin'])->post(route('admin.students.restore', $student->id));
        $response->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'active']);
    }

    // ── 4b. Teachers CRUD ──

    public function test_admin_can_list_teachers(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.teachers.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_teacher(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->post(route('admin.teachers.store'), [
            'name' => 'Guru Baru',
            'whatsapp' => '081234567890',
            'major' => 'Matematika',
            'subjects' => 'Matematika, Fisika',
            'class_rate' => 50000,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.teachers.index'));
        $this->assertDatabaseHas('users', ['phone' => '081234567890']);
        $this->assertDatabaseHas('teachers', ['name' => 'Guru Baru']);
    }

    public function test_admin_can_hibernate_teacher(): void
    {
        $data = $this->seedBasicData();
        $teacher = $data['teacher'];
        $response = $this->actingAs($data['admin'])->delete(route('admin.teachers.destroy', $teacher->id));

        $response->assertRedirect(route('admin.teachers.index'));
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'status' => 'hibernasi']);
    }

    // ── 4c. Programs CRUD ──

    public function test_admin_can_list_programs(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.programs.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_program(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->post(route('admin.programs.store'), [
            'name' => 'Program Baru',
            'type' => 'privat',
            'subject' => 'Matematika',
            'description' => 'Deskripsi program',
            'default_parent_rate' => 250000,
            'default_teacher_rate' => 150000,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.programs.index'));
        $this->assertDatabaseHas('programs', ['name' => 'Program Baru']);
    }

    // ── 4d. Enrollments CRUD ──

    public function test_admin_can_list_enrollments(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.enrollments.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_enrollment(): void
    {
        $admin = $this->createAdmin();
        $program = Program::factory()->create(['status' => 'active']);
        $teacherUser = $this->createTeacherUser();
        $teacher = Teacher::where('user_id', $teacherUser->id)->first();
        $studentUser = $this->createStudentUser();
        $student = Student::where('user_id', $studentUser->id)->first();

        $response = $this->actingAs($admin)->post(route('admin.enrollments.store'), [
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'parent_rate' => 200000,
            'teacher_rate' => 100000,
            'status' => 'active',
            'student_ids' => [$student->id],
        ]);

        $response->assertRedirect(route('admin.enrollments.index'));
        $this->assertDatabaseHas('enrollments', [
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    // ── 4e. Bank Accounts CRUD ──

    public function test_admin_can_list_bank_accounts(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.bank-accounts.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_bank_account(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->post(route('admin.bank-accounts.store'), [
            'bank_name' => 'BNI',
            'account_number' => '9876543210',
            'account_holder' => 'Bimbel Test',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.bank-accounts.index'));
        $this->assertDatabaseHas('bank_accounts', ['account_number' => '9876543210']);
    }

    // ── 4f. Class Students CRUD ──

    public function test_admin_can_list_class_students(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.class-students.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_class_student(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->post(route('admin.class-students.store'), [
            'name' => 'Class Student Baru',
            'whatsapp_primary' => '081234567890',
            'whatsapp_secondary' => '',
            'rate_per_meeting' => 35000,
            'status' => 'active',
            'notes' => 'Test',
        ]);

        $response->assertRedirect(route('admin.class-students.index'));
        $this->assertDatabaseHas('class_students', ['name' => 'Class Student Baru']);
    }

    // ── 4g. Class Student Sessions ──

    public function test_admin_can_list_class_student_sessions(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.class-student-sessions.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_class_student_session(): void
    {
        $data = $this->seedBasicData();
        $classStudent = $data['classStudent'];

        $response = $this->actingAs($data['admin'])->post(route('admin.class-student-sessions.store'), [
            'class_student_ids' => [$classStudent->id],
            'session_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '15:30',
            'notes' => 'Sesi baru',
        ]);

        $response->assertRedirect(route('admin.class-student-sessions.index'));
        $this->assertDatabaseHas('class_student_sessions', ['notes' => 'Sesi baru']);
    }

    // ── 4h. Lesson Offers ──

    public function test_admin_can_list_lesson_offers(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.lesson-offers.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_lesson_offer(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->post(route('admin.lesson-offers.store'), [
            'code' => 'OFFER123',
            'education_level' => 'SMA',
            'subject' => 'Fisika',
            'schedules' => [['day' => 'Senin', 'time' => 'sore']],
            'note' => 'Permintaan les',
            'status' => 'open',
            'contact_whatsapp' => '081234567890',
        ]);

        $response->assertRedirect(route('admin.lesson-offers.index'));
        $this->assertDatabaseHas('lesson_offers', ['subject' => 'Fisika']);
    }

    // ── 4i. Attendance (Presensi) ──

    public function test_admin_can_access_presensi_index(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.presensi.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_presensi_detail(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.presensi.show', $data['attendance']->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_validate_attendance(): void
    {
        $data = $this->seedBasicData();
        $attendance = $data['attendance'];
        $attendance->update(['status_validation' => 'pending', 'validated_at' => null, 'validated_by' => null]);

        $response = $this->actingAs($data['admin'])->post(route('admin.presensi.validate', $attendance->id), [
            'status' => 'terima',
        ]);

        $response->assertRedirect(route('admin.presensi.index'));
        $this->assertDatabaseHas('enrollment_attendances', [
            'id' => $attendance->id,
            'status_validation' => 'terima',
        ]);
    }

    // ── 4j. Analysis ──

    public function test_admin_can_access_analysis_ortu(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.analysis.ortu'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_analysis_ortu_kelas(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.analysis.ortu-kelas'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_analysis_guru(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.analysis.guru'));
        $response->assertStatus(200);
    }

    // ── 4k. Payments ──

    public function test_admin_can_access_payments_ortu(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.payments.ortu'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_payments_guru(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.payments.guru'));
        $response->assertStatus(200);
    }

    public function test_admin_can_update_parent_payment(): void
    {
        $data = $this->seedBasicData();
        $attendance = $data['attendance'];

        $response = $this->actingAs($data['admin'])->post(route('admin.payments.ortu.payment', $attendance->id), [
            'parent_payment_status' => 'unpaid',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_attendances', [
            'id' => $attendance->id,
            'parent_payment_status' => 'unpaid',
        ]);
    }

    public function test_admin_can_update_teacher_payment(): void
    {
        $data = $this->seedBasicData();
        $attendance = $data['attendance'];

        $response = $this->actingAs($data['admin'])->post(route('admin.payments.guru.payment', $attendance->id), [
            'teacher_payment_status' => 'unpaid',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_attendances', [
            'id' => $attendance->id,
            'teacher_payment_status' => 'unpaid',
        ]);
    }

    // ── 4l. Discounts ──

    public function test_admin_can_access_discounts(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.discounts.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_store_discounts(): void
    {
        $data = $this->seedBasicData();
        $enrollment = $data['enrollment'];

        $response = $this->actingAs($data['admin'])->post(route('admin.discounts.store'), [
            'month' => now()->month,
            'year' => now()->year,
            'enrollment_ids' => [$enrollment->id],
            'discount_type' => 'percent',
            'discount_value' => 10,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_student_discounts', [
            'enrollment_id' => $enrollment->id,
            'discount_type' => 'percent',
            'discount_value' => 10,
        ]);
    }

    // ── 4m. Finance ──

    public function test_admin_can_access_finance(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.finance.index'));
        $response->assertStatus(200);
    }

    // ── 4n. Class Reports ──

    public function test_admin_can_access_class_reports(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.class-reports.index'));
        $response->assertStatus(200);
    }

    // ── 4o. History ──

    public function test_admin_can_access_history_students(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.history.students'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_history_teachers(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.history.teachers'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_history_payments(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.history.payments'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_history_audit(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.history.audit'));
        $response->assertStatus(200);
    }

    // ── 4p. Export ──

    public function test_admin_can_access_export_index(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.export.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_export_students(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.export.students'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_export_teachers(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.export.teachers'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_export_lessons(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.export.lessons'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_export_attendances(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.export.attendances'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_export_class_groups(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.export.class-groups'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_export_class_sessions(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.export.class-sessions'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_export_audit(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.export.audit'));
        $response->assertStatus(200);
    }

    // ── 4q. Analysis Discount Updates ──

    public function test_admin_can_update_enrollment_discount(): void
    {
        $data = $this->seedBasicData();
        $enrollment = $data['enrollment'];
        $student = $data['student'];

        $response = $this->actingAs($data['admin'])->post(route('admin.analysis.ortu-discount'), [
            'month' => now()->month,
            'year' => now()->year,
            'enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'discount_type' => 'percent',
            'discount_value' => 15,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_student_discounts', [
            'enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'discount_type' => 'percent',
            'discount_value' => 15,
        ]);
    }

    public function test_admin_can_update_class_discount(): void
    {
        $data = $this->seedBasicData();
        $classStudent = $data['classStudent'];

        $response = $this->actingAs($data['admin'])->post(route('admin.analysis.ortu-class-discount'), [
            'month' => now()->month,
            'year' => now()->year,
            'class_student_id' => $classStudent->id,
            'discount_type' => 'amount',
            'discount_value' => 5000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('class_student_discounts', [
            'class_student_id' => $classStudent->id,
            'discount_type' => 'amount',
            'discount_value' => 5000,
        ]);
    }

    // ── 4r. Finance Snapshots ──

    public function test_admin_can_snapshot_students(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->post(route('admin.finance.snapshot.students'), [
            'month' => now()->month,
            'year' => now()->year,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('monthly_student_snapshots', [
            'month' => now()->month,
            'year' => now()->year,
        ]);
    }

    public function test_admin_can_snapshot_teachers(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->post(route('admin.finance.snapshot.teachers'), [
            'month' => now()->month,
            'year' => now()->year,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('monthly_teacher_snapshots', [
            'month' => now()->month,
            'year' => now()->year,
        ]);
    }

    // ── 4s. Profile ──

    public function test_admin_can_access_profile(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->get(route('profile.edit'));
        $response->assertStatus(200);
    }

    public function test_admin_can_update_profile(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->patch(route('profile.update'), [
            'name' => 'Admin Updated',
            'email' => $admin->email,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('users', ['name' => 'Admin Updated']);
    }

    // ── 4t. Password Force ──

    public function test_user_with_must_change_password_is_redirected(): void
    {
        $user = $this->createUserWithRole(UserRole::Admin, true);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('password.force.edit'));
    }

    public function test_user_can_force_change_password(): void
    {
        $user = $this->createUserWithRole(UserRole::Admin, true);
        $response = $this->actingAs($user)->put(route('password.force.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertFalse($user->fresh()->must_change_password);
    }

    // ── 4u. Lesson Offers Admin ──

    public function test_admin_can_hibernate_lesson_offer(): void
    {
        $data = $this->seedBasicData();
        $offer = LessonOffer::first();

        $response = $this->actingAs($data['admin'])->delete(route('admin.lesson-offers.destroy', $offer->id));

        $response->assertRedirect(route('admin.lesson-offers.index'));
        $this->assertSoftDeleted($offer);
    }

    // ── 4v. Class Student Sessions Table View ──

    public function test_admin_can_access_class_student_sessions_table(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.class-student-sessions.table'));
        $response->assertStatus(200);
    }

    // ── 4w. Inactive Pages ──

    public function test_admin_can_access_inactive_students(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.students.inactive'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_inactive_teachers(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.teachers.inactive'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_inactive_programs(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.programs.inactive'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_inactive_enrollments(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.enrollments.inactive'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_inactive_lesson_offers(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.lesson-offers.inactive'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_inactive_bank_accounts(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.bank-accounts.inactive'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_inactive_class_students(): void
    {
        $data = $this->seedBasicData();
        $response = $this->actingAs($data['admin'])->get(route('admin.class-students.inactive'));
        $response->assertStatus(200);
    }
}