<?php

namespace Tests\Feature;

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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuruRouteTest extends TestCase
{
    use RefreshDatabase;

    private User $guruUser;
    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        // Use sequence to avoid whatsapp uniqueness conflicts across tests
        $this->teacher = Teacher::factory()
            ->state(new Sequence(fn () => ['whatsapp' => '0819' . fake()->unique()->numerify('######')]))
            ->create();
        $this->guruUser = User::factory()->create(['role' => 'guru', 'name' => 'Guru Test']);
        $this->teacher->update(['user_id' => $this->guruUser->id]);
    }

    private function createPrivatEnrollment(Program $program, array $overrides = []): Enrollment
    {
        $teacher = $overrides['teacher'] ?? $this->teacher;
        $enrollment = Enrollment::factory()->create(array_merge([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'type' => 'privat',
            'status' => 'active',
            'validation_status' => 0,
        ], $overrides));
        return $enrollment;
    }

    private function createKelasEnrollment(Program $program): Enrollment
    {
        return Enrollment::factory()->create(array_merge([
            'program_id' => $program->id,
            'type' => 'kelas',
            'status' => 'active',
            'validation_status' => 0,
        ]));
    }

    public function test_guru_dashboard_loads(): void
    {
        $response = $this->actingAs($this->guruUser)->get('/guru');
        $response->assertStatus(200);
    }

    public function test_guru_presensi_index_loads(): void
    {
        $response = $this->actingAs($this->guruUser)->get('/guru/presensi');
        $response->assertStatus(200);
    }

    public function test_guru_presensi_create_loads(): void
    {
        $program = Program::factory()->create(['type' => 'privat']);
        $enrollment = $this->createPrivatEnrollment($program);

        $response = $this->actingAs($this->guruUser)->get('/guru/presensi/create');
        $response->assertStatus(200);
    }

    public function test_guru_can_store_presensi_privat(): void
    {
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = $this->createPrivatEnrollment($program);
        $enrollment->students()->attach($student->id);

        $response = $this->actingAs($this->guruUser)->post('/guru/presensi', [
            'enrollment_id' => $enrollment->id,
            'lesson_date' => now()->subDays(1)->format('Y-m-d'),
            'student_ids' => [$student->id],
            'notes' => 'Test lesson',
        ]);

        $response->assertRedirect('/guru/presensi');
        $this->assertDatabaseHas('enrollment_attendances', [
            'enrollment_id' => $enrollment->id,
        ]);
    }

    public function test_guru_can_store_presensi_kelas(): void
    {
        $program = Program::factory()->create(['type' => 'kelas']);
        $enrollment = $this->createKelasEnrollment($program);

        $response = $this->actingAs($this->guruUser)->post('/guru/presensi', [
            'enrollment_id' => $enrollment->id,
            'lesson_date' => now()->subDays(1)->format('Y-m-d'),
            'notes' => 'Kelas test',
        ]);

        $response->assertRedirect('/guru/presensi');
        $this->assertDatabaseHas('enrollment_attendances', [
            'enrollment_id' => $enrollment->id,
            'session_teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_guru_presensi_requires_valid_enrollment(): void
    {
        $response = $this->actingAs($this->guruUser)->post('/guru/presensi', [
            'enrollment_id' => 9999,
            'lesson_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('enrollment_id');
    }

    public function test_guru_riwayat_loads(): void
    {
        $response = $this->actingAs($this->guruUser)->get('/guru/riwayat');
        $response->assertStatus(200);
    }

    public function test_guru_proyeksi_gaji_loads(): void
    {
        $response = $this->actingAs($this->guruUser)->get('/guru/proyeksi-gaji');
        $response->assertStatus(200);
    }

    public function test_guru_proyaksi_gaji_with_attendance_loads(): void
    {
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = $this->createPrivatEnrollment($program);
        $enrollment->students()->attach($student->id);

        MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_payment_status' => 'paid',
            'teacher_payment_status' => 'paid',
            'teacher_rate' => 50000,
        ])->students()->attach($student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->guruUser)->get(
            '/guru/proyeksi-gaji?month=' . now()->month . '&year=' . now()->year
        );
        $response->assertStatus(200);
    }

    public function test_guru_tawaran_index_loads(): void
    {
        $response = $this->actingAs($this->guruUser)->get('/guru/tawaran');
        $response->assertStatus(200);
    }

    public function test_guru_documents_index_loads(): void
    {
        $response = $this->actingAs($this->guruUser)->get('/guru/documents');
        $response->assertStatus(200);
    }

    public function test_non_guru_cannot_access_guru_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/guru');
        $response->assertStatus(403);
    }

    public function test_guru_presensi_edit_loads_for_terlambat(): void
    {
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = $this->createPrivatEnrollment($program);
        $enrollment->students()->attach($student->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'status_validation' => 'terlambat',
            'teacher_rate' => 50000,
        ]);
        $attendance->students()->attach($student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->guruUser)->get("/guru/presensi/{$attendance->id}/edit");
        $response->assertStatus(200);
    }

    public function test_guru_cannot_edit_accepted_presensi(): void
    {
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = $this->createPrivatEnrollment($program);
        $enrollment->students()->attach($student->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'status_validation' => 'terima',
            'teacher_rate' => 50000,
        ]);
        $attendance->students()->attach($student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->guruUser)->get("/guru/presensi/{$attendance->id}/edit");
        $response->assertStatus(403);
    }

    public function test_guru_can_update_presensi_terlambat(): void
    {
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = $this->createPrivatEnrollment($program);
        $enrollment->students()->attach($student->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'status_validation' => 'terlambat',
            'lesson_date' => now()->subDays(5),
            'teacher_rate' => 50000,
        ]);
        $attendance->students()->attach($student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->guruUser)->put("/guru/presensi/{$attendance->id}", [
            'lesson_date' => now()->subDays(2)->format('Y-m-d'),
            'notes' => 'Updated notes',
        ]);

        $response->assertRedirect('/guru/presensi');
    }

    public function test_guru_can_upload_image_with_presensi(): void
    {
        Storage::fake('public');

        $program = Program::factory()->create(['type' => 'kelas']);
        $enrollment = $this->createKelasEnrollment($program);

        $image = UploadedFile::fake()->image('attendance.jpg', 640, 480);

        $response = $this->actingAs($this->guruUser)->post('/guru/presensi', [
            'enrollment_id' => $enrollment->id,
            'lesson_date' => now()->subDays(1)->format('Y-m-d'),
            'notes' => 'With photo',
            'image' => $image,
        ]);

        $response->assertRedirect('/guru/presensi');
        $this->assertDatabaseHas('enrollment_attendances', ['enrollment_id' => $enrollment->id]);
    }

    public function test_guru_presensi_requires_lesson_date(): void
    {
        $program = Program::factory()->create(['type' => 'privat']);
        $parent = ParentModel::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);
        $enrollment = $this->createPrivatEnrollment($program);
        $enrollment->students()->attach($student->id);

        $response = $this->actingAs($this->guruUser)->post('/guru/presensi', [
            'enrollment_id' => $enrollment->id,
            'student_ids' => [$student->id],
        ]);

        $response->assertSessionHasErrors('lesson_date');
    }

    public function test_guru_presensi_privat_requires_student_selection(): void
    {
        $program = Program::factory()->create(['type' => 'privat']);
        $enrollment = $this->createPrivatEnrollment($program);

        $response = $this->actingAs($this->guruUser)->post('/guru/presensi', [
            'enrollment_id' => $enrollment->id,
            'lesson_date' => now()->subDays(1)->format('Y-m-d'),
            'student_ids' => [],
        ]);

        $response->assertSessionHasErrors('student_ids');
    }

    public function test_guru_presensi_lesson_date_not_in_future(): void
    {
        $program = Program::factory()->create(['type' => 'kelas']);
        $enrollment = $this->createKelasEnrollment($program);

        $response = $this->actingAs($this->guruUser)->post('/guru/presensi', [
            'enrollment_id' => $enrollment->id,
            'lesson_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('lesson_date');
    }
}
