<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldFiles extends Command
{
    protected $signature = 'cleanup:old-files';

    protected $description = 'Delete old files (payment proofs, lesson photos, invoices, salary slips) older than 6 months';

    public function handle(): int
    {
        $this->info('Starting cleanup of files older than 6 months...');

        $cutoff = now()->subMonths(6);

        $directories = [
            'payment-proofs',
            'lesson-photos',
            'invoice',
            'salary',
            'presensi',
            'photo',
            'pdf',
        ];

        $totalDeleted = 0;

        foreach ($directories as $directory) {
            $count = $this->cleanupDirectory($directory, $cutoff);
            $totalDeleted += $count;
            $this->line("  Cleaned {$count} files from '{$directory}'.");
        }

        $this->newLine();
        $this->info("✓ Cleanup completed! Total files deleted: {$totalDeleted}");

        return Command::SUCCESS;
    }

    private function cleanupDirectory(string $directory, \Carbon\Carbon $cutoff): int
    {
        $disk = Storage::disk('public');
        $count = 0;

        if (!$disk->exists($directory)) {
            return 0;
        }

        $allFiles = $disk->allFiles($directory);

        foreach ($allFiles as $file) {
            $lastModified = $disk->lastModified($file);

            if ($lastModified && \Carbon\Carbon::createFromTimestamp($lastModified)->lt($cutoff)) {
                $disk->delete($file);
                $count++;
            }
        }

        // Remove empty subdirectories
        $this->removeEmptyDirectories($disk, $directory);

        return $count;
    }

    private function removeEmptyDirectories($disk, string $directory): void
    {
        $directories = $disk->allDirectories($directory);

        // Sort by depth (deepest first)
        $directories = array_reverse($directories);

        foreach ($directories as $dir) {
            if (empty($disk->files($dir)) && empty($disk->directories($dir))) {
                $disk->deleteDirectory($dir);
            }
        }
    }
}