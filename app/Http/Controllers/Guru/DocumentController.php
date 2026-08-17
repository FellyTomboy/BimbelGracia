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
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

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
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        // Check access
        if ($document->access_type === 'teacher') {
            $hasAccess = $teacher && $document->teachers()->where('teacher_id', $teacher->id)->exists();
            abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        // Log the view
        $this->logAccess($request, $document, 'view');

        // Get teacher's name for watermark
        $teacherName = $teacher?->display_name ?? 'Guru';

        return view('guru.documents.show', compact('document', 'teacherName'));
    }

    public function verifyPassword(Request $request, Document $document): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!$document->access_password || !Hash::check($validated['password'], $document->access_password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        session()->put('document_unlocked_' . $document->id, true);

        return redirect()->route('guru.documents.show', $document);
    }

    /**
     * Stream document file with authorization check.
     * Files are served from the private 'documents' disk — not publicly accessible.
     */
    public function stream(Request $request, Document $document): StreamedResponse
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

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
