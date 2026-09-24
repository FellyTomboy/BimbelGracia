<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ImageCompressionService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(Driver::class);
    }

    /**
     * Compress an image file in place.
     *
     * @param  string  $path  Absolute filesystem path
     * @param  int  $maxWidth
     * @param  int  $quality  JPEG quality (0-100)
     * @return array{before: int, after: int, saved: int}
     */
    public function compressFile(string $path, int $maxWidth = 1920, int $quality = 75): array
    {
        $before = filesize($path);

        $img = $this->manager->decodePath($path);

        // Downscale only — don't upscale small images
        if ($img->width() > $maxWidth) {
            $img->scale($maxWidth);
        }

        // Normalize to JPEG for photos (smaller than PNG)
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'png') {
            $newPath = preg_replace('/\.[^.]+$/', '.jpg', $path);
            $img->encodeUsingFormat(Format::JPEG, $quality)->save($newPath);
            if ($newPath !== $path) {
                @unlink($path);
            }
            $reportPath = $newPath;
        } else {
            $img->encodeUsingFormat(Format::JPEG, $quality)->save($path);
            $reportPath = $path;
        }

        clearstatcache(true, $reportPath);
        $after = file_exists($reportPath) ? filesize($reportPath) : $before;

        return [
            'before' => $before,
            'after' => $after,
            'saved' => $before - $after,
        ];
    }

    /**
     * Compress all images under a given base path (relative to public disk).
     *
     * @return array<string, array{before:int, after:int, saved:int}>
     */
    public function compressDirectory(
        string $relativePath,
        int $maxWidth = 1920,
        int $quality = 75
    ): array {
        $diskRoot = config('filesystems.disks.public.root');
        $fullPath = $diskRoot . '/' . $relativePath;

        if (! is_dir($fullPath)) {
            return [];
        }

        $results = [];
        $extensions = ['jpg', 'jpeg', 'png'];

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

            try {
                $result = $this->compressFile($file->getRealPath(), $maxWidth, $quality);
                $results[$file->getRealPath()] = $result;
            } catch (\Throwable $e) {
                $results[$file->getRealPath()] = [
                    'before' => $file->getSize(),
                    'after' => $file->getSize(),
                    'saved' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
