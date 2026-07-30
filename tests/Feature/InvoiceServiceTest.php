<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Pdf\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_student_invoice_pdf(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => UserRole::Murid]);
        $student = Student::factory()->create(['user_id' => $user->id]);

        $teacher = Teacher::factory()->create();
        $program = Program::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'parent_rate' => 100000,
        ]);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => 7,
            'year' => 2026,
            'status_validation' => 'terima',
            'parent_rate' => 100000,
        ]);

        $attendance->students()->attach($student->id, ['total_present' => 4]);

        $service = app(InvoiceService::class);
        $filename = $service->generateStudentInvoice($student, 7, 2026, collect([$attendance]));

        $this->assertNotNull($filename);
        $this->assertTrue(Storage::disk('public')->exists($filename));
    }

    public function test_generates_teacher_salary_pdf(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();
        $program = Program::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'teacher_rate' => 50000,
        ]);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => 7,
            'year' => 2026,
            'status_validation' => 'terima',
            'teacher_rate' => 50000,
        ]);

        $service = app(InvoiceService::class);
        $filename = $service->generateTeacherSalarySlip($teacher, 7, 2026, collect([$attendance]));

        $this->assertNotNull($filename);
        $this->assertTrue(Storage::disk('public')->exists($filename));
    }
}