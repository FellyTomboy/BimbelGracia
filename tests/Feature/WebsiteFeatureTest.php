<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BankAccount;
use App\Models\Enrollment;
use App\Models\LessonOffer;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteFeatureTest extends TestCase
{
    use RefreshDatabase;

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
        $user = $this->createUserWithRole(UserRole::Parent);
        $parent = ParentModel::factory()->create(['user_id' => $user->id, 'name' => $user->name]);
        Student::factory()->create([
            'parent_id' => $parent->id,
            'nickname' => $user->name,
            'full_name' => $user->name,
            'status' => 'active',
        ]);
        return $user;
    }

    private function seedBasicData(): array
    {
        $admin = $this->createAdmin();

        $program = Program::factory()->create([
            'name' => 'Program Test', 'type' => 'privat',
            'default_parent_rate' => 200000, 'default_teacher_rate' => 100000, 'status' => 'active',
        ]);

        $teacherUser = $this->createTeacherUser();
        $teacher = Teacher::where('user_id', $teacherUser->id)->first();

        $studentUser = $this->createStudentUser();
        $parent = ParentModel::where('user_id', $studentUser->id)->first();
        $student = Student::where('parent_id', $parent->id)->first();

        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id, 'teacher_id' => $teacher->id,
            'parent_rate' => 200000, 'teacher_rate' => 100000,
            'validation_status' => 1, 'status' => 'active',
        ]);
        $enrollment->students()->attach($student->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month, 'year' => now()->year,
            'lesson_date' => now()->subDays(5),
            'status_validation' => 'terima',
            'parent_payment_status' => 'paid', 'teacher_payment_status' => 'paid',
            'created_by' => $admin->id, 'parent_rate' => 200000, 'teacher_rate' => 100000,
        ]);
        $attendance->students()->attach($student->id, ['total_present' => 2]);

        BankAccount::factory()->create(['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'Bimbel Test', 'status' => 'active']);

        LessonOffer::factory()->create([
            'education_level' => 'SMA', 'subject' => 'Matematika',
            'schedules' => [['day' => 'Sabtu', 'time' => '15:00']], 'status' => 'open', 'created_by' => $admin->id,
        ]);

        return compact('admin', 'program', 'teacherUser', 'teacher', 'studentUser', 'student', 'enrollment', 'attendance');
    }

    // ── 1. Guest Tests ──

    public function test_guest_can_access_landing_page(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_guest_can_access_login_page(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guest_cannot_access_admin_routes(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_guest_cannot_access_guru_routes(): void
    {
        $this->get('/guru')->assertRedirect('/login');
    }

    public function test_guest_cannot_access_parent_routes(): void
    {
        $this->get('/parent')->assertRedirect('/login');
    }

    public function test_guest_can_login(): void
    {
        $user = $this->createAdmin();
        $this->post('/login', ['phone' => $user->phone, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    // ── 2. Murid Tests ──

    public function test_student_can_access_parent_dashboard(): void
    {
        $this->actingAs($this->createStudentUser())->get('/parent')->assertStatus(200);
    }

    public function test_student_can_access_history(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['studentUser'])->get(route('parent.history.index'))->assertStatus(200);
    }

    public function test_student_can_access_billing(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['studentUser'])->get(route('parent.billing.index'))->assertStatus(200);
    }

    public function test_parent_must_complete_invoice_data_before_downloading_invoice(): void
    {
        $data = $this->seedBasicData();
        $parent = $data['studentUser']->parent;
        $student = $parent->students()->first();

        $parent->update(['name' => null, 'address' => null]);
        $student->update(['full_name' => null]);

        $this->actingAs($data['studentUser'])
            ->post(route('parent.billing.download-invoice', ['year' => now()->year, 'month' => now()->month]))
            ->assertRedirect(route('parent.billing.complete-data', [
                'redirect_to' => route('parent.billing.download-invoice', ['year' => now()->year, 'month' => now()->month]),
            ]));
    }

    public function test_student_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->createStudentUser())->get('/admin')->assertStatus(403);
    }

    public function test_student_cannot_access_guru_routes(): void
    {
        $this->actingAs($this->createStudentUser())->get('/guru')->assertStatus(403);
    }

    // ── 3. Teacher Tests ──

    public function test_teacher_can_access_guru_dashboard(): void
    {
        $this->actingAs($this->createTeacherUser())->get('/guru')->assertStatus(200);
    }

    public function test_teacher_can_access_presensi_index(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['teacherUser'])->get(route('guru.presensi.index'))->assertStatus(200);
    }

    public function test_teacher_can_access_presensi_create(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['teacherUser'])->get(route('guru.presensi.create'))->assertStatus(200);
    }

    public function test_teacher_can_store_presensi(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['teacherUser'])->post(route('guru.presensi.store'), [
            'enrollment_id' => $data['enrollment']->id,
            'lesson_date' => now()->subDays(2)->format('Y-m-d'),
            'notes' => 'Test presensi',
            'student_ids' => [$data['student']->id],
        ])->assertRedirect(route('guru.presensi.index'));
        $this->assertDatabaseHas('enrollment_attendances', ['enrollment_id' => $data['enrollment']->id, 'notes' => 'Test presensi']);
    }

    public function test_teacher_can_access_lesson_offers(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['teacherUser'])->get(route('guru.tawaran.index'))->assertStatus(200);
    }

    public function test_teacher_can_access_history(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['teacherUser'])->get(route('guru.history.index'))->assertStatus(200);
    }

    public function test_teacher_can_access_salary_projection(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['teacherUser'])->get(route('guru.salary-projection.index'))->assertStatus(200);
    }

    public function test_teacher_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->createTeacherUser())->get('/admin')->assertStatus(403);
    }

    public function test_teacher_cannot_access_parent_routes(): void
    {
        $this->actingAs($this->createTeacherUser())->get('/parent')->assertStatus(403);
    }

    // ── 4. Admin Tests ──

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->actingAs($this->createAdmin())->get('/admin')->assertStatus(200);
    }

    public function test_admin_can_list_students(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.students.index'))->assertStatus(200)->assertSee($data['student']->name);
    }

    public function test_admin_can_create_student_with_nickname_and_nullable_parent_name(): void
    {
        $this->actingAs($this->createAdmin())->post(route('admin.students.store'), [
            'nickname' => 'Student Baru',
            'full_name' => 'Student Baru Lengkap',
            'parent_name' => '',
            'whatsapp' => '081234567890',
            'address' => 'Alamat test',
            'status' => 'active',
        ])->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('users', ['phone' => '081234567890']);
        $this->assertDatabaseHas('students', ['nickname' => 'Student Baru', 'full_name' => 'Student Baru Lengkap']);

        $student = \App\Models\Student::where('nickname', 'Student Baru')->first();
        $this->assertNotNull($student);
        $this->assertNull($student->parent?->name);
    }

    public function test_admin_can_edit_student(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->put(route('admin.students.update', $data['student']->id), [
            'nickname' => 'Student Updated',
            'full_name' => 'Student Updated Lengkap',
            'whatsapp' => $data['student']->parent->user->phone,
            'address' => 'Alamat updated',
            'status' => 'active',
        ])->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseHas('students', ['nickname' => 'Student Updated', 'full_name' => 'Student Updated Lengkap']);
    }

    public function test_admin_can_hibernate_student(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->delete(route('admin.students.destroy', $data['student']->id))
            ->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseHas('students', ['id' => $data['student']->id, 'status' => 'hibernasi']);
    }

    public function test_admin_can_restore_student(): void
    {
        $data = $this->seedBasicData();
        $data['student']->update(['status' => 'hibernasi']);
        $data['student']->delete();
        $this->actingAs($data['admin'])->post(route('admin.students.restore', $data['student']->id))
            ->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseHas('students', ['id' => $data['student']->id, 'status' => 'active']);
    }

    public function test_admin_can_list_teachers(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.teachers.index'))->assertStatus(200);
    }

    public function test_admin_can_create_teacher(): void
    {
        $this->actingAs($this->createAdmin())->post(route('admin.teachers.store'), [
            'name' => 'Guru Baru', 'whatsapp' => '081234567890',
            'major' => 'Matematika', 'subjects' => 'Matematika, Fisika',
            'class_rate' => 50000, 'status' => 'active',
        ])->assertRedirect(route('admin.teachers.index'));
        $this->assertDatabaseHas('users', ['phone' => '081234567890']);
        $this->assertDatabaseHas('teachers', ['name' => 'Guru Baru']);
    }

    public function test_admin_can_hibernate_teacher(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->delete(route('admin.teachers.destroy', $data['teacher']->id))
            ->assertRedirect(route('admin.teachers.index'));
        $this->assertDatabaseHas('teachers', ['id' => $data['teacher']->id, 'status' => 'hibernasi']);
    }

    public function test_admin_can_list_programs(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.programs.index'))->assertStatus(200);
    }

    public function test_admin_can_create_program(): void
    {
        $this->actingAs($this->createAdmin())->post(route('admin.programs.store'), [
            'name' => 'Program Baru', 'type' => 'privat', 'subject' => 'Matematika',
            'description' => 'Deskripsi program', 'default_parent_rate' => 250000, 'default_teacher_rate' => 150000, 'status' => 'active',
        ])->assertRedirect(route('admin.programs.index'));
        $this->assertDatabaseHas('programs', ['name' => 'Program Baru']);
    }

    public function test_admin_can_list_enrollments(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.enrollments.index'))->assertStatus(200);
    }

    public function test_admin_can_create_enrollment(): void
    {
        $admin = $this->createAdmin();
        $program = Program::factory()->create(['status' => 'active']);
        $teacherUser = $this->createTeacherUser();
        $teacher = Teacher::where('user_id', $teacherUser->id)->first();
        $studentUser = $this->createStudentUser();
        $parent = ParentModel::where('user_id', $studentUser->id)->first();
        $student = Student::where('parent_id', $parent->id)->first();

        $this->actingAs($admin)->post(route('admin.enrollments.store'), [
            'program_id' => $program->id, 'teacher_id' => $teacher->id,
            'parent_rate' => 200000, 'teacher_rate' => 100000, 'status' => 'active',
            'student_ids' => [$student->id],
            'agreed_sessions_per_month' => 4,
        ])->assertRedirect(route('admin.enrollments.index'));
        $this->assertDatabaseHas('enrollments', ['program_id' => $program->id, 'teacher_id' => $teacher->id]);
    }

    public function test_admin_can_list_bank_accounts(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.bank-accounts.index'))->assertStatus(200);
    }

    public function test_admin_can_create_bank_account(): void
    {
        $this->actingAs($this->createAdmin())->post(route('admin.bank-accounts.store'), [
            'bank_name' => 'BNI', 'account_number' => '9876543210', 'account_holder' => 'Bimbel Test', 'status' => 'active',
        ])->assertRedirect(route('admin.bank-accounts.index'));
        $this->assertDatabaseHas('bank_accounts', ['account_number' => '9876543210']);
    }

    public function test_admin_can_list_class_student_sessions(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.class-student-sessions.index'))->assertStatus(200);
    }

    public function test_admin_can_list_lesson_offers(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.lesson-offers.index'))->assertStatus(200);
    }

    public function test_admin_can_create_lesson_offer(): void
    {
        $this->actingAs($this->createAdmin())->post(route('admin.lesson-offers.store'), [
            'code' => 'OFFER123', 'education_level' => 'SMA', 'subject' => 'Fisika',
            'schedules' => [['day' => 'Senin', 'time' => 'sore']], 'note' => 'Permintaan les',
            'status' => 'open', 'contact_whatsapp' => '081234567890',
        ])->assertRedirect(route('admin.lesson-offers.index'));
        $this->assertDatabaseHas('lesson_offers', ['subject' => 'Fisika']);
    }

    public function test_admin_can_access_presensi_index(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.presensi.index'))->assertStatus(200);
    }

    public function test_admin_can_view_presensi_detail(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.presensi.show', $data['attendance']->id))->assertStatus(200);
    }

    public function test_admin_can_validate_attendance(): void
    {
        $data = $this->seedBasicData();
        $data['attendance']->update(['status_validation' => 'pending', 'validated_at' => null, 'validated_by' => null]);
        $this->actingAs($data['admin'])->post(route('admin.presensi.validate', $data['attendance']->id), ['status' => 'terima'])
            ->assertRedirect(route('admin.presensi.index'));
        $this->assertDatabaseHas('enrollment_attendances', ['id' => $data['attendance']->id, 'status_validation' => 'terima']);
    }

    public function test_admin_can_access_analysis_ortu(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.analysis.ortu'))->assertStatus(200);
    }

    public function test_admin_can_access_analysis_ortu_kelas(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.analysis.ortu'))->assertStatus(200);
    }

    public function test_admin_can_access_analysis_guru(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.analysis.guru'))->assertStatus(200);
    }

    public function test_admin_can_access_payments_ortu(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.payments.ortu'))->assertStatus(200);
    }

    public function test_admin_can_access_payments_guru(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.payments.guru'))->assertStatus(200);
    }

    public function test_admin_can_update_parent_payment(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->post(route('admin.payments.ortu.payment', $data['attendance']->id), ['parent_payment_status' => 'unpaid'])
            ->assertRedirect();
        $this->assertDatabaseHas('enrollment_attendances', ['id' => $data['attendance']->id, 'parent_payment_status' => 'unpaid']);
    }

    public function test_admin_can_update_teacher_payment(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->post(route('admin.payments.guru.payment', $data['attendance']->id), ['teacher_payment_status' => 'unpaid'])
            ->assertRedirect();
        $this->assertDatabaseHas('enrollment_attendances', ['id' => $data['attendance']->id, 'teacher_payment_status' => 'unpaid']);
    }

    public function test_admin_can_access_discounts(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.discounts.index'))->assertStatus(200);
    }

    public function test_admin_can_store_discounts(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->post(route('admin.discounts.store'), [
            'month' => now()->month, 'year' => now()->year,
            'enrollment_ids' => [$data['enrollment']->id], 'discount_type' => 'percent', 'discount_value' => 10,
        ])->assertRedirect();
        $this->assertDatabaseHas('enrollment_student_discounts', ['enrollment_id' => $data['enrollment']->id, 'discount_type' => 'percent', 'discount_value' => 10]);
    }

    public function test_admin_can_access_finance(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.finance.index'))->assertStatus(200);
    }

    public function test_admin_can_access_class_reports(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.class-reports.index'))->assertStatus(200);
    }

    public function test_admin_can_access_history_students(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.history.students'))->assertStatus(200);
    }

    public function test_admin_can_access_history_teachers(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.history.teachers'))->assertStatus(200);
    }

    public function test_admin_can_access_history_payments(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.history.payments'))->assertStatus(200);
    }

    public function test_admin_can_access_history_audit(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.history.audit'))->assertStatus(200);
    }

    public function test_admin_can_access_export_index(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.export.index'))->assertStatus(200);
    }

    public function test_admin_can_access_export_students(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.export.students'))->assertStatus(200);
    }

    public function test_admin_can_access_export_teachers(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.export.teachers'))->assertStatus(200);
    }

    public function test_admin_can_access_export_lessons(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.export.lessons'))->assertStatus(200);
    }

    public function test_admin_can_access_export_attendances(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.export.attendances'))->assertStatus(200);
    }

    public function test_admin_can_access_export_audit(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->get(route('admin.export.audit'))->assertStatus(200);
    }

    public function test_admin_can_update_enrollment_discount(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->post(route('admin.analysis.ortu-discount'), [
            'month' => now()->month, 'year' => now()->year,
            'enrollment_id' => $data['enrollment']->id, 'student_id' => $data['student']->id,
            'discount_type' => 'percent', 'discount_value' => 15,
        ])->assertRedirect();
        $this->assertDatabaseHas('enrollment_student_discounts', ['enrollment_id' => $data['enrollment']->id, 'student_id' => $data['student']->id, 'discount_type' => 'percent', 'discount_value' => 15]);
    }

    public function test_admin_can_snapshot_students(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->post(route('admin.finance.snapshot.students'), ['month' => now()->month, 'year' => now()->year])
            ->assertRedirect();
        $this->assertDatabaseHas('monthly_student_snapshots', ['month' => now()->month, 'year' => now()->year]);
    }

    public function test_admin_can_snapshot_teachers(): void
    {
        $data = $this->seedBasicData();
        $this->actingAs($data['admin'])->post(route('admin.finance.snapshot.teachers'), ['month' => now()->month, 'year' => now()->year])
            ->assertRedirect();
        $this->assertDatabaseHas('monthly_teacher_snapshots', ['month' => now()->month, 'year' => now()->year]);
    }

    public function test_admin_can_access_profile(): void
    {
        $this->actingAs($this->createAdmin())->get(route('profile.edit'))->assertStatus(200);
    }

    public function test_admin_can_update_profile(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin)->patch(route('profile.update'), ['name' => 'Admin Updated', 'email' => $admin->email])
            ->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('users', ['name' => 'Admin Updated']);
    }

    public function test_user_with_must_change_password_is_redirected(): void
    {
        $user = $this->createUserWithRole(UserRole::Admin, true);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.force.edit'));
    }

    public function test_user_can_force_change_password(): void
    {
        $user = $this->createUserWithRole(UserRole::Admin, true);
        $this->actingAs($user)->put(route('password.force.update'), [
            'current_password' => 'password', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('dashboard'));
        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_admin_can_hibernate_lesson_offer(): void
    {
        $data = $this->seedBasicData();
        $offer = LessonOffer::first();
        $this->actingAs($data['admin'])->delete(route('admin.lesson-offers.destroy', $offer->id))
            ->assertRedirect(route('admin.lesson-offers.index'));
        $this->assertSoftDeleted($offer);
    }

    public function test_admin_can_restore_lesson_offer(): void
    {
        $data = $this->seedBasicData();
        $offer = LessonOffer::first();
        $offer->delete();
        $this->actingAs($data['admin'])->post(route('admin.lesson-offers.restore', $offer->id))
            ->assertRedirect(route('admin.lesson-offers.index'));
        $this->assertDatabaseHas('lesson_offers', ['id' => $offer->id, 'status' => 'open']);
    }
}