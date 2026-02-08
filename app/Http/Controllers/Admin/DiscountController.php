<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\EnrollmentStudentDiscount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountController extends Controller
{
    public function index(Request $request): View
    {
        [$month, $year] = $this->resolvePeriod($request);

        $enrollments = Enrollment::query()
            ->with(['program', 'teacher', 'students'])
            ->orderBy('id')
            ->get();

        return view('admin.discounts.index', [
            'month' => $month,
            'year' => $year,
            'enrollments' => $enrollments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer', 'exists:enrollments,id'],
            'discount_type' => ['required', 'in:percent,amount'],
            'discount_value' => ['required', 'integer', 'min:0'],
        ]);

        $value = (int) $validated['discount_value'];
        $type = $validated['discount_type'];

        $enrollments = Enrollment::query()
            ->with('students')
            ->whereIn('id', $validated['enrollment_ids'])
            ->get();

        foreach ($enrollments as $enrollment) {
            foreach ($enrollment->students as $student) {
                if ($value === 0) {
                    EnrollmentStudentDiscount::query()
                        ->where('enrollment_id', $enrollment->id)
                        ->where('student_id', $student->id)
                        ->where('month', $validated['month'])
                        ->where('year', $validated['year'])
                        ->delete();
                    continue;
                }

                EnrollmentStudentDiscount::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'student_id' => $student->id,
                        'month' => $validated['month'],
                        'year' => $validated['year'],
                    ],
                    [
                        'discount_type' => $type,
                        'discount_value' => $value,
                    ]
                );
            }
        }

        return back()->with('status', 'Diskon massal berhasil diterapkan.');
    }

    private function resolvePeriod(Request $request): array
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        return [$month, $year];
    }
}
