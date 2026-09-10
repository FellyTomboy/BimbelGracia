<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\StudentGrade;
use App\Models\Student;
use Illuminate\Console\Command;

class PromoteStudentGrades extends Command
{
    protected $signature = 'students:promote-grades {--dry-run : Preview changes without saving}';

    protected $description = 'Naikkan kelas semua murid satu tingkat (awal tahun ajaran baru, 1 Juli). Siswa kelas 12 diubah statusnya menjadi lulus.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $promoted = 0;
        $graduated = 0;
        $skipped = 0;

        Student::withTrashed()
            ->whereNotNull('kelas')
            ->orderBy('id')
            ->chunkById(100, function ($students) use ($dryRun, &$promoted, &$graduated, &$skipped): void {
                foreach ($students as $student) {
                    $next = StudentGrade::nextLevel($student->kelas);

                    if ($next === null) {
                        if ($student->kelas === '12') {
                            if (! $dryRun) {
                                $student->status = 'lulus';
                                $student->save();
                            }
                            $graduated++;
                            $this->line(sprintf(
                                '  ✓ %s (kelas 12) → status lulus',
                                $student->display_name,
                            ));
                        } else {
                            $skipped++;
                        }
                        continue;
                    }

                    $previous = $student->kelas;
                    if (! $dryRun) {
                        $student->kelas = $next;
                        $student->save();
                    }
                    $promoted++;
                    $this->line(sprintf(
                        '  ↑ %s: %s → %s',
                        $student->display_name,
                        $previous,
                        $next,
                    ));
                }
            });

        $this->newLine();
        $this->info(sprintf(
            'Ringkasan: Dipromote %d | Lulus %d | Dilewati %d%s',
            $promoted,
            $graduated,
            $skipped,
            $dryRun ? ' (DRY RUN)' : '',
        ));

        return self::SUCCESS;
    }
}
