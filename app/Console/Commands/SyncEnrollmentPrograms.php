<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\JenjangMatcher;
use App\Models\Enrollment;
use Illuminate\Console\Command;

class SyncEnrollmentPrograms extends Command
{
    protected $signature = 'enrollments:sync-programs {--dry-run : Preview changes without saving}';

    protected $description = 'Sinkronkan program_id enrollment dengan jenjang kelas siswa (TK/SD/SMP/SMA). Hanya mengubah program_id, TIDAK menyentuh pricing.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $updated = 0;
        $mismatched = 0;
        $skipped = 0;

        Enrollment::with(['program', 'students'])
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(100, function ($enrollments) use ($dryRun, &$updated, &$mismatched, &$skipped): void {
                foreach ($enrollments as $enrollment) {
                    $current = $enrollment->program;

                    if (! $current) {
                        $skipped++;
                        continue;
                    }

                    if (JenjangMatcher::isSpecialProgram($current)) {
                        $skipped++;
                        continue;
                    }

                    $target = JenjangMatcher::findTargetProgram($enrollment);

                    if ($target === null) {
                        $mismatched++;
                        $this->line(sprintf(
                            '  ⚠ enrollment#%d (program: %s, siswa: %d) → mismatch, butuh review',
                            $enrollment->id,
                            $current->name,
                            $enrollment->students->count(),
                        ));
                        continue;
                    }

                    if ($target->id === $current->id) {
                        continue;
                    }

                    if (! $dryRun) {
                        $enrollment->program_id = $target->id;
                        $enrollment->save();
                    }
                    $updated++;
                    $this->line(sprintf(
                        '  ✓ enrollment#%d: %s → %s',
                        $enrollment->id,
                        $current->name,
                        $target->name,
                    ));
                }
            });

        $this->newLine();
        $this->info(sprintf(
            'Ringkasan: Updated %d | Mismatch %d | Skipped %d%s',
            $updated,
            $mismatched,
            $skipped,
            $dryRun ? ' (DRY RUN)' : '',
        ));

        return self::SUCCESS;
    }
}
