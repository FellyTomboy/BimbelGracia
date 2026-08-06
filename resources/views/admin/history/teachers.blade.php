<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Mengajar Guru</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.history.teachers') }}" class="p-6 grid md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Guru</label>
                        <select name="teacher_id" class="mt-1 w-full border-gray-300 rounded-md">
                            <option value="">Semua</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected($teacherId == $teacher->id)>{{ $teacher->name }}</option>
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
                        <a href="{{ route('admin.history.teachers') }}" class="text-sm text-gray-500">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Periode</th>
                                <th class="py-2">Guru</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Program</th>
                                <th class="py-2">Tarif</th>
                                <th class="py-2">Jml</th>
                                <th class="py-2">Subtotal</th>
                                <th class="py-2">Denda</th>
                                <th class="py-2">Total</th>
                                <th class="py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @php
                                $calcService = app(\App\Services\CalculationService::class);
                                $grandTotal = 0;
                                $grandPenalty = 0;
                            @endphp
                            @foreach ($attendances->groupBy(fn($a) => $a->month . '-' . $a->year . '-' . ($teacherId ?: 'all')) as $periodKey => $periodAttendances)
                                @php
                                    $first = $periodAttendances->first();
                                    $periodMonth = $first->month;
                                    $periodYear = $first->year;

                                    if ($teacherId) {
                                        $teacher = $teachers->firstWhere('id', (int) $teacherId);
                                        if ($teacher) {
                                            $result = $calcService->calculateTeacherSalary((int) $teacherId, $periodMonth, $periodYear, $periodAttendances);
                                            $grandTotal += $result['grand_total'];
                                            $grandPenalty += $result['total_penalty'];
                                            @endphp
                                            @foreach ($result['rows'] as $row)
                                                <tr>
                                                    <td class="py-2">{{ sprintf('%02d', $periodMonth) }}/{{ $periodYear }}</td>
                                                    <td class="py-2">{{ $teacher->name }}</td>
                                                    <td class="py-2">{{ $row['student'] }}</td>
                                                    <td class="py-2">{{ $row['program'] }}<br><span class="text-xs text-gray-500">{{ $row['label_detail'] }}</span></td>
                                                    <td class="py-2">Rp {{ number_format($row['rate']) }}</td>
                                                    <td class="py-2">{{ $row['count'] }}x</td>
                                                    <td class="py-2">Rp {{ number_format($row['total']) }}</td>
                                                    <td class="py-2">{{ $row['penalty'] > 0 ? '-Rp '.number_format($row['penalty']) : '-' }}</td>
                                                    <td class="py-2 font-medium">Rp {{ number_format($row['total'] - $row['penalty']) }}</td>
                                                    <td class="py-2">{{ $first->teacher_payment_status }}</td>
                                                </tr>
                                            @endforeach
                                        @php
                                        }
                                    } else {
                                        // Show per-attendance for "all teachers"
                                        foreach ($periodAttendances as $attendance) {
                                            $rate = $attendance->teacher_rate ?? 0;
                                            $present = $attendance->students->sum(fn ($s) => (int) ($s->pivot?->total_present ?? 0));
                                            $total = $present * $rate;
                                            $penalty = $attendance->status_validation === 'terlambat' ? (int) ($rate * 0.1) : 0;
                                            $grandTotal += $total;
                                            $grandPenalty += $penalty;
                                            @endphp
                                            <tr>
                                                <td class="py-2">{{ sprintf('%02d', $attendance->month) }}/{{ $attendance->year }}</td>
                                                <td class="py-2">
                                                    <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->name ?? '-'" type="guru" />
                                                </td>
                                                <td class="py-2">
                                                    @foreach ($attendance->students as $s)
                                                        <x-hibernated-label :model="$s" :label="$s->name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                                                    @endforeach
                                                </td>
                                                <td class="py-2">
                                                    <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" />
                                                </td>
                                                <td class="py-2">Rp {{ number_format($rate) }}</td>
                                                <td class="py-2">{{ $present }}</td>
                                                <td class="py-2">Rp {{ number_format($total) }}</td>
                                                <td class="py-2">{{ $penalty > 0 ? '-Rp '.number_format($penalty) : '-' }}</td>
                                                <td class="py-2">Rp {{ number_format($total - $penalty) }}</td>
                                                <td class="py-2">{{ $attendance->teacher_payment_status }}</td>
                                            </tr>
                                        @php
                                        }
                                    }
                                @endphp
                            @endforeach
                        </tbody>
                        @if ($grandTotal > 0)
                            <tfoot>
                                <tr class="font-bold bg-gray-50">
                                    <td colspan="6" class="py-2 text-right">Grand Total</td>
                                    <td class="py-2">Rp {{ number_format($grandTotal) }}</td>
                                    <td class="py-2">{{ $grandPenalty > 0 ? '-Rp '.number_format($grandPenalty) : '-' }}</td>
                                    <td class="py-2">Rp {{ number_format($grandTotal - $grandPenalty) }}</td>
                                    <td class="py-2"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>

                    <div class="mt-4">
                        {{ $attendances->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>