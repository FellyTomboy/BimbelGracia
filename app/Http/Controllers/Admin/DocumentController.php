<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

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
    public function index(): View
    {
        $documents = Document::with(['uploader', 'teachers'])
            ->latest()
            ->paginate(20);

        return view('admin.documents.index', compact('documents'));
    }

    public function create(): View
    {
        $teachers = Teacher::where('status', 'active')->orderBy('name')->get();
        return view('admin.documents.create', compact('teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:51200'], // max 50MB
            'access_type' => ['required', 'in:teacher,password'],
            'access_password' => ['nullable', 'string', 'min:4', 'max:255'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
        ]);

        // Handle file upload
        $file = $validated['file'];
        $originalName = $file->getClientOriginalName();
        $path = $file->store('', 'documents');

        $document = Document::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_name' => $originalName,
            'file_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'access_type' => $validated['access_type'],
            'access_password' => $validated['access_password'] ? Hash::make($validated['access_password']) : null,
            'access_password_plain' => $validated['access_password'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);
        // Sync teachers if access_type = teacher
        if ($validated['access_type'] === 'teacher' && !empty($validated['teacher_ids'])) {
            $document->teachers()->sync($validated['teacher_ids']);
        }

        return redirect()
            ->route('admin.documents.index')
            ->with('status', 'Dokumen berhasil diupload.');
    }

    public function edit(Document $document): View
    {
        $document->load('teachers');
        $teachers = Teacher::where('status', 'active')->orderBy('name')->get();
        return view('admin.documents.edit', compact('document', 'teachers'));
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:51200'],
            'access_type' => ['required', 'in:teacher,password'],
            'access_password' => ['nullable', 'string', 'min:4', 'max:255'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
        ]);

        $updateData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'access_type' => $validated['access_type'],
        ];

        // Handle password
        if ($validated['access_type'] === 'password' && $validated['access_password']) {
            $updateData['access_password'] = Hash::make($validated['access_password']);
            $updateData['access_password_plain'] = $validated['access_password'];
        } elseif ($validated['access_type'] === 'teacher') {
            $updateData['access_password'] = null;
            $updateData['access_password_plain'] = null;
        }

        // Handle file replacement
        if ($request->hasFile('file')) {
            Storage::disk('documents')->delete($document->file_path);
            $file = $validated['file'];
            $updateData['file_path'] = $file->store('', 'documents');
            $updateData['file_name'] = $file->getClientOriginalName();
            $updateData['file_type'] = $file->getMimeType() ?: $file->getClientMimeType();
            $updateData['file_size'] = $file->getSize();
        }

        $document->update($updateData);

        // Sync teachers
        if ($validated['access_type'] === 'teacher') {
            $document->teachers()->sync($validated['teacher_ids'] ?? []);
        } else {
            $document->teachers()->detach();
        }

        return redirect()
            ->route('admin.documents.index')
            ->with('status', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        Storage::disk('documents')->delete($document->file_path);
        $document->delete();

        return redirect()
            ->route('admin.documents.index')
            ->with('status', 'Dokumen berhasil dihapus.');
    }

    /**
     * Stream document file for download (admin).
     */
    public function stream(Document $document): StreamedResponse
    {
        abort_if(
            !Storage::disk('documents')->exists($document->file_path),
            404,
            'File tidak ditemukan.'
        );

        $fullPath = Storage::disk('documents')->path($document->file_path);

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
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Stream document file for inline preview (admin).
     */
    public function preview(Document $document): StreamedResponse
    {
        abort_if(
            !Storage::disk('documents')->exists($document->file_path),
            404,
            'File tidak ditemukan.'
        );

        $fullPath = Storage::disk('documents')->path($document->file_path);

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
}