<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\MonthlySnapshotSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SnapshotMonthlyCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    public function test_students_command_defaults_to_current_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 10, 0, 0));

        $service = Mockery::mock(MonthlySnapshotSyncService::class);
        $service->shouldReceive('syncStudentSnapshotsForPeriod')->once()->with(7, 2026);
        $service->shouldReceive('syncClassStudentsForPeriod')->once()->with(7, 2026);

        $this->app->instance(MonthlySnapshotSyncService::class, $service);

        $this->artisan('snapshot:students-monthly')
                ->expectsOutput('Snapshot murid berhasil disimpan untuk 07/2026.')
            ->assertExitCode(0);
    }

    public function test_teachers_command_accepts_explicit_period(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 10, 0, 0));

        $service = Mockery::mock(MonthlySnapshotSyncService::class);
        $service->shouldReceive('syncTeacherSnapshotsForPeriod')->once()->with(4, 2025);

        $this->app->instance(MonthlySnapshotSyncService::class, $service);

        $this->artisan('snapshot:teachers-monthly', [
            '--month' => 4,
            '--year' => 2025,
        ])
            ->expectsOutput('Snapshot guru berhasil disimpan untuk 04/2025.')
            ->assertExitCode(0);
    }
}