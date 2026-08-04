<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\ParentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassStudentSessionCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_shows_class_attendance_and_hibernation_badge(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'role' => UserRole::Admin,
            'must_change_password' => false,
        ]);

        $this->actingAs($admin);

        // Create a Kelas program
        $program = Program::create([
            'name' => 'Kelas Matematika',
            'type' => 'privat',
            'subject' => 'Matematika',
            'default_parent_rate' => 50000,
            'default_teacher_rate' => 30000,
            'status' => 'active',
        ]);

        $teacher = Teacher::factory()->create();

        $enrollment = Enrollment::create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'parent_rate' => 50000,
            'teacher_rate' => 30000,
            'validation_status' => 1,
            'status' => 'active',
        ]);

        $parent = ParentModel::factory()->create();
        $activeStudent = Student::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'Murid Aktif',
            'status' => 'active',
        ]);

        $hibernatedStudent = Student::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'Murid Hibernasi',
            'status' => 'hibernasi',
        ]);

        $attendance = MonthlyAttendance::create([
            'enrollment_id' => $enrollment->id,
            'lesson_date' => '2026-07-10',
            'month' => 7,
            'year' => 2026,
            'status_validation' => 'terima',
            'parent_rate' => 50000,
            'teacher_rate' => 30000,
            'created_by' => $admin->id,
        ]);

        $attendance->students()->attach([$activeStudent->id, $hibernatedStudent->id], ['total_present' => 1]);

        $response = $this->withoutMiddleware()->get(route('admin.class-student-sessions.index', [
            'month' => 7,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSeeText('Kelas Matematika', false);
        $response->assertSeeText('Murid Aktif', false);
        $response->assertSeeText('Murid Hibernasi', false);
        $response->assertSeeText('Hibernasi', false);
        $response->assertSeeText('Diterima', false);
    }
}