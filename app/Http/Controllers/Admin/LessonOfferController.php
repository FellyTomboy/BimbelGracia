<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LessonOffer;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonOfferController extends Controller
{
    public function index(): View
    {
        $offers = LessonOffer::with(['student', 'creator'])
            ->withTrashed()
            ->latest()
            ->get();

        return view('admin.lesson-offers.index', compact('offers'));
    }

    public function create(): View
    {
        $students = Student::orderBy('name')->get();

        return view('admin.lesson-offers.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:lesson_offers,code'],
            'student_id' => ['required', 'exists:students,id'],
            'subject' => ['required', 'string', 'max:255'],
            'schedule_day' => ['required', 'string', 'max:20'],
            'schedule_time' => ['required', 'date_format:H:i'],
            'note' => ['nullable', 'string'],
            'status' => ['required', 'in:open,closed'],
            'contact_whatsapp' => ['nullable', 'string', 'max:32'],
        ]);

        LessonOffer::create([
            'code' => $validated['code'],
            'student_id' => $validated['student_id'],
            'subject' => $validated['subject'],
            'schedule_day' => $validated['schedule_day'],
            'schedule_time' => $validated['schedule_time'],
            'note' => $validated['note'] ?? null,
            'status' => $validated['status'],
            'contact_whatsapp' => $validated['contact_whatsapp'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.lesson-offers.index')
            ->with('status', 'Tawaran les berhasil dibuat.');
    }

    public function edit(LessonOffer $lessonOffer): View
    {
        $students = Student::orderBy('name')->get();

        return view('admin.lesson-offers.edit', compact('lessonOffer', 'students'));
    }

    public function update(Request $request, LessonOffer $lessonOffer): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:lesson_offers,code,'.$lessonOffer->id],
            'student_id' => ['required', 'exists:students,id'],
            'subject' => ['required', 'string', 'max:255'],
            'schedule_day' => ['required', 'string', 'max:20'],
            'schedule_time' => ['required', 'date_format:H:i'],
            'note' => ['nullable', 'string'],
            'status' => ['required', 'in:open,closed'],
            'contact_whatsapp' => ['nullable', 'string', 'max:32'],
        ]);

        $lessonOffer->update($validated);

        return redirect()
            ->route('admin.lesson-offers.index')
            ->with('status', 'Tawaran les berhasil diperbarui.');
    }

    public function destroy(LessonOffer $lessonOffer): RedirectResponse
    {
        $lessonOffer->delete();

        return redirect()
            ->route('admin.lesson-offers.index')
            ->with('status', 'Tawaran les dihibernasi.');
    }

    public function restore(int $lessonOfferId): RedirectResponse
    {
        $lessonOffer = LessonOffer::withTrashed()->findOrFail($lessonOfferId);
        $lessonOffer->restore();

        return redirect()
            ->route('admin.lesson-offers.index')
            ->with('status', 'Tawaran les berhasil dipulihkan.');
    }
}
