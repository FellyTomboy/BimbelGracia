<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Murid') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Kalender Les</h3>
                @php
                    $now = now();
                    $month = (int) request('month', $now->month);
                    $year = (int) request('year', $now->year);
                    $start = Carbon\Carbon::create($year, $month, 1);
                    $end = $start->copy()->endOfMonth();
                    $firstDayOfWeek = $start->dayOfWeekIso;

                    $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'];
                    $programColors = [];

                    $attendances = \App\Models\MonthlyAttendance::with(['enrollment.program', 'students'])
                        ->whereHas('students', fn ($q) => $q->where('student_id', auth()->user()->student?->id ?? 0))
                        ->whereMonth('lesson_date', $month)
                        ->whereYear('lesson_date', $year)
                        ->orderBy('lesson_date')
                        ->get()
                        ->groupBy(fn ($a) => $a->lesson_date?->format('Y-m-d'));

                    // Generate unique colors per program
                    $colorIdx = 0;
                @endphp

                <div class="flex items-center justify-between mb-4">
                    <a href="?month={{ $month - 1 < 1 ? 12 : $month - 1 }}&year={{ $month - 1 < 1 ? $year - 1 : $year }}" class="text-sm text-indigo-600">&larr; Sebelumnya</a>
                    <span class="font-semibold">{{ $start->format('F Y') }}</span>
                    <a href="?month={{ $month + 1 > 12 ? 1 : $month + 1 }}&year={{ $month + 1 > 12 ? $year + 1 : $year }}" class="text-sm text-indigo-600">Berikutnya &rarr;</a>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-gray-500 mb-1">
                    @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayName)
                        <div class="py-1">{{ $dayName }}</div>
                    @endforeach
                </div>

                @php
                    $day = 1;
                    $daysInMonth = $start->daysInMonth;
                    $totalCells = $firstDayOfWeek - 1 + $daysInMonth;
                    $rows = (int) ceil($totalCells / 7);
                @endphp

                @for ($row = 0; $row < $rows; $row++)
                    <div class="grid grid-cols-7 gap-1">
                        @for ($col = 0; $col < 7; $col++)
                            @php
                                $cellIndex = ($row * 7) + $col;
                                $inMonth = $cellIndex >= ($firstDayOfWeek - 1) && $day <= $daysInMonth;
                                $dateKey = $inMonth ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
                                $dayItems = $inMonth ? ($attendances[$dateKey] ?? collect()) : collect();
                            @endphp
                            <div class="min-h-24 border border-gray-100 rounded p-1 @if($inMonth) bg-white @else bg-gray-50 @endif">
                                @if ($inMonth)
                                    <div class="text-xs font-semibold text-gray-500 mb-1">{{ $day }}</div>
                                    @foreach ($dayItems as $att)
                                        @php
                                            $progName = $att->enrollment?->program?->name ?? 'Unknown';
                                            if (!isset($programColors[$progName])) {
                                                $programColors[$progName] = $colors[$colorIdx % count($colors)];
                                                $colorIdx++;
                                            }
                                        @endphp
                                        <div class="text-xs rounded px-1 py-0.5 mb-0.5 text-white truncate" style="background-color: {{ $programColors[$progName] }}">
                                            {{ $progName }}
                                        </div>
                                    @endforeach
                                    @php $day++; @endphp
                                @endif
                            </div>
                        @endfor
                    </div>
                @endfor

                @if (!empty($programColors))
                    <div class="mt-4 flex flex-wrap gap-3 text-xs">
                        @foreach ($programColors as $progName => $color)
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full inline-block" style="background-color: {{ $color }}"></span>
                                {{ $progName }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">Riwayat Les</h3>
                    <p class="mt-2 text-sm text-gray-600">Lihat daftar les, guru, dan total pertemuan.</p>
                    <div class="mt-4">
                        <a href="{{ route('murid.history.index') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Buka Riwayat</a>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">Tagihan</h3>
                    <p class="mt-2 text-sm text-gray-600">Cek total tagihan dan status pembayaran.</p>
                    <div class="mt-4">
                        <a href="{{ route('murid.billing.index') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Lihat Tagihan</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
