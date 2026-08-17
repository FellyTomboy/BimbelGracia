<x-app-layout>
    <x-slot name="title">Dashboard Guru</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Guru</h2>
            <p class="text-sm text-gray-500 mt-0.5">Selamat datang, {{ auth()->user()?->name ?? 'Guru' }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- KPI CARDS --}}
            @php
                $teacher = \App\Models\Teacher::where('user_id', auth()->id())->first();
                $teacherId = $teacher?->id ?? 0;
                $month = now()->month;
                $year = now()->year;
                $totalLes = \App\Models\MonthlyAttendance::whereHas('enrollment', fn($q) => $q->where('teacher_id', $teacherId))
                    ->whereMonth('lesson_date', $month)->whereYear('lesson_date', $year)->count();
                $accepted = \App\Models\MonthlyAttendance::whereHas('enrollment', fn($q) => $q->where('teacher_id', $teacherId))
                    ->where('status_validation', 'terima')->whereMonth('lesson_date', $month)->whereYear('lesson_date', $year)->count();
                $pending = \App\Models\MonthlyAttendance::whereHas('enrollment', fn($q) => $q->where('teacher_id', $teacherId))
                    ->where('status_validation', 'pending')->whereMonth('lesson_date', $month)->whereYear('lesson_date', $year)->count();
                $kpis = [
                    ['icon' => '📅', 'label' => 'Les Bulan Ini', 'value' => $totalLes, 'color' => 'from-blue-500 to-blue-600'],
                    ['icon' => '✅', 'label' => 'Diterima', 'value' => $accepted, 'color' => 'from-emerald-500 to-emerald-600'],
                    ['icon' => '⏳', 'label' => 'Pending', 'value' => $pending, 'color' => 'from-amber-500 to-amber-600'],
                    ['icon' => '💰', 'label' => 'Total Les', 'value' => $totalLes, 'color' => 'from-violet-500 to-violet-600'],
                ];
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ($kpis as $kpi)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $kpi['color'] }} flex items-center justify-center text-2xl shadow-sm shrink-0">
                            {{ $kpi['icon'] }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold text-gray-800">{{ $kpi['value'] }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $kpi['label'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- KALENDER LES --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Kalender Les Saya</h3>
                </div>
                <div class="p-5">
                    @php
                        $now = now();
                        $month = (int) request('month', $now->month);
                        $year = (int) request('year', $now->year);
                        $start = Carbon\Carbon::create($year, $month, 1);
                        $firstDayOfWeek = $start->dayOfWeekIso;

                        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'];
                        $programColors = [];

                        $attendances = \App\Models\MonthlyAttendance::with(['enrollment.program', 'students'])
                            ->whereHas('enrollment', fn ($q) => $q->where('teacher_id', $teacherId))
                            ->whereMonth('lesson_date', $month)
                            ->whereYear('lesson_date', $year)
                            ->orderBy('lesson_date')
                            ->get()
                            ->groupBy(fn ($a) => $a->lesson_date?->format('Y-m-d'));

                        $colorIdx = 0;
                    @endphp

                    <div class="flex items-center justify-between mb-4">
                        <a href="?month={{ $month - 1 < 1 ? 12 : $month - 1 }}&year={{ $month - 1 < 1 ? $year - 1 : $year }}" class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-700 transition-colors">&larr; Sebelumnya</a>
                        <span class="font-semibold text-gray-800">{{ $start->format('F Y') }}</span>
                        <a href="?month={{ $month + 1 > 12 ? 1 : $month + 1 }}&year={{ $month + 1 > 12 ? $year + 1 : $year }}" class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-700 transition-colors">Berikutnya &rarr;</a>
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
                                <div class="min-h-24 border border-gray-100 rounded-lg p-1 @if($inMonth) bg-white @else bg-gray-50 @endif">
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
                                            <div class="text-xs rounded-md px-1.5 py-0.5 mb-0.5 text-white truncate" style="background-color: {{ $programColors[$progName] }}" title="{{ $progName }}">
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
            </div>

            {{-- LAUNCHER CARD GRID --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">🚀 Menu Cepat</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <a href="{{ route('guru.presensi.create') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square">
                        <div class="text-5xl mb-2">📝</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Isi Presensi</div>
                    </a>
                    <a href="{{ route('guru.presensi.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square">
                        <div class="text-5xl mb-2">📋</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Riwayat Presensi</div>
                    </a>
                    <a href="{{ route('guru.history.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square">
                        <div class="text-5xl mb-2">📚</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Riwayat Les</div>
                    </a>
                    <a href="{{ route('guru.salary-projection.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square">
                        <div class="text-5xl mb-2">💰</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Proyeksi Gaji</div>
                    </a>
                    <a href="{{ route('guru.tawaran.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square">
                        <div class="text-5xl mb-2">🎯</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Tawaran Les</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>