<x-app-layout>
    <x-slot name="title">Kalender Kelas</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kalender Kelas</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola kehadiran murid dan guru untuk setiap sesi kelas</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.class-student-sessions.table') }}" class="px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50 transition-colors">Tabel</a>
                <a href="{{ route('admin.class-student-sessions.create', ['month' => $month, 'year' => $year]) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Presensi
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            {{-- Month / Year Filter --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <form method="GET" action="{{ route('admin.class-student-sessions.index') }}" class="flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-700">Periode:</label>
                    <select name="month" class="border-gray-300 rounded-xl text-sm">
                        @foreach (['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $num => $label)
                            <option value="{{ $num }}" @selected($month == $num)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="w-24 border-gray-300 rounded-xl text-sm" />
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all">Terapkan</button>
                </form>
            </div>

            {{-- Calendar Grid --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                {{-- Day Header --}}
                <div class="grid grid-cols-7 border-b border-gray-100">
                    @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayName)
                        <div class="py-3 text-center text-sm font-semibold text-gray-500 border-r border-gray-100 last:border-r-0">{{ $dayName }}</div>
                    @endforeach
                </div>

                {{-- Calendar Cells --}}
                @php
                    $startOffset = $firstDayOfWeek - 1;
                    $totalCells = $startOffset + $daysInMonth;
                    $rows = (int) ceil($totalCells / 7);
                    $day = 1;

                    // Build sessionsByDate from ClassSession grouped by program + date
                    $sessionsByDate = [];
                    foreach ($sessions as $programId => $programSessions) {
                        foreach ($programSessions as $session) {
                            $dateKey = $session->session_date->format('Y-m-d');
                            if (!isset($sessionsByDate[$dateKey])) {
                                $sessionsByDate[$dateKey] = collect();
                            }
                            $sessionsByDate[$dateKey]->push($session);
                        }
                    }
                @endphp

                @for ($row = 0; $row < $rows; $row++)
                    <div class="grid grid-cols-7 border-b border-gray-100 last:border-b-0">
                        @for ($col = 0; $col < 7; $col++)
                            @php
                                $cellIndex = ($row * 7) + $col;
                                $inMonth = $cellIndex >= $startOffset && $day <= $daysInMonth;
                                $dateKey = $inMonth ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
                                $cellSessions = $inMonth ? ($sessionsByDate[$dateKey] ?? collect()) : collect();
                            @endphp
                            <div class="min-h-32 border-r border-gray-100 last:border-r-0 p-2 @if(!$inMonth) bg-gray-50 @endif">
                                @if ($inMonth)
                                    <div class="text-xs font-semibold text-gray-400 mb-2">{{ $day }}</div>
                                    <div class="space-y-2">
                                        @foreach ($cellSessions as $session)
                                            @php
                                                $teachers = $session->teachers;
                                                $students = $session->attendances->flatMap(fn($a) => $a->students)->unique('id')->values();
                                            @endphp
                                            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-2 text-xs">
                                                <div class="font-semibold text-indigo-700 mb-1">{{ $session->program->name }}</div>
                                                @if ($teachers->isNotEmpty())
                                                    <div class="flex flex-wrap gap-1 mb-1">
                                                        @foreach ($teachers as $t)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700">
                                                                {{ $t->displayName }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @if ($students->isNotEmpty())
                                                    <div class="text-gray-600 leading-tight">
                                                        {{ $students->take(3)->map->display_name->join(', ') }}{{ $students->count() > 3 ? ' +' . ($students->count() - 3) . ' lagi' : '' }}
                                                    </div>
                                                @endif
                                                <div class="flex items-center gap-2 mt-1.5">
                                                    <a href="{{ route('admin.class-student-sessions.edit', $session) }}"
                                                       class="text-[10px] text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>
                                                    <button type="button"
                                                            onclick="if(confirm('Hapus sesi {{ $session->session_date->format('d M Y') }}?')) { document.getElementById('delete-form-{{ $session->id }}').submit(); }"
                                                            class="text-[10px] text-rose-500 hover:text-rose-700 font-medium">Hapus</button>
                                                    <form id="delete-form-{{ $session->id }}" method="POST" action="{{ route('admin.class-student-sessions.destroy', $session) }}" class="hidden">
                                                        @csrf @method('DELETE')
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                        @if ($cellSessions->isEmpty())
                                            <div class="text-[10px] text-gray-300">-</div>
                                        @endif
                                    </div>
                                    @php $day++; @endphp
                                @endif
                            </div>
                        @endfor
                    </div>
                @endfor
            </div>

            {{-- Legend: Programs --}}
            @if (!empty($sessionsByDate))
                <div class="flex flex-wrap gap-3 text-xs">
                    @foreach ($programs as $program)
                        @if ($sessions->has($program->id))
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded bg-indigo-500 inline-block"></span>
                                {{ $program->name }}
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
