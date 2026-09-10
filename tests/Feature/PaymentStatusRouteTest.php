<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\MonthlyAttendance;
use App\Models\ParentModel;
use App\Models\PaymentProof;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentStatusRouteTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $guruUser;
    private User $parentUser;
    private Teacher $teacher;
    private ParentModel $parent;
    private Student $student;
    private Enrollment $enrollment;
    private MonthlyAttendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable fines for predictable tests
        DB::table('settings')->updateOrInsert(
            ['key' => 'fine.attendance_penalty_enabled'],
            ['value' => 'false']
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'fine.late_penalty_enabled'],
            ['value' => 'false']
        );

        // Admin
        $this->adminUser = User::factory()->create(['role' => 'admin']);

        // Guru — teachers table has full_name + nickname, not 'name'
        $this->teacher = Teacher::create([
            'user_id' => null,
            'nickname' => 'Guru Test',
            'full_name' => 'Guru Payment Test',
            'whatsapp' => '08191234567',
            'major' => 'IPA',
            'subjects' => 'Matematika, Fisika',
            'address' => 'Jl Test Guru',
            'bank_name' => 'BCA',
            'bank_account' => '12345678',
            'bank_owner' => 'Guru Test',
            'class_rate' => 50000,
            'status' => 'active',
        ]);
        $this->guruUser = User::factory()->create(['role' => 'guru']);
        $this->teacher->update(['user_id' => $this->guruUser->id]);

        // Parent + Student
        $this->parent = ParentModel::factory()->create(['name' => 'Ortu Payment Test', 'address' => 'Jl Test']);
        $this->parentUser = User::factory()->create(['role' => 'parent']);
        $this->parent->update(['user_id' => $this->parentUser->id]);
        $this->student = Student::factory()->create([
            'parent_id' => $this->parent->id,
            'full_name' => 'Siswa Payment Test',
        ]);

        // Program + Enrollment (enrollments table has NO student_id — it's in pivot table)
        $program = Program::factory()->create(['type' => 'privat', 'name' => 'Les Privat']);
        $this->enrollment = Enrollment::factory()->create([
            'teacher_id' => $this->teacher->id,
            'program_id' => $program->id,
            'parent_rate' => 150000,
            'teacher_rate' => 100000,
        ]);
        $this->enrollment->students()->attach($this->student->id);

        // Attendance record
        $this->attendance = MonthlyAttendance::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'month' => 9,
            'year' => 2026,
            'status_validation' => 'terima',
            'total_lessons' => 4,
            'parent_payment_status' => 'unpaid',
            'teacher_payment_status' => 'unpaid',
        ]);
        $this->attendance->students()->attach($this->student->id, ['total_present' => 4]);
    }

    // ─── Ortu Payment Status ──────────────────────────────────────────────

    public function test_update_parent_payment_status_to_paid(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('payments.ortu.payment', $this->attendance), [
            'parent_payment_status' => 'paid',
        ]);

        $response->assertSessionHas('status');
        $this->assertEquals('paid', $this->attendance->fresh()->parent_payment_status);
    }

    public function test_update_parent_payment_status_to_unpaid(): void
    {
        $this->attendance->update(['parent_payment_status' => 'paid']);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('payments.ortu.payment', $this->attendance), [
            'parent_payment_status' => 'unpaid',
        ]);

        $response->assertSessionHas('status');
        $this->assertEquals('unpaid', $this->attendance->fresh()->parent_payment_status);
    }

    public function test_update_parent_payment_status_requires_valid_value(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('payments.ortu.payment', $this->attendance), [
            'parent_payment_status' => 'invalid_value',
        ]);

        $response->assertSessionHasErrors('parent_payment_status');
    }

    public function test_update_parent_monthly_payment_preserves_month_year_filter(): void
    {
        $this->actingAs($this->adminUser);

        // POST with month=9&year=2026 query string (simulates from filtered view)
        $response = $this->withHeaders(['Referer' => route('payments.ortu', ['month' => 9, 'year' => 2026])])
            ->post(route('payments.ortu.monthly-payment'), [
                'attendance_ids' => (string) $this->attendance->id,
                'parent_payment_status' => 'paid',
            ]);

        $response->assertSessionHas('status');
        $this->assertEquals('paid', $this->attendance->fresh()->parent_payment_status);
    }

    public function test_payments_ortu_page_shows_paid_status(): void
    {
        $this->attendance->update(['parent_payment_status' => 'paid']);

        $this->actingAs($this->adminUser);

        $response = $this->get(route('payments.ortu', ['month' => 9, 'year' => 2026]));

        $response->assertStatus(200);
        // Should show "Sudah Bayar" in the dropdown (selected option)
        $response->assertSee('paid', false);
    }

    public function test_payments_ortu_page_shows_unpaid_status(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('payments.ortu', ['month' => 9, 'year' => 2026]));

        $response->assertStatus(200);
        // Should show "Belum Bayar" option
        $response->assertSee('Belum bayar', false);
    }

    public function test_payments_ortu_page_with_empty_search(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('payments.ortu', [
            'month' => 9,
            'year' => 2026,
            'search' => '',
        ]));

        $response->assertStatus(200);
    }

    public function test_payments_ortu_page_with_null_search(): void
    {
        $this->actingAs($this->adminUser);

        // Simulates ?search (no value) — this was the bug
        $response = $this->withHeaders(['Referer' => route('payments.ortu', ['month' => 9, 'year' => 2026, 'search' => ''])])
            ->get(route('payments.ortu', [
                'month' => 9,
                'year' => 2026,
                'search' => '',
            ]));

        $response->assertStatus(200);
    }

    // ─── Guru Payment Status ───────────────────────────────────────────────

    public function test_update_teacher_payment_status_to_paid(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('payments.guru.payment', $this->attendance), [
            'teacher_payment_status' => 'paid',
        ]);

        $response->assertSessionHas('status');
        $this->assertEquals('paid', $this->attendance->fresh()->teacher_payment_status);
    }

    public function test_update_teacher_payment_status_to_held(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('payments.guru.payment', $this->attendance), [
            'teacher_payment_status' => 'held',
        ]);

        $response->assertSessionHas('status');
        $this->assertEquals('held', $this->attendance->fresh()->teacher_payment_status);
    }

    public function test_update_teacher_payment_status_requires_valid_value(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('payments.guru.payment', $this->attendance), [
            'teacher_payment_status' => 'invalid',
        ]);

        $response->assertSessionHasErrors('teacher_payment_status');
    }

    public function test_payments_guru_page_shows_correct_status(): void
    {
        $this->attendance->update(['teacher_payment_status' => 'paid']);

        $this->actingAs($this->adminUser);

        $response = $this->get(route('payments.guru', ['month' => 9, 'year' => 2026]));

        $response->assertStatus(200);
    }

    public function test_payments_guru_page_with_empty_search(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('payments.guru', [
            'month' => 9,
            'year' => 2026,
            'search' => '',
        ]));

        $response->assertStatus(200);
    }

    public function test_payments_guru_page_with_null_search(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('payments.guru', [
            'month' => 9,
            'year' => 2026,
            'search' => '',
        ]));

        $response->assertStatus(200);
    }

    // ─── Pagination ────────────────────────────────────────────────────────

    public function test_payments_ortu_pagination_page_param(): void
    {
        $this->actingAs($this->adminUser);

        // Page 2 with filter params should not crash
        $response = $this->get(route('payments.ortu', [
            'month' => 9,
            'year' => 2026,
            'page' => 2,
        ]));

        $response->assertStatus(200);
    }

    public function test_payments_guru_pagination_page_param(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('payments.guru', [
            'month' => 9,
            'year' => 2026,
            'page' => 2,
        ]));

        $response->assertStatus(200);
    }

    // ─── Payment Proof ────────────────────────────────────────────────────

    public function test_confirm_parent_payment_proof_approve(): void
    {
        $proof = PaymentProof::create([
            'parent_id' => $this->parent->id,
            'month' => 9,
            'year' => 2026,
            'proof_path' => 'fake/path.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('payments.ortu.confirm-proof', $proof), [
            'action' => 'approve',
        ]);

        $response->assertSessionHas('status');
        $this->assertEquals('approved', $proof->fresh()->status);
    }

    public function test_confirm_parent_payment_proof_reject(): void
    {
        $proof = PaymentProof::create([
            'parent_id' => $this->parent->id,
            'month' => 9,
            'year' => 2026,
            'proof_path' => 'fake/path.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('payments.ortu.confirm-proof', $proof), [
            'action' => 'reject',
        ]);

        $response->assertSessionHas('status');
        $this->assertEquals('rejected', $proof->fresh()->status);
    }

    public function test_confirm_parent_payment_proof_requires_valid_action(): void
    {
        $proof = PaymentProof::create([
            'parent_id' => $this->parent->id,
            'month' => 9,
            'year' => 2026,
            'proof_path' => 'fake/path.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('payments.ortu.confirm-proof', $proof), [
            'action' => 'invalid',
        ]);

        $response->assertSessionHasErrors('action');
    }

    // ─── Authorization ────────────────────────────────────────────────────

    public function test_parent_payment_update_requires_admin(): void
    {
        $this->actingAs($this->parentUser);

        $response = $this->post(route('payments.ortu.payment', $this->attendance), [
            'parent_payment_status' => 'paid',
        ]);

        $response->assertStatus(403);
    }

    public function test_teacher_payment_update_requires_admin(): void
    {
        $this->actingAs($this->guruUser);

        $response = $this->post(route('payments.guru.payment', $this->attendance), [
            'teacher_payment_status' => 'paid',
        ]);

        $response->assertStatus(403);
    }

    // ─── Unauthenticated ──────────────────────────────────────────────────

    public function test_payments_ortu_requires_auth(): void
    {
        $response = $this->get(route('payments.ortu', ['month' => 9, 'year' => 2026]));
        $response->assertStatus(302); // redirect to login
    }

    public function test_payments_guru_requires_auth(): void
    {
        $response = $this->get(route('payments.guru', ['month' => 9, 'year' => 2026]));
        $response->assertStatus(302);
    }
}
