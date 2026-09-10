<x-app-layout>
    <x-slot name="title">Mismatch Program Enrollment</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Enrollment', 'url' => route('admin.enrollments.index')], ['label' => 'Mismatch Program']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mismatch Program Enrollment</h2>
                <p class="text-sm text-gray-500 mt-0.5">Enrollment yang program-nya tidak sesuai dengan jenjang kelas siswa</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-200 hover:border-slate-400 transition-all">
                    ← Kembali ke Enrollment
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="{
             tab: '{{ $activeTab }}',
             flashMessage: {{ \Illuminate\Support\Js::from(session('status') ?? '') }},
             showFlash: {{ \Illuminate\Support\Js::from((bool) session('status')) }},
             flashTimer: null,
             init() { if (this.showFlash) this._startTimer(); },
             _startTimer() {
                 if (this.flashTimer) clearTimeout(this.flashTimer);
                 this.showFlash = true;
                 this.flashTimer = setTimeout(() => { this.showFlash = false; }, 4000);
             },
             setFlash(msg) { this.flashMessage = msg; this._startTimer(); },
         }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Banner --}}
            <div x-show="showFlash"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="flashMessage"></span>
                <button @click="showFlash = false" class="ml-auto text-emerald-500 hover:text-emerald-700 font-bold text-lg leading-none">&times;</button>
            </div>

            {{-- Tab Buttons --}}
            <div class="flex items-center gap-1 mb-4 bg-white rounded-2xl p-1 shadow-sm border border-gray-100 w-fit">
                <button @click="tab = 'kelas'"
                        class="px-5 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2"
                        :class="tab === 'kelas' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                    Kelas
                    @if ($allKelasCount > 0)
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold"
                              :class="tab === 'kelas' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700'">
                            {{ $allKelasCount }}
                        </span>
                    @endif
                </button>
                <button @click="tab = 'privat'"
                        class="px-5 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2"
                        :class="tab === 'privat' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                    Privat
                    @if ($allPrivatCount > 0)
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold"
                              :class="tab === 'privat' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700'">
                            {{ $allPrivatCount }}
                        </span>
                    @endif
                </button>
            </div>

            <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl text-sm">
                <strong>Catatan:</strong> Mismatch terjadi saat (a) siswa sudah naik jenjang tapi enrollment belum ter-update, atau (b) enrollment berisi siswa dengan jenjang berbeda (mix). Sistem auto-sync enrollment tiap 1 Juli akan mengubah program untuk kasus (a). Untuk kasus (b), admin perlu pilih program Mix (jika sudah ditambahkan) atau update manual.
            </div>

            {{-- KELAS TABLE --}}
            <div x-show="tab === 'kelas'" x-cloak>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 bg-gray-50/50">
                                    <th class="py-3 px-4 font-medium">#</th>
                                    <th class="py-3 px-4 font-medium">Murid</th>
                                    <th class="py-3 px-4 font-medium">Kelas Siswa</th>
                                    <th class="py-3 px-4 font-medium">Program Saat Ini</th>
                                    <th class="py-3 px-4 font-medium">Jenjang Expected</th>
                                    <th class="py-3 px-4 font-medium">Alasan</th>
                                    <th class="py-3 px-4 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($allKelas as $i => $enrollment)
                                    @php
                                        $jenjangs = $enrollment->students
                                            ->map(fn ($s) => \App\Helpers\JenjangMatcher::fromGrade($s->kelas))
                                            ->filter()
                                            ->unique()
                                            ->values()
                                            ->all();
                                        $expectedNames = collect($jenjangs)->map(function ($j) use ($enrollment) {
                                            return \App\Helpers\JenjangMatcher::expectedProgramName(
                                                (string) ($enrollment->type ?? 'kelas'),
                                                $j,
                                                \App\Helpers\JenjangMatcher::isOnline($enrollment->program),
                                            );
                                        });
                                        $reason = count($jenjangs) > 1
                                            ? 'Mix (jenjang siswa berbeda)'
                                            : 'Belum auto-sync (jenjang siswa sudah pindah)';
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-4 text-gray-400">{{ $i + 1 }}</td>
                                        <td class="py-3 px-4 font-medium text-gray-900">
                                            {{ $enrollment->students->map->display_name->implode(', ') ?: '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-gray-600">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($enrollment->students as $student)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                                        {{ $student->display_name }} · Kelas {{ $student->kelas ?? '—' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                                {{ $enrollment->program?->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($expectedNames as $name)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        {{ $name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-xs text-gray-500">{{ $reason }}</td>
                                        <td class="py-3 px-4">
                                            <a href="{{ route('admin.enrollments.edit', $enrollment) }}"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                                Edit Manual
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-12">
                                            <x-empty-state icon="✅" title="Tidak ada mismatch kelas" description="Semua enrollment kelas program-nya sesuai." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- PRIVAT TABLE --}}
            <div x-show="tab === 'privat'" x-cloak>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 bg-gray-50/50">
                                    <th class="py-3 px-4 font-medium">#</th>
                                    <th class="py-3 px-4 font-medium">Murid</th>
                                    <th class="py-3 px-4 font-medium">Kelas Siswa</th>
                                    <th class="py-3 px-4 font-medium">Program Saat Ini</th>
                                    <th class="py-3 px-4 font-medium">Jenjang Expected</th>
                                    <th class="py-3 px-4 font-medium">Alasan</th>
                                    <th class="py-3 px-4 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($allPrivat as $i => $enrollment)
                                    @php
                                        $jenjangs = $enrollment->students
                                            ->map(fn ($s) => \App\Helpers\JenjangMatcher::fromGrade($s->kelas))
                                            ->filter()
                                            ->unique()
                                            ->values()
                                            ->all();
                                        $expectedNames = collect($jenjangs)->map(function ($j) use ($enrollment) {
                                            return \App\Helpers\JenjangMatcher::expectedProgramName(
                                                (string) ($enrollment->type ?? 'privat'),
                                                $j,
                                                \App\Helpers\JenjangMatcher::isOnline($enrollment->program),
                                            );
                                        });
                                        $reason = count($jenjangs) > 1
                                            ? 'Mix (jenjang siswa berbeda)'
                                            : 'Belum auto-sync (jenjang siswa sudah pindah)';
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-4 text-gray-400">{{ $i + 1 }}</td>
                                        <td class="py-3 px-4 font-medium text-gray-900">
                                            {{ $enrollment->students->map->display_name->implode(', ') ?: '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-gray-600">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($enrollment->students as $student)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                                        {{ $student->display_name }} · Kelas {{ $student->kelas ?? '—' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                                {{ $enrollment->program?->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($expectedNames as $name)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        {{ $name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-xs text-gray-500">{{ $reason }}</td>
                                        <td class="py-3 px-4">
                                            <a href="{{ route('admin.enrollments.edit', $enrollment) }}"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                                Edit Manual
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-12">
                                            <x-empty-state icon="✅" title="Tidak ada mismatch privat" description="Semua enrollment privat program-nya sesuai." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <style>[x-cloak] { display: none !important; }</style>
    </div>
</x-app-layout>
