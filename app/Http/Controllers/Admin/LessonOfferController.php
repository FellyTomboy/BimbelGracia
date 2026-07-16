<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LessonOffer;
use App\Traits\SearchAndSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonOfferController extends Controller
{
    use SearchAndSort;

    private array $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    private array $times = ['pagi', 'siang', 'sore', 'malam'];

    private array $educationLevels = [
        'Pre-school / PAUD',
        'TK A',
        'TK B',
        'SD 1',
        'SD 2',
        'SD 3',
        'SD 4',
        'SD 5',
        'SD 6',
        'SMP 1',
        'SMP 2',
        'SMP 3',
        'SMA 1',
        'SMA 2',
        'SMA 3',
        'SMK 1',
        'SMK 2',
        'SMK 3',
        'Professional',
        'Dewasa',
    ];

    public function index(Request $request): View
    {
        $params = $this->getSearchSortParams($request);

        $offers = LessonOffer::with('creator');

        $offers = $this->applySearch($offers, $params['search'], [
            'code', 'education_level', 'subject', 'status',
        ]);

        $offers = $this->applySort($offers, $params['sort'], $params['direction'], [
            'code', 'education_level', 'subject', 'status', 'created_at',
        ]);

        $offers = $offers->paginate(20)->withQueryString();

        return view('admin.lesson-offers.index', compact('offers'));
    }

    public function inactive(): View
    {
        $offers = LessonOffer::onlyTrashed()
            ->with('creator')
            ->latest('deleted_at')
            ->get();

        return view('admin.lesson-offers.inactive', compact('offers'));
    }

    public function create(): View
    {
        return view('admin.lesson-offers.create', [
            'educationLevels' => $this->educationLevels,
            'days' => $this->days,
            'times' => $this->times,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'education_level' => ['required', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day' => ['required', 'string', 'max:20'],
            'schedules.*.time' => ['required', 'string', 'in:pagi,siang,sore,malam'],
            'note' => ['nullable', 'string'],
            'status' => ['required', 'in:open,closed'],
            'contact_whatsapp' => ['nullable', 'string', 'max:32'],
        ]);

        LessonOffer::create([
            'education_level' => $validated['education_level'],
            'subject' => $validated['subject'],
            'schedules' => $validated['schedules'],
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
        return view('admin.lesson-offers.edit', [
            'lessonOffer' => $lessonOffer,
            'educationLevels' => $this->educationLevels,
            'days' => $this->days,
            'times' => $this->times,
        ]);
    }

    public function update(Request $request, LessonOffer $lessonOffer): RedirectResponse
    {
        $validated = $request->validate([
            'education_level' => ['required', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day' => ['required', 'string', 'max:20'],
            'schedules.*.time' => ['required', 'string', 'in:pagi,siang,sore,malam'],
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
