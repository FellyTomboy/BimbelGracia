<x-app-layout>
    <x-slot name="title">Presensi Kelas</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Presensi Kelas</h2>
            <p class="text-sm text-gray-500 mt-0.5">Isi daftar murid yang hadir untuk setiap sesi kelas</p>
        </div>
    </x-slot>

    <div x-data="classAttendanceModal({})">

        {{-- Fill Students Modal ─────────────────────────────────────────────── --}}
        <div x-show="modalOpen"
            x-on:cls-att-submit.window="submitModal()"
            x-on:cls-att-close.window="close()"
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
                class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">

                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-gray-900 font-semibold text-base" x-text="modalBody.match(/<p class=\"text-xs text-gray-500 mt-1\">([^<]+)/)?.[1] || 'Isi Murid Hadir'">Isi Murid Hadir</h3>
                    <button @click="close()" class="text-gray-400 hover:text-gray-600 transition-colors">
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
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
                @if (session('status'))
                    <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">{{ session('status') }}</div>
                @endif

                {{-- Filter --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <form method="GET" class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Bulan</label>
                            <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="w-full sm:w-20 rounded-md border-gray-300 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Tahun</label>
                            <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="w-full sm:w-24 rounded-md border-gray-300 text-sm" required />
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm">Terapkan</button>
                    </form>
                </div>

                {{-- Attendance List --}}
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pr-4">Tanggal</th>
                                    <th class="py-2 pr-4">Program</th>
                                    <th class="py-2 pr-4">Guru</th>
                                    <th class="py-2 pr-4">Murid Hadir</th>
                                    <th class="py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($attendances as $attendance)
                                    <tr>
                                        <td class="py-2 pr-4 whitespace-nowrap">{{ $attendance->lesson_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="py-2 pr-4">{{ $attendance->enrollment?->program?->name ?? '-' }}</td>
                                        <td class="py-2 pr-4">{{ $attendance->enrollment?->teacher?->displayName ?? '-' }}</td>
                                        <td class="py-2 pr-4">
                                            @if ($attendance->students->isNotEmpty())
                                                <span class="text-emerald-600 font-medium">{{ $attendance->students->count() }} murid</span>
                                                <span class="text-gray-400 text-xs">({{ $attendance->students->pluck('display_name')->implode(', ') }})</span>
                                            @else
                                                <span class="text-amber-600 italic">Belum diisi</span>
                                            @endif
                                        </td>
                                        <td class="py-2">
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                    @click="openFillModal({{ $attendance->id }})"
                                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                    {{ $attendance->students->isNotEmpty() ? 'Edit' : 'Isi Murid' }}
                                                </button>
                                                <button type="button"
                                                    onclick="openDeleteClassAttModal({{ $attendance->id }}, '{{ addslashes($attendance->enrollment?->program?->name ?? '-') }}', '{{ $attendance->lesson_date?->format('d/m/Y') ?? '-' }}')"
                                                    class="text-rose-600 hover:text-rose-900 text-sm font-medium">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-gray-400">Belum ada presensi kelas untuk periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal ─────────────────────────────────────────── --}}
    <div id="delete-class-att-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center p-4"
         style="background:rgba(0,0,0,.25);backdrop-filter:blur(2px)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-4 bg-rose-600 flex items-center justify-between rounded-t-2xl">
                <h3 class="text-white font-semibold text-base">Hapus Presensi Kelas?</h3>
                <button onclick="closeDeleteClassAttModal()" class="text-white/70 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <p class="text-sm text-gray-600">Yakin ingin menghapus presensi kelas berikut?</p>
                <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-rose-600">Tanggal</span>
                        <span class="font-medium text-rose-900" id="dcam-date"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-rose-600">Program</span>
                        <span class="font-medium text-rose-900" id="dcam-program"></span>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <p class="text-sm text-amber-700">Tindakan ini tidak bisa dibatalkan.</p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button onclick="closeDeleteClassAttModal()"
                        class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">Batal</button>
                    <button onclick="submitDeleteClassAtt()"
                        id="dcam-submit-btn"
                        class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 transition-colors">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let __dcamId = null;

        function openDeleteClassAttModal(id, program, date) {
            __dcamId = id;
            document.getElementById('dcam-date').textContent = date;
            document.getElementById('dcam-program').textContent = program;
            const modal = document.getElementById('delete-class-att-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteClassAttModal() {
            const modal = document.getElementById('delete-class-att-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            __dcamId = null;
        }

        async function submitDeleteClassAtt() {
            if (!__dcamId) return;
            const btn = document.getElementById('dcam-submit-btn');
            btn.disabled = true;
            btn.textContent = 'Menghapus...';
            try {
                await window.Ajax.delete('/admin/class-attendance/' + __dcamId);
                window.Toast?.success('Presensi kelas dihapus.');
                closeDeleteClassAttModal();
                setTimeout(() => location.reload(), 500);
            } catch (e) {
                btn.disabled = false;
                btn.textContent = 'Ya, Hapus';
            }
        }
    </script>
</x-app-layout>
