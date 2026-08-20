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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentRouteTest extends TestCase
{
    use RefreshDatabase;

    private User $parentUser;
    private ParentModel $parent;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parent = ParentModel::factory()->create(['name' => 'Parent Test', 'address' => 'Alamat']);
        $this->parentUser = User::factory()->create(['name' => 'Parent User Test', 'role' => 'parent']);
        $this->parent->update(['user_id' => $this->parentUser->id]);
        $this->student = Student::factory()->create([
            'parent_id' => $this->parent->id,
            'full_name' => 'Siswa Test',
        ]);
    }

    private function createTeacher(): Teacher
    {
        return Teacher::factory()
            ->state(new Sequence(fn () => ['whatsapp' => '0819' . fake()->unique()->numerify('######')]))
            ->create();
    }

    public function test_parent_dashboard_loads(): void
    {
        $response = $this->actingAs($this->parentUser)->get('/parent');
        $response->assertStatus(200);
    }

    public function test_parent_billing_index_loads(): void
    {
        $response = $this->actingAs($this->parentUser)->get('/parent/tagihan');
        $response->assertStatus(200);
    }

    public function test_parent_billing_index_with_attendances_loads(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($this->student->id);

        MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_payment_status' => 'unpaid',
        ])->students()->attach($this->student->id, ['total_present' => 1]);

        $response = $this->actingAs($this->parentUser)->get('/parent/tagihan');
        $response->assertStatus(200);
    }

    public function test_parent_riwayat_loads(): void
    {
        $response = $this->actingAs($this->parentUser)->get('/parent/riwayat');
        $response->assertStatus(200);
    }

    public function test_parent_complete_data_loads(): void
    {
        $response = $this->actingAs($this->parentUser)->get('/parent/complete-data');
        $response->assertStatus(200);
    }

    public function test_parent_can_submit_complete_data(): void
    {
        $response = $this->actingAs($this->parentUser)->post('/parent/complete-data', [
            'name' => 'Nama Orang Tua Lengkap',
            'address' => 'Alamat Lengkap',
            'students' => [
                [
                    'id' => $this->student->id,
                    'full_name' => 'Nama Siswa Lengkap',
                ],
            ],
        ]);

        $response->assertRedirect();
        // Verify via direct DB query (avoids model table naming issues in tests)
        $updatedParent = DB::table('parents')->where('id', $this->parent->id)->first();
        $this->assertEquals('Nama Orang Tua Lengkap', $updatedParent->name);

        $updatedStudent = DB::table('students')->where('id', $this->student->id)->first();
        $this->assertEquals('Nama Siswa Lengkap', $updatedStudent->full_name);
    }

    public function test_parent_complete_data_requires_valid_student(): void
    {
        $response = $this->actingAs($this->parentUser)->post('/parent/complete-data', [
            'name' => 'Test',
            'students' => [
                ['id' => 9999, 'full_name' => 'Invalid'],
            ],
        ]);

        $response->assertSessionHasErrors('students.0.id');
    }

    public function test_parent_can_upload_payment_proof(): void
    {
        Storage::fake('public');

        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($this->student->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'month' => now()->month,
            'year' => now()->year,
            'status_validation' => 'terima',
            'parent_payment_status' => 'unpaid',
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        $proof = UploadedFile::fake()->image('transfer.jpg', 800, 600);

        $response = $this->actingAs($this->parentUser)->post("/parent/tagihan/{$attendance->id}/upload", [
            'payment_proof' => $proof,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    public function test_parent_cannot_upload_proof_for_unrelated_attendance(): void
    {
        Storage::fake('public');

        // Create a different parent's student
        $otherParent = ParentModel::factory()->create();
        $otherStudent = Student::factory()->create(['parent_id' => $otherParent->id]);
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($otherStudent->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
        ]);
        $attendance->students()->attach($otherStudent->id, ['total_present' => 1]);

        $proof = UploadedFile::fake()->image('transfer.jpg', 800, 600);

        // Our parent user tries to upload proof for the other parent's attendance
        $response = $this->actingAs($this->parentUser)->post("/parent/tagihan/{$attendance->id}/upload", [
            'payment_proof' => $proof,
        ]);

        $response->assertStatus(403);
    }

    public function test_parent_download_invoice_with_no_children_returns_404(): void
    {
        // Create a fresh parent with no children
        $orphanParent = ParentModel::factory()->create(['name' => 'Orphan', 'address' => 'Alamat']);
        $orphanUser = User::factory()->create(['role' => 'parent']);
        $orphanParent->update(['user_id' => $orphanUser->id]);

        // Route is POST /parent/tagihan/invoice/{year}/{month}
        $response = $this->actingAs($orphanUser)->post('/parent/tagihan/invoice/' . now()->year . '/' . now()->month);
        $response->assertStatus(404);
    }

    public function test_parent_cannot_access_guru_routes(): void
    {
        $response = $this->actingAs($this->parentUser)->get('/guru');
        $response->assertStatus(403);
    }

    public function test_parent_cannot_access_admin_routes(): void
    {
        $response = $this->actingAs($this->parentUser)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_parent_upload_proof_requires_valid_image(): void
    {
        $teacher = $this->createTeacher();
        $program = Program::factory()->create(['type' => 'privat']);
        $enrollment = Enrollment::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
        ]);
        $enrollment->students()->attach($this->student->id);

        $attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $enrollment->id,
        ]);
        $attendance->students()->attach($this->student->id, ['total_present' => 1]);

        // Send a non-image file
        $fakeFile = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->actingAs($this->parentUser)->post("/parent/tagihan/{$attendance->id}/upload", [
            'payment_proof' => $fakeFile,
        ]);

        $response->assertSessionHasErrors('payment_proof');
    }
}
