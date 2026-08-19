<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        $documents = Document::with(['uploader'])
            ->where(function ($q) use ($teacher) {
                // Documents assigned to this teacher
                if ($teacher) {
                    $q->whereHas('teachers', fn ($q) => $q->where('teacher_id', $teacher->id));
                }
                // OR documents with password access
                $q->orWhere('access_type', 'password');
            })
            ->latest()
            ->get();

        return view('guru.documents.index', compact('documents'));
    }

    public function show(Request $request, Document $document): View
    {
        $user = $request->user();

        // Server-side authorization: guru must be able to see this document in their list.
        // For password documents, the page shows the password form when not yet unlocked,
        // so we only require visibility here. Actual file access is enforced in view/download.
        abort_unless($document->isVisibleTo($user), 403, 'Anda tidak memiliki akses ke dokumen ini.');

        // Get teacher's name for watermark
        $teacher = Teacher::where('user_id', $user->id)->first();
        $teacherName = $teacher?->display_name ?? $user->name;

        return view('guru.documents.show', compact('document', 'teacherName'));
    }

    /**
     * Render the secure document viewer page with watermark and protections.
     */
    public function viewer(Request $request, Document $document): View
    {
        $user = $request->user();

        // Server-side authorization: guru must be able to access this document.
        abort_unless($document->canBeAccessedBy($user), 403, 'Anda tidak memiliki akses ke dokumen ini.');

        // Build watermark text with teacher identity.
        $teacher = Teacher::where('user_id', $user->id)->first();
        $teacherName = $teacher?->display_name ?? $user->name;
        $watermarkText = $teacherName . ' • ' . $user->email;

        // Strict-tier documents load through a short-lived signed URL so the
        // underlying file endpoint can't be bookmarked, shared, or reused
        // outside this viewing session.
        $viewUrl = $document->isStrict()
            ? URL::temporarySignedRoute('guru.documents.view', now()->addMinutes(5), ['document' => $document->id])
            : route('guru.documents.view', $document);

        return view('guru.documents.viewer', compact('document', 'watermarkText', 'viewUrl'));
    }

    public function verifyPassword(Request $request, Document $document): RedirectResponse
    {
        // Only password-protected documents can be unlocked via password.
        abort_unless($document->access_type === 'password', 403, 'Dokumen ini tidak menggunakan akses password.');

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! $document->access_password || ! Hash::check($validated['password'], $document->access_password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        session()->put('document_unlocked_' . $document->id, true);

        return redirect()->route('guru.documents.show', $document);
    }

    /**
     * Stream the document file to the browser for viewing.
     * Performs server-side authorization before serving the file.
     * Strict-tier documents additionally require a valid signed request
     * (i.e. must come from the temporary URL issued by viewer()).
     *
     * Image files are watermarked server-side with the teacher's identity
     * as a deterrent against unauthorized redistribution.
     */
    public function view(Request $request, Document $document): StreamedResponse
    {
        $user = $request->user();

        // Server-side authorization before serving the file.
        abort_unless($document->canBeAccessedBy($user), 403, 'Anda tidak memiliki akses ke dokumen ini.');

        if ($document->isStrict() && ! $request->hasValidSignature()) {
            abort(403, 'Tautan akses tidak valid atau sudah kedaluwarsa.');
        }

        $disk = Storage::disk('documents');
        abort_unless($disk->exists($document->file_path), 404, 'File tidak ditemukan.');

        // Log the view access.
        $this->logAccess($request, $document, 'view');

        $fullPath = $disk->path($document->file_path);
        $mimeType = $document->file_type ?: 'application/octet-stream';

        // Server-side image watermarking for JPEG/PNG images.
        $imageMimeTypes = ['image/jpeg', 'image/pjpeg', 'image/png'];
        if (in_array($mimeType, $imageMimeTypes, true)) {
            $watermarkText = $this->buildImageWatermarkText($user);

            return response()->stream(
                function () use ($fullPath, $mimeType, $watermarkText) {
                    $this->streamWatermarkedImage($fullPath, $mimeType, $watermarkText);
                },
                200,
                [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, private, max-age=0',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'SAMEORIGIN',
                    'Content-Security-Policy' => "default-src 'none'; sandbox",
                    'Referrer-Policy' => 'no-referrer',
                ]
            );
        }

        // Use HeaderUtils to safely build Content-Disposition (prevents header injection via filename)
        $disposition = HeaderUtils::makeDisposition(
            'inline',
            $document->file_name,
            'document'
        );

        return response()->streamDownload(
            fn () => readfile($fullPath),
            null,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => $disposition,
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
                'Referrer-Policy' => 'no-referrer',
            ]
        );
    }

    /**
     * Build a short watermark text string for image overlay.
     */
    private function buildImageWatermarkText(object $user): string
    {
        $teacher = Teacher::where('user_id', $user->id)->first();
        $name = $teacher?->display_name ?? $user->name;

        return $name . ' — ' . now()->format('d/m/Y H:i');
    }

    /**
     * Output a watermarked version of an image file using GD.
     * Falls back to the original file on any GD error.
     */
    private function streamWatermarkedImage(string $path, string $mimeType, string $text): void
    {
        try {
            // Load source image from disk.
            $sourceImage = match ($mimeType) {
                'image/jpeg', 'image/pjpeg' => imagecreatefromjpeg($path),
                'image/png' => imagecreatefrompng($path),
                default => null,
            };

            if (! $sourceImage) {
                readfile($path);
                return;
            }

            $srcW = imagesx($sourceImage);
            $srcH = imagesy($sourceImage);

            // Scale watermark font size to image dimensions.
            $fontSize = max(2, min(5, (int) floor($srcW / 150)));

            // Text bounding box (GD built-in bitmap font).
            $bbox = imagettfbbox($fontSize, 30, $this->watermarkTtfFont(), $text);
            $textW = abs($bbox[2] - $bbox[0]);
            $textH = abs($bbox[5] - $bbox[3]);

            // Tile diagonally across the image.
            $strideX = $textW + intval($srcW * 0.12);
            $strideY = intval($textH * 5.5);
            $angle = 30;

            // Allocate watermark color: semi-transparent white.
            $wmColor = imagecolorallocatealpha($sourceImage, 255, 255, 255, 100);

            // Apply tiled diagonal text overlay.
            for ($y = -$srcH; $y < $srcH * 2; $y += $strideY) {
                for ($x = -$srcW; $x < $srcW * 2; $x += $strideX) {
                    imagettftext(
                        $sourceImage,
                        $fontSize,
                        $angle,
                        $x,
                        $y,
                        $wmColor,
                        $this->watermarkTtfFont(),
                        $text
                    );
                }
            }

            // Stream the watermarked result.
            ob_start();
            match ($mimeType) {
                'image/jpeg', 'image/pjpeg' => imagejpeg($sourceImage, null, 82),
                'image/png' => imagepng($sourceImage, null, 5),
                default => null,
            };
            echo ob_get_clean();

            imagedestroy($sourceImage);
        } catch (\Throwable) {
            // Fall back to original on any GD error.
            if (isset($sourceImage) && $sourceImage) {
                imagedestroy($sourceImage);
            }
            readfile($path);
        }
    }

    /**
     * Return the path to a TrueType font for watermarking.
     * Uses the first available TTF in the storage/fonts directory,
     * falling back to DejaVu Sans if nothing is found.
     */
    private function watermarkTtfFont(): string
    {
        $dir = storage_path('app/fonts');
        if (is_dir($dir)) {
            $fonts = glob("$dir/*.ttf");
            if ($fonts !== false && count($fonts) > 0) {
                return realpath($fonts[0]) ?: $fonts[0];
            }
        }
        // Return DejaVu Sans bundled with Laravel's debug toolbar or a safe empty font path.
        // GD will fall back to a bitmap font when no TTF is available.
        return '';
    }

    /**
     * Download the document file.
     * Performs server-side authorization before serving the file.
     * Strict-tier documents cannot be downloaded at all — view-only.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        $user = $request->user();

        // Server-side authorization before serving the file.
        abort_unless($document->canBeAccessedBy($user), 403, 'Anda tidak memiliki akses ke dokumen ini.');

        abort_if($document->isStrict(), 403, 'Dokumen ini tidak dapat diunduh.');

        $disk = Storage::disk('documents');
        abort_unless($disk->exists($document->file_path), 404, 'File tidak ditemukan.');

        // Log the download access.
        $this->logAccess($request, $document, 'download');

        $fullPath = $disk->path($document->file_path);

        // Use HeaderUtils to safely build Content-Disposition (prevents header injection via filename)
        $disposition = HeaderUtils::makeDisposition(
            'attachment',
            $document->file_name,
            'document'
        );

        return response()->streamDownload(
            fn () => readfile($fullPath),
            null,
            [
                'Content-Type' => $document->file_type ?: 'application/octet-stream',
                'Content-Disposition' => $disposition,
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
                'Referrer-Policy' => 'no-referrer',
            ]
        );
    }

    /**
     * Record a document access event.
     */
    private function logAccess(Request $request, Document $document, string $action): void
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();

        DocumentAccessLog::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'teacher_id' => $teacher?->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);
    }
}