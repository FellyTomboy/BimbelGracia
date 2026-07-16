<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MonthlySnapshotSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SnapshotMonthlyTeachersCommand extends Command
{
    protected $signature = 'snapshot:teachers-monthly {--month=} {--year=}';

    protected $description = 'Snapshot monthly teacher counts';

    public function handle(MonthlySnapshotSyncService $snapshotSyncService): int
    {
        [$month, $year] = $this->resolveTargetPeriod();

        $snapshotSyncService->syncTeacherSnapshotsForPeriod($month, $year);

        $this->info(sprintf('Snapshot guru berhasil disimpan untuk %02d/%04d.', $month, $year));

        return self::SUCCESS;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function resolveTargetPeriod(): array
    {
        $defaultPeriod = Carbon::now()->startOfMonth();

        $month = (int) ($this->option('month') ?: $defaultPeriod->month);
        $year = (int) ($this->option('year') ?: $defaultPeriod->year);

        if ($month < 1 || $month > 12) {
            $this->error('Option --month harus berada di antara 1 dan 12.');
            return [$defaultPeriod->month, $defaultPeriod->year];
        }

        if ($year < 2020 || $year > 2100) {
            $this->error('Option --year berada di luar rentang yang diizinkan.');
            return [$defaultPeriod->month, $defaultPeriod->year];
        }

        return [$month, $year];
    }
}