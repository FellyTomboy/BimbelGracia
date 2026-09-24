<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ImageCompressionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CompressAttendancePhotos extends Command
{
    protected $signature = 'photos:compress
                            {--directory=photo : Relative path under public disk (photo/attendance, photo/transfer-proof, photo)}
                            {--quality=75 : JPEG quality (0-100)}
                            {--max-width=1920 : Max image width in pixels}
                            {--dry-run : Preview without actually compressing}';

    protected $description = 'Compress attendance and transfer-proof photos to reduce file size';

    public function handle(ImageCompressionService $service): int
    {
        $directory = $this->option('directory');
        $quality = (int) $this->option('quality');
        $maxWidth = (int) $this->option('max-width');
        $dryRun = $this->option('dry-run');

        $this->info("Compressing photos in '{$directory}/'...");
        $this->info("  Max width : {$maxWidth}px");
        $this->info("  Quality   : {$quality}");
        $this->info("  Dry run   : " . ($dryRun ? 'YES (no files will be changed)' : 'NO'));
        $this->newLine();

        if ($dryRun) {
            return $this->dryRun($directory);
        }

        $disk = Storage::disk('public');
        $diskRoot = config('filesystems.disks.public.root');
        $fullPath = $diskRoot . '/' . $directory;

        if (! is_dir($fullPath)) {
            $this->error("Directory not found: {$fullPath}");
            return Command::FAILURE;
        }

        $extensions = ['jpg', 'jpeg', 'png'];
        $totalBefore = 0;
        $totalAfter = 0;
        $processed = 0;
        $errors = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, $extensions, true)) {
                continue;
            }
            $files[] = $file->getRealPath();
            $totalBefore += $file->getSize();
        }

        if (empty($files)) {
            $this->warn('No image files found.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $path) {
            try {
                $result = $service->compressFile($path, $maxWidth, $quality);
                $totalAfter += $result['after'];
                $processed++;
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn("  Error on {$path}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $saved = $totalBefore - $totalAfter;
        $pct = $totalBefore > 0 ? round(($saved / $totalBefore) * 100, 1) : 0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Files processed', $processed],
                ['Files with errors', $errors],
                ['Size before', $this->formatBytes($totalBefore)],
                ['Size after', $this->formatBytes($totalAfter)],
                ['Space saved', $this->formatBytes($saved) . " ({$pct}%)"],
            ]
        );

        $this->info('Done.');

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function dryRun(string $directory): int
    {
        $diskRoot = config('filesystems.disks.public.root');
        $fullPath = $diskRoot . '/' . $directory;

        if (! is_dir($fullPath)) {
            $this->error("Directory not found: {$fullPath}");
            return Command::FAILURE;
        }

        $extensions = ['jpg', 'jpeg', 'png'];
        $totalSize = 0;
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, $extensions, true)) {
                continue;
            }
            $files[] = $file;
            $totalSize += $file->getSize();
        }

        if (empty($files)) {
            $this->warn('No image files found.');
            return Command::SUCCESS;
        }

        $this->info(count($files) . ' image files would be compressed.');
        $this->info('Total current size: ' . $this->formatBytes($totalSize));
        $this->newLine();

        // Estimate: quality=75, maxWidth=1920 → roughly 85-90% reduction for photos
        $estimatedAfter = (int) ($totalSize * 0.12);
        $estimatedSaving = $totalSize - $estimatedAfter;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Files found', count($files)],
                ['Total current size', $this->formatBytes($totalSize)],
                ['Estimated size after', $this->formatBytes($estimatedAfter) . ' (est.)'],
                ['Estimated saving', $this->formatBytes($estimatedSaving) . ' (~88% est.)'],
            ]
        );

        $this->newLine();
        $this->warn('DRY RUN — no files were changed.');

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
