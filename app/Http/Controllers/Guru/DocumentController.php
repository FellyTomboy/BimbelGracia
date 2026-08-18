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
use Illuminate\View\View;
<<<<<<< HEAD
use Symfony\Component\HttpFoundation\HeaderUtils;
=======
>>>>>>> 1a30744 (feat: secure document storage and add download with access logging)
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

        // Log the view access (only when the document is actually unlocked/accessible).
        if ($document->canBeAccessedBy($user)) {
            $this->logAccess($document, $user, 'view');
        }

        // Log the view
        $this->logAccess($request, $document, 'view');

        // Get teacher's name for watermark
        $teacherName = $teacher?->display_name ?? 'Guru';

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

        return view('guru.documents.viewer', compact('document', 'watermarkText'));
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
<<<<<<< HEAD
     * Stream document file with authorization check.
     * Files are served from the private 'documents' disk — not publicly accessible.
     */
    public function stream(Request $request, Document $document): StreamedResponse
=======
     * Stream the document file to the browser for viewing.
     * Performs server-side authorization before serving the file.
     */
    public function view(Request $request, Document $document): StreamedResponse
>>>>>>> 1a30744 (feat: secure document storage and add download with access logging)
    {
        $user = $request->user();

<<<<<<< HEAD
        // Authorization check
        if ($document->access_type === 'teacher') {
            $hasAccess = $teacher && $document->teachers()->where('teacher_id', $teacher->id)->exists();
            abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke dokumen ini.');
        } elseif ($document->access_type === 'password') {
            $unlocked = session()->get('document_unlocked_' . $document->id, false);
            abort_unless($unlocked, 403, 'Masukkan password terlebih dahulu.');
        }

        // Verify file exists on private disk
        abort_if(
            !Storage::disk('documents')->exists($document->file_path),
            404,
            'File tidak ditemukan.'
        );

        // Log the access (do this BEFORE streaming so failures abort cleanly)
        $this->logAccess($request, $document, 'stream');

        $fullPath = Storage::disk('documents')->path($document->file_path);

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
                'Content-Type' => $document->file_type ?: 'application/octet-stream',
                'Content-Disposition' => $disposition,
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
=======
        // Server-side authorization before serving the file.
        abort_unless($document->canBeAccessedBy($user), 403, 'Anda tidak memiliki akses ke dokumen ini.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->file_path), 404, 'File tidak ditemukan.');

        // Log the view access.
        $this->logAccess($document, $user, 'view');

        $contentType = $document->file_type ?: 'application/octet-stream';

        return $disk->response($document->file_path, $document->file_name, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    /**
     * Download the document file.
     * Performs server-side authorization before serving the file.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        $user = $request->user();

        // Server-side authorization before serving the file.
        abort_unless($document->canBeAccessedBy($user), 403, 'Anda tidak memiliki akses ke dokumen ini.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->file_path), 404, 'File tidak ditemukan.');

        // Log the download access.
        $this->logAccess($document, $user, 'download');

        return $disk->download($document->file_path, $document->file_name, [
            'Content-Type' => $document->file_type ?: 'application/octet-stream',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'attachment; filename="' . $document->file_name . '"',
        ]);
    }

    /**
     * Record a document access event.
     */
    private function logAccess(Document $document, $user, string $action): void
    {
        $teacher = Teacher::where('user_id', $user->id)->first();

        DocumentAccessLog::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'teacher_id' => $teacher?->id,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
        ]);
>>>>>>> 1a30744 (feat: secure document storage and add download with access logging)
    }

    /**
     * Log document access to the database.
     */
    private function logAccess(Request $request, Document $document, string $action): void
    {
        DocumentAccessLog::create([
            'user_id' => $request->user()->id,
            'document_id' => $document->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'accessed_at' => now(),
        ]);
    }
}
