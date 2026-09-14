<x-app-layout>
    <x-slot name="title">Jadwal Kelas Bersama</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jadwal Kelas Bersama</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.class-student-sessions.index') }}" class="px-4 py-2 rounded-md border text-sm">Kalender</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="classSessionModal({})">

        {{-- Delete Confirmation Modal ─────────────────────────────────────────── --}}
        <div x-show="modalOpen"
            x-on:css-submit.window="submitModal()"
            x-on:css-close.window="close()"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="close()">

            <div x-show="modalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-rose-600 rounded-t-2xl">
                    <h3 class="text-white font-semibold text-base">Hapus Sesi Kelas?</h3>
                    <button @click="close()" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-5">
                    <div x-html="modalBody" class="space-y-0"></div>
                </div>
            </div>
        </div>

        {{-- Main Content ───────────────────────────────────────────────────── --}}
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    @if (empty($grouped))
                        <div class="text-center py-12 text-gray-400">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p>Belum ada presensi kelas</p>
                        </div>
                    @else
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 text-xs uppercase tracking-wide border-b">
                                    <th class="py-2 pr-4">Tanggal</th>
                                    <th class="py-2 pr-4">Program</th>
                                    <th class="py-2 pr-4">Guru</th>
                                    <th class="py-2 pr-4">Murid</th>
                                    <th class="py-2 pr-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($grouped as $row)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-3 pr-4 align-top text-gray-900 whitespace-nowrap">
                                            {{ $row['date']->format('d M Y') }}
                                        </td>
                                        <td class="py-3 pr-4 align-top">
                                            <x-hibernated-label :model="$row['program']" :label="$row['program']?->name ?? '-'" type="program" />
                                        </td>
                                        <td class="py-3 pr-4 align-top">
                                            @if ($row['teachers']->isNotEmpty())
                                                @foreach ($row['teachers'] as $t)
                                                    <x-hibernated-label :model="$t" :label="$t->displayName" type="guru" />{{ !$loop->last ? ', ' : '' }}
                                                @endforeach
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 align-top">
                                            @if ($row['students']->isNotEmpty())
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach ($row['students'] as $s)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                                            {{ $s->display_name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 align-top">
                                            <div class="flex items-center gap-1">
                                                <a href="{{ route('admin.class-student-sessions.edit', $row['class_session']) }}"
                                                   class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                                    Edit
                                                </a>
                                                <button type="button"
                                                    @click="openDeleteModal({{ $row['class_session']->id }})"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
