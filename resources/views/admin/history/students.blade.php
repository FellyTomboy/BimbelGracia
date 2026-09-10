<x-app-layout>
    <x-slot name="title">Riwayat Les Murid</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Les Murid</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.history.students') }}" class="p-6 grid md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Murid</label>
                        <select name="student_id" class="mt-1 w-full border-gray-300 rounded-md">
                            <option value="">Semua</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected($studentId == $student->id)>{{ $student->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bulan</label>
                        <input type="number" name="month" value="{{ $month ?? '' }}" min="1" max="12" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Bulan" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tahun</label>
                        <input type="number" name="year" value="{{ $year ?? '' }}" min="2020" max="2100" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Tahun" />
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Terapkan</button>
                        <a href="{{ route('admin.history.students') }}" class="text-sm text-gray-500">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Periode</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Guru</th>
                                <th class="py-2">Program</th>
                                <th class="py-2">Biaya</th>
                                <th class="py-2">Jml</th>
                                <th class="py-2">Subtotal</th>
                                <th class="py-2">Diskon</th>
                                <th class="py-2">Denda</th>
                                <th class="py-2">Total</th>
                                <th class="py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @php
                                $calcService = app(\App\Services\CalculationService::class);
                                $grandTotal = 0;
                                $grandDiscount = 0;
                                $grandPenalty = 0;
                            @endphp
                            @foreach ($grouped as $periodKey => $periodAttendances)
                                @php
                                    $first = $periodAttendances->first();
                                    $periodMonth = $first->month;
                                    $periodYear = $first->year;
                                    $periodSubtotal = 0;
                                    $periodDiscount = 0;
                                    $periodPenalty = 0;

                                    if ($studentId) {
                                        $student = $students->firstWhere('id', (int) $studentId);
                                        if ($student) {
                                            $result = $calcService->calculateStudentBilling($student, $periodMonth, $periodYear, $periodAttendances);
                                            foreach ($result['rows'] as $row) {
                                                $grandTotal += $row['total'];
                                                $grandDiscount += $row['discount']['amount'] ?? 0;
                                                $grandPenalty += $row['penalty'];
                                                $periodSubtotal += $row['total'];
                                                $periodDiscount += $row['discount']['amount'] ?? 0;
                                                $periodPenalty += $row['penalty'];
                                            }
                                            @endphp
                                            @foreach ($result['rows'] as $row)
                                                <tr>
                                                    <td class="py-2">{{ sprintf('%02d', $periodMonth) }}/{{ $periodYear }}</td>
                                                    <td class="py-2">{{ $student->display_name }}</td>
                                                    <td class="py-2">{{ $row['teacher'] }}</td>
                                                    <td class="py-2">{{ $row['teacher'] }} - {{ $row['program'] }}{{ $row['detail'] }}</td>
                                                    <td class="py-2">Rp {{ number_format($row['rate']) }}</td>
                                                    <td class="py-2">{{ $row['count'] }}x</td>
                                                    <td class="py-2">Rp {{ number_format($row['subtotal']) }}</td>
                                                    <td class="py-2">{{ ($row['discount']['amount'] ?? 0) > 0 ? '-Rp '.number_format($row['discount']['amount']) : '-' }}</td>
                                                    <td class="py-2">{{ $row['penalty'] > 0 ? '+Rp '.number_format($row['penalty']) : '-' }}</td>
                                                    <td class="py-2 font-medium">Rp {{ number_format($row['total']) }}</td>
                                                    <td class="py-2">{{ $first->parent_payment_status }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="bg-slate-50 text-gray-600">
                                                <td colspan="9" class="py-1 text-right text-xs font-medium">Subtotal {{ sprintf('%02d', $periodMonth) }}/{{ $periodYear }}</td>
                                                <td class="py-1 text-xs">Rp {{ number_format($periodSubtotal) }}</td>
                                                <td class="py-1"></td>
                                            </tr>
                                        @php
                                        }
                                    } else {
                                        // Show per-attendance for "all students"
                                        foreach ($periodAttendances as $attendance) {
                                            $billing = $calcService->calculateAttendanceBilling($attendance);
                                            $grandTotal += $billing['total'];
                                            $periodSubtotal += $billing['total'];
                                            @endphp
                                            <tr>
                                                <td class="py-2">{{ sprintf('%02d', $attendance->month) }}/{{ $attendance->year }}</td>
                                                <td class="py-2">
                                                    @foreach ($attendance->students as $s)
                                                        <x-hibernated-label :model="$s" :label="$s->display_name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                                                    @endforeach
                                                </td>
                                                <td class="py-2">
                                                    <x-hibernated-label :model="$attendance->sessionTeacher ?? $attendance->enrollment?->teacher" :label="($attendance->sessionTeacher ?? $attendance->enrollment?->teacher)?->displayName ?? '-'" type="guru" />
                                                </td>
                                                <td class="py-2">
                                                    <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" />
                                                </td>
                                                <td class="py-2">Rp {{ number_format($billing['parent_rate']) }}</td>
                                                <td class="py-2">{{ $billing['present_sum'] }}</td>
                                                <td class="py-2">Rp {{ number_format($billing['total']) }}</td>
                                                <td class="py-2">-</td>
                                                <td class="py-2">-</td>
                                                <td class="py-2">Rp {{ number_format($billing['total']) }}</td>
                                                <td class="py-2">{{ $attendance->parent_payment_status }}</td>
                                            </tr>
                                        @php
                                        }
                                        @endphp
                                        <tr class="bg-slate-50 text-gray-600">
                                            <td colspan="9" class="py-1 text-right text-xs font-medium">Subtotal {{ sprintf('%02d', $periodMonth) }}/{{ $periodYear }}</td>
                                            <td class="py-1 text-xs">Rp {{ number_format($periodSubtotal) }}</td>
                                            <td class="py-1"></td>
                                        </tr>
                                @php
                                    }
                                @endphp
                            @endforeach
                        </tbody>
                        @if ($grandTotal > 0)
                            <tfoot>
                                <tr class="font-bold bg-gray-50">
                                    <td colspan="6" class="py-2 text-right">Grand Total</td>
                                    <td class="py-2">Rp {{ number_format($grandTotal + $grandDiscount - $grandPenalty) }}</td>
                                    <td class="py-2">{{ $grandDiscount > 0 ? '-Rp '.number_format($grandDiscount) : '-' }}</td>
                                    <td class="py-2">{{ $grandPenalty > 0 ? '+Rp '.number_format($grandPenalty) : '-' }}</td>
                                    <td class="py-2">Rp {{ number_format($grandTotal) }}</td>
                                    <td class="py-2"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>

                    <div class="mt-4">
                        {{ $periodPagination->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var key = 'scroll_' + location.pathname + '?{{ http_build_query(request()->query()) }}';
        window.addEventListener('load', function () {
            var pos = sessionStorage.getItem(key);
            if (pos !== null) { window.scrollTo(0, parseInt(pos, 10)); sessionStorage.removeItem(key); }
        });
        document.querySelectorAll('form[method=POST], a[href*="delete"], a[href*="destroy"]').forEach(function (el) {
            el.addEventListener('click', function () { sessionStorage.setItem(key, window.scrollY); });
        });
        document.querySelectorAll('form[method=GET]').forEach(function (form) {
            form.addEventListener('submit', function () { sessionStorage.setItem(key, 0); });
        });
    })();
    </script>
</x-app-layout>