<x-app-layout>
    <x-slot name="title">Kalender Kelas</x-slot>

    <div x-data="{
        ...classPresensiModal({
            createUrl: '{{ route('admin.class-student-sessions.create-form') }}',
            storeUrl: '{{ route('admin.class-student-sessions.store') }}',
            editUrl: (id) => `/admin/class-student-sessions/${id}/form`,
            updateUrl: (id) => `/admin/class-student-sessions/${id}`,
            deleteUrl: (id) => `/admin/class-student-sessions/${id}`,
            listSelector: 'table',
        }),
        openCreateWithDate(date, programId) {
            const params = new URLSearchParams();
            if (date) params.set('session_date', date);
            if (programId) params.set('program_id', programId);
            const query = params.toString();
            const baseUrl = typeof this.createUrl === 'function' ? this.createUrl() : this.createUrl;
            const url = query ? `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}${query}` : baseUrl;
            const orig = this.createUrl;
            this.createUrl = url;
            this.openCreate().finally(() => { this.createUrl = orig; });
        },
    }"
    @open-create-modal.window="openCreate()"
    @open-edit-modal.window="openEdit($event.detail)"
    @open-delete-modal.window="confirmDelete($event.detail)">

        <x-slot name="header">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kalender Kelas</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Kelola kehadiran murid dan guru untuk setiap sesi kelas</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.class-student-sessions.table') }}" class="px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50 transition-colors">Tabel</a>
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-create-modal'))"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl {{ $selectedProgramId ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }} text-sm font-medium transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Presensi
                    </button>
                </div>
            </div>
        </x-slot>

        {{-- Create/Edit Modal ─────────────────────────────────────────── --}}
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background:rgba(0,0,0,.25);backdrop-filter:blur(2px)"
             @keydown.escape.window="close()">

            <div x-show="modalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">

                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-indigo-600 rounded-t-2xl">
                    <h3 class="text-white font-semibold text-base" x-text="modalTitle">Tambah Presensi Kelas</h3>
                    <button @click="close()" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-5 max-h-[70vh] overflow-y-auto">
                    <div x-html="modalBody" class="space-y-0"></div>
                </div>
            </div>
        </div>

        {{-- Delete Confirmation ─────────────────────────────────────────── --}}
        <div x-show="deleteConfirmId !== null"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background:rgba(0,0,0,.25);backdrop-filter:blur(2px)"
             @keydown.escape.window="deleteConfirmId = null">

            <div x-show="deleteConfirmId !== null"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-rose-600 rounded-t-2xl">
                    <h3 class="text-white font-semibold text-base">Hapus Sesi Kelas?</h3>
                    <button @click="deleteConfirmId = null" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-5">
                    <p class="text-sm text-gray-600 mb-4">Tindakan ini tidak bisa dibatalkan.</p>
                    <div class="flex justify-end gap-3">
                        <button @click="deleteConfirmId = null"
                                class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                            Batal
                        </button>
                        <button @click="deleteRow()"
                                :disabled="deleteLoading"
                                class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 transition-colors disabled:opacity-50">
                            <span x-text="deleteLoading ? 'Menghapus...' : 'Ya, Hapus'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content ───────────────────────────────────────────────────── --}}
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
                @if (session('status'))
                    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ session('status') }}
                    </div>
                @endif

                {{-- Month / Year / Program Filter --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <form method="GET" action="{{ route('admin.class-student-sessions.index') }}" class="flex flex-wrap items-center gap-3">
                        <select name="month" class="border-gray-300 rounded-xl text-sm">
                            @foreach (['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $num => $label)
                                <option value="{{ $num }}" @selected($month == $num)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="w-24 border-gray-300 rounded-xl text-sm" />
                        <select name="program_id" class="border-gray-300 rounded-xl text-sm">
                            <option value="">Semua Program</option>
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}" @selected($selectedProgramId == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
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

                    @php
                        $paletteFallback = array_values($programPalette);

                        function programColor($session, $palette, $fallback) {
                            if (!$session || !$session->program) return $fallback[0];
                            $id = (int) $session->program_id;
                            return $palette[$id] ?? $fallback[($id - 1) % count($fallback)];
                        }

                        function programColorForProgram($program, $palette, $fallback) {
                            if (!$program) return $fallback[0];
                            $id = (int) $program->id;
                            return $palette[$id] ?? $fallback[($id - 1) % count($fallback)];
                        }
                    @endphp

                    @php
                        $startOffset = $firstDayOfWeek - 1;
                        $totalCells = $startOffset + $daysInMonth;
                        $rows = (int) ceil($totalCells / 7);
                        $day = 1;

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
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="text-xs font-semibold text-gray-400">{{ $day }}</div>
                                            <button type="button"
                                                    @click="openCreateWithDate('{{ $dateKey }}', {{ $selectedProgramId ?? 'null' }})"
                                                    class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 hover:bg-indigo-600 hover:text-white flex items-center justify-center text-xs font-bold transition-colors"
                                                    title="{{ $selectedProgramId ? 'Tambah presensi' : 'Pilih program dulu' }}">
                                                +
                                            </button>
                                        </div>
                                        <div class="space-y-2">
                                            @foreach ($cellSessions as $session)
                                                @php
                                                    $teachers = $session->teachers;
                                                    $students = $session->attendances->flatMap(fn($a) => $a->students)->unique('id')->values();
                                                    $c = programColor($session, $programPalette, $paletteFallback);
                                                @endphp
                                                <div class="rounded-lg border p-2 text-xs" style="background-color: {{ $c['bg'] }}; border-color: {{ $c['border'] }};">
                                                    <div class="font-semibold text-gray-800 mb-1">{{ $session->program->name }}</div>
                                                    @if ($teachers->isNotEmpty())
                                                        <div class="flex flex-wrap gap-1 mb-1">
                                                            @foreach ($teachers as $t)
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium border bg-white text-gray-700" style="border-color: {{ $c['chip_border'] }};">
                                                                    {{ $t->displayName }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @if ($students->isNotEmpty())
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach ($students as $s)
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium border border-gray-300 bg-white text-gray-600">
                                                                    {{ $s->display_name }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    <div class="flex items-center gap-2 mt-1.5">
                                                        <button type="button"
                                                                @click="openEdit({{ $session->id }})"
                                                                class="text-[10px] text-purple-600 hover:text-purple-800 font-medium">Edit</button>
                                                        <button type="button"
                                                                onclick="window.dispatchEvent(new CustomEvent('open-delete-modal', {detail: {{ $session->id }}}))"
                                                                class="text-[10px] text-rose-500 hover:text-rose-700 font-medium">Hapus</button>
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
                                @php $c = programColorForProgram($program, $programPalette, $paletteFallback); @endphp
                                <span class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded inline-block" style="background-color: {{ $c['dot'] }};"></span>
                                    {{ $program->name }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>
