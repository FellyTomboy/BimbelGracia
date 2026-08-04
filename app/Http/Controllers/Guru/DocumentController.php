<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

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

        return view('guru.documents.show', compact('document'));
    }

    public function verifyPassword(Request $request, Document $document): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!$document->access_password || !Hash::check($validated['password'], $document->access_password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        // Store in session that this document is unlocked
        session()->put('document_unlocked_' . $document->id, true);

        return redirect()->route('guru.documents.show', $document);
    }

    public function download(Request $request, Document $document): RedirectResponse
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        // Check access
        if ($document->access_type === 'teacher') {
            $hasAccess = $teacher && $document->teachers()->where('teacher_id', $teacher->id)->exists();
            abort_unless($hasAccess, 403);
        } elseif ($document->access_type === 'password') {
            $unlocked = session()->get('document_unlocked_' . $document->id, false);
            abort_unless($unlocked, 403, 'Masukkan password terlebih dahulu.');
        }

        return redirect($document->file_url);
    }
}