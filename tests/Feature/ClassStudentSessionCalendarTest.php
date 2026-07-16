<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ClassStudent;
use App\Models\ClassStudentSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassStudentSessionCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_one_session_with_multiple_students(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'role' => UserRole::Admin,
            'must_change_password' => false,
        ]);

        $this->actingAs($admin);

        $firstStudent = ClassStudent::create([
            'name' => 'Murid Aktif',
            'whatsapp_primary' => '081234567890',
            'whatsapp_secondary' => null,
            'rate_per_meeting' => 50000,
            'status' => 'active',
            'notes' => null,
        ]);

        $secondStudent = ClassStudent::create([
            'name' => 'Murid Hibernasi',
            'whatsapp_primary' => '081234567891',
            'whatsapp_secondary' => null,
            'rate_per_meeting' => 50000,
            'status' => 'hibernasi',
            'notes' => null,
        ]);

        $this->withoutMiddleware()->post(route('admin.class-student-sessions.store'), [
            'class_student_ids' => [$firstStudent->id, $secondStudent->id],
            'session_date' => '2026-07-10',
            'start_time' => '14:00',
            'end_time' => '15:30',
            'notes' => 'Latihan kelompok',
        ])
            ->assertRedirect(route('admin.class-student-sessions.index'));

        $this->assertDatabaseCount('class_student_sessions', 1);

        $session = ClassStudentSession::query()->with('students')->firstOrFail();

        $this->assertSame('2026-07-10', $session->session_date->format('Y-m-d')); 
        $this->assertSame('14:00', $session->start_time->format('H:i'));
        $this->assertSame('15:30', $session->end_time?->format('H:i'));
        $this->assertSame('Latihan kelompok', $session->notes);
        $this->assertCount(2, $session->students);
        $this->assertTrue($session->students->contains('name', 'Murid Aktif'));
        $this->assertTrue($session->students->contains('name', 'Murid Hibernasi'));
    }

    public function test_calendar_groups_session_block_and_shows_hibernation_badge(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'role' => UserRole::Admin,
            'must_change_password' => false,
        ]);

        $this->actingAs($admin);

        $activeStudent = ClassStudent::create([
            'name' => 'Murid Aktif',
            'whatsapp_primary' => '081234567892',
            'whatsapp_secondary' => null,
            'rate_per_meeting' => 50000,
            'status' => 'active',
            'notes' => null,
        ]);

        $hibernatedStudent = ClassStudent::create([
            'name' => 'Murid Hibernasi',
            'whatsapp_primary' => '081234567893',
            'whatsapp_secondary' => null,
            'rate_per_meeting' => 50000,
            'status' => 'hibernasi',
            'notes' => null,
        ]);

        $session = ClassStudentSession::create([
            'session_date' => '2026-07-10',
            'start_time' => '14:00',
            'end_time' => '15:30',
            'notes' => 'Latihan kelompok',
        ]);

        $session->students()->attach([$activeStudent->id, $hibernatedStudent->id]);

        $response = $this->withoutMiddleware()->get(route('admin.class-student-sessions.index', [
            'month' => 7,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSeeText('14:00 - 15:30', false);
        $response->assertSeeText('Murid Aktif', false);
        $response->assertSeeText('Murid Hibernasi', false);
        $response->assertSeeText('Hibernasi', false);
        $response->assertSeeText('Latihan kelompok', false);
    }
}