<x-app-layout>
    <x-slot name="title">Notifikasi Presensi</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifikasi Presensi</h2>
            <p class="text-sm text-gray-500 mt-0.5">Penolakan presensi dari orangtua yang menunggu konfirmasi admin.</p>
        </div>
    </x-slot>

    <div x-data="attendanceValidationModal({})">

        {{-- Modal ─────────────────────────────────────────────────────────────── --}}
        <div x-show="modalOpen"
            x-on:att-val-submit.window="submitUphold()"
            x-on:att-val-close.window="close()"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="close()">

            {{-- Backdrop ───────────────────────────────────────────────────── --}}
            <div x-show="modalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm"
                @click="close()">
            </div>

            {{-- Panel ──────────────────────────────────────────────────────── --}}
            <div x-show="modalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                {{-- Header ────────────────────────────────────────────────── --}}
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-rose-600 rounded-t-2xl">
                    <h3 class="text-white font-semibold text-base" x-text="modalTitle">Konfirmasi Ditolak</h3>
                    <button @click="close()" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body ───────────────────────────────────────────────────── --}}
                <div class="p-5">
                    <div x-html="modalBody" class="space-y-0"></div>
                </div>
            </div>
        </div>

        {{-- Main Content ───────────────────────────────────────────────────── --}}
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                @if (session('status'))
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="font-semibold text-gray-900">Antrian Konfirmasi</h3>
                        <p class="text-sm text-gray-500 mt-1">Tentukan apakah penolakan orangtua dikonfirmasi atau tidak.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 bg-gray-50/50">
                                    <th class="py-3 px-4 font-medium">Tanggal Les</th>
                                    <th class="py-3 px-4 font-medium">Program</th>
                                    <th class="py-3 px-4 font-medium">Guru</th>
                                    <th class="py-3 px-4 font-medium">Murid</th>
                                    <th class="py-3 px-4 font-medium">Alasan Penolakan</th>
                                    <th class="py-3 px-4 font-medium">Diajukan</th>
                                    <th class="py-3 px-4 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($attendances as $attendance)
                                    <tr class="hover:bg-gray-50/50 transition-colors align-top"
                                        data-attendance-id="{{ $attendance->id }}">
                                        <td class="py-3 px-4 font-medium text-gray-900">{{ $attendance->lesson_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="py-3 px-4">
                                            <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" />
                                        </td>
                                        <td class="py-3 px-4">
                                            <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->displayName ?? '-'" type="guru" />
                                        </td>
                                        <td class="py-3 px-4">
                                            @if ($attendance->students->count() > 0)
                                                @foreach ($attendance->students as $student)
                                                    <x-hibernated-label :model="$student" :label="$student->display_name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                                                @endforeach
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-600 max-w-xs">
                                            <p class="text-sm">{{ $attendance->parent_rejection_reason ?? '-' }}</p>
                                        </td>
                                        <td class="py-3 px-4 text-gray-500 whitespace-nowrap">
                                            {{ $attendance->parent_reviewed_at?->format('d M Y H:i') ?? '-' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button"
                                                    @click="openUpholdModal({{ $attendance->id }})"
                                                    class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-medium hover:bg-rose-700">
                                                    Konfirmasi Ditolak
                                                </button>
                                                <button type="button"
                                                    @click="dismiss({{ $attendance->id }})"
                                                    class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium hover:bg-gray-200">
                                                    Tidak Dikonfirmasi
                                                </button>
                                                <a href="{{ route('admin.presensi.show', $attendance) }}"
                                                    class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-medium hover:bg-indigo-100">Detail</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-10">
                                            <x-empty-state icon="🔔" title="Tidak ada notifikasi" description="Belum ada penolakan presensi yang menunggu konfirmasi." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
