<x-app-layout>
    <x-slot name="title">Presensi Les</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Presensi Les</h2>
            <p class="text-sm text-gray-500 mt-0.5">Daftar presensi les yang dicatat guru. Tolak jika ada data yang tidak sesuai.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm flex items-start gap-2">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.5 13A2 2 0 004.5 20h15a2 2 0 001.71-3.14l-7.5-13a2 2 0 00-3.42 0z"/></svg>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <form method="GET" action="{{ route('murid.history.index') }}" class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Bulan</label>
                        <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="w-full sm:w-20 rounded-xl border-gray-200 text-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tahun</label>
                        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="w-full sm:w-24 rounded-xl border-gray-200 text-sm" required />
                    </div>
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">Terapkan</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full align-middle">
                        <table class="min-w-full text-xs sm:text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 bg-gray-50/50">
                                    <th class="py-3 px-3 sm:px-4 font-medium">Program</th>
                                    <th class="py-3 px-3 sm:px-4 font-medium">Guru</th>
                                    <th class="py-3 px-3 sm:px-4 font-medium">Tanggal</th>
                                    <th class="py-3 px-3 sm:px-4 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($attendances as $attendance)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-3 sm:px-4">
                                            <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" />
                                        </td>
                                        <td class="py-3 px-3 sm:px-4">
                                            <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->displayName ?? '-'" type="guru" />
                                        </td>
                                        <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                            {{ $attendance->lesson_date?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        <td class="py-3 px-3 sm:px-4">
                                            @if ($attendance->status_validation === 'pending')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">Menunggu Validasi</span>
                                            @elseif ($attendance->status_validation === 'ditolak')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Ditolak Admin</span>
                                            @elseif ($attendance->parent_review_status === 'pending')
                                                <div class="flex flex-col gap-2">
                                                    <div class="flex flex-col gap-1">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Menunggu Admin</span>
                                                        <span class="text-xs text-gray-500">Penolakan sudah dikirim.</span>
                                                    </div>
                                                    <form method="POST" action="{{ route('murid.history.cancel-reject', $attendance) }}" onsubmit="return confirm('Batalkan penolakan presensi ini?')">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium hover:bg-gray-200 transition-colors w-full sm:w-auto">
                                                            Batalkan
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif ($attendance->parent_review_status === 'rejected')
                                                <div class="flex flex-col gap-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Ditolak</span>
                                                    <span class="text-xs text-gray-500">Hubungi admin.</span>
                                                </div>
                                            @elseif ($attendance->parent_review_status === 'dismissed')
                                                <div class="flex flex-col gap-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-700 border border-gray-200">Tdk Dikonfirmasi</span>
                                                    <span class="text-xs text-gray-500">Admin tidak konfirmasi.</span>
                                                </div>
                                            @else
                                                <button type="button" onclick="openRejectModal({{ $attendance->id }})" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-medium hover:bg-rose-700 transition-colors w-full sm:w-auto">
                                                    Tolak
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <x-empty-state icon="📚" title="Belum ada riwayat" description="Belum ada data les untuk periode ini." />
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

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeRejectModal()"></div>
            <div class="relative inline-block bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full mx-4">
                <form id="rejectForm" method="POST" action="">
                    @csrf
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-rose-100">
                                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.5 13A2 2 0 004.5 20h15a2 2 0 001.71-3.14l-7.5-13a2 2 0 00-3.42 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900" id="modal-title">Tolak Presensi</h3>
                                <p class="text-sm text-gray-500 mt-1">Anda akan menolak presensi ini. Berikan alasan penolakan agar admin dapat memprosesnya.</p>
                                <div class="mt-4">
                                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan <span class="text-rose-500">*</span></label>
                                    <textarea
                                        name="rejection_reason"
                                        id="rejection_reason"
                                        rows="4"
                                        class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"
                                        placeholder="Contoh: Saya tidak hadir pada tanggal tersebut..."
                                        required
                                        maxlength="1000"
                                    ></textarea>
                                    <p class="text-xs text-gray-400 mt-1">Maksimal 1000 karakter</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex flex-col sm:flex-row items-center justify-end gap-3 rounded-b-2xl">
                        <button type="button" onclick="closeRejectModal()" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-white text-gray-700 text-sm font-medium border border-gray-200 hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-medium hover:bg-rose-700 transition-colors">
                            Kirim Penolakan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRejectModal(attendanceId) {
            document.getElementById('rejectForm').action = '{{ route("murid.history.reject", "REPLACE_ID") }}'.replace('REPLACE_ID', attendanceId);
            document.getElementById('rejectModal').classList.remove('hidden');
            document.getElementById('rejection_reason').value = '';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRejectModal();
            }
        });
    </script>
</x-app-layout>