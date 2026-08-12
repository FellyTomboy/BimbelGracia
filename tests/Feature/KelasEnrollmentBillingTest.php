<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelasEnrollmentBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_package_billing_uses_flat_package_rate_and_session_teacher(): void
    {
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);

        $program = Program::factory()->create(['name' => 'Kelas SD', 'type' => 'kelas']);
        $teacherA = Teacher::factory()->create(['name' => 'Guru A']);
        $teacherB = Teacher::factory()->create(['name' => 'Guru B']);

        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => null,
            'type' => 'kelas',
            'parent_rate' => 250000,
            'teacher_rate' => 60000,
            'agreed_sessions_per_month' => 12,
        ]);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => 8,
            'year' => 2026,
            'status_validation' => 'terima',
            'parent_rate' => 250000,
            'teacher_rate' => 60000,
            'session_teacher_id' => $teacherA->id,
        ]);

        $attendance->students()->attach($student->id, ['total_present' => 2]);

        $service = app(CalculationService::class);
        $result = $service->calculateStudentBilling($student, 8, 2026, collect([$attendance]));

        $this->assertSame('kelas', $enrollment->fresh()->type);
        $this->assertCount(1, $result['rows']);
        $this->assertSame(125000, $result['rows'][0]['rate']);
        $this->assertSame(125000, $result['rows'][0]['total']);

        $teacherResult = $service->calculateTeacherSalary($teacherA->id, 8, 2026, collect([$attendance]));
        $this->assertSame(1, $teacherResult['rows']->count());
        $this->assertSame(60000, $teacherResult['rows'][0]['rate']);
        $this->assertSame(2 * 60000, $teacherResult['rows'][0]['total']);
    }
}
