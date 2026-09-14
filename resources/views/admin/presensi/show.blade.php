<x-app-layout>
    <x-slot name="title">Detail Presensi</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Presensi</h2>
            <a href="{{ route('admin.presensi.index') }}" class="text-sm text-gray-500">Kembali</a>
        </div>
    </x-slot>

    <div x-data="attendanceDetailModal({})">

        {{-- Modal (for validate/fix-enrollment/delete) ─────────────────────── --}}
        <div x-show="modalOpen"
            x-on:att-detail-submit.window="submitModal()"
            x-on:att-detail-close.window="close()"
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

                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-gray-900 font-semibold text-base" x-text="modalTitle">Konfirmasi</h3>
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

        {{-- Uphold Rejection Modal (rose-themed, separate) ─────────────────── --}}
        <div x-show="upholdModalOpen"
            x-on:att-uphold-submit.window="submitUphold()"
            x-on:att-uphold-close.window="closeUphold()"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="closeUphold()">

            <div x-show="upholdModalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                <div class="px-5 py-4 bg-rose-600 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-white font-semibold text-base">Konfirmasi Penolakan Presensi</h3>
                    <button @click="closeUphold()" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-5">
                    <div x-html="upholdModalBody" class="space-y-0"></div>
                </div>
            </div>
        </div>

        {{-- Main Content ───────────────────────────────────────────────────── --}}
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                @if (session('status'))
                    <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                    <p><span class="font-semibold">Tanggal Les:</span> {{ $attendance->lesson_date?->format('d M Y') ?? '-' }}</p>
                    <p><span class="font-semibold">Program:</span> <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" /></p>
                    <p><span class="font-semibold">Guru:</span> <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->displayName ?? '-'" type="guru" /></p>
                    <p><span class="font-semibold">Murid:</span>
                        @if ($attendance->students->count() > 0)
                            @foreach ($attendance->students as $student)
                                <x-hibernated-label :model="$student" :label="$student->display_name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                            @endforeach
                        @else
                            -
                        @endif
                    </p>
                    <p><span class="font-semibold">Enrollment:</span> #{{ $attendance->enrollment_id }}</p>
                    <p><span class="font-semibold">Catatan:</span> {{ $attendance->notes ?? '-' }}</p>
                    <p>
                        <span class="font-semibold">Status:</span>
                        <span data-status-badge
                            @if ($attendance->status_validation === 'terima')
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200"
                            @elseif ($attendance->status_validation === 'terlambat')
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200"
                            @elseif ($attendance->status_validation === 'ditolak')
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200"
                            @else
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200"
                            @endif>
                            @if ($attendance->status_validation === 'terima')Diterima
                            @elseif ($attendance->status_validation === 'terlambat')Terlambat (potongan 10%)
                            @elseif ($attendance->status_validation === 'ditolak')Ditolak
                            @else Pending
                            @endif
                        </span>
                    </p>
                    <p><span class="font-semibold">Notifikasi Ortu:</span>
                        @if ($attendance->parent_review_status === 'pending')
                            <span class="text-amber-600 font-semibold">Menunggu konfirmasi</span>
                        @elseif ($attendance->parent_review_status === 'rejected')
                            <span class="text-rose-600 font-semibold">Sudah dikonfirmasi ditolak</span>
                        @elseif ($attendance->parent_review_status === 'dismissed')
                            <span class="text-gray-500 font-semibold">Tidak dikonfirmasi</span>
                        @else
                            <span class="text-gray-500 font-semibold">-</span>
                        @endif
                    </p>

                    @if ($attendance->image)
                        <div>
                            <p class="font-semibold">Foto Bukti:</p>
                            <img src="{{ asset('storage/' . $attendance->image) }}" class="mt-2 max-w-md rounded-md border" alt="Bukti presensi" />
                        </div>
                    @endif

                    <div>
                        <p class="font-semibold">Kehadiran per Murid:</p>
                        <ul class="mt-2 list-disc pl-5 text-sm text-gray-700">
                            @forelse ($attendance->students as $student)
                                <li>
                                    <x-hibernated-label :model="$student" :label="$student->display_name" type="murid privat" />:
                                    {{ $student->pivot->total_present ? 'Hadir' : 'Tidak Hadir' }}
                                </li>
                            @empty
                                <li>-</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                {{-- Perbaiki Enrollment ─────────────────────────────────── --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Perbaiki Enrollment</h3>
                    <p class="text-xs text-gray-500 mb-3">Mengubah enrollment akan mengganti data murid dan mengembalikan status ke pending.</p>
                    <button type="button"
                        @click="openFixEnrollmentModal({{ $attendance->id }})"
                        class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-colors">
                        Ubah Enrollment
                    </button>
                </div>

                {{-- Validasi ─────────────────────────────────────────── --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Validasi</h3>
                    <button type="button"
                        @click="openValidateModal({{ $attendance->id }})"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                        Ubah Status Validasi
                    </button>
                </div>

                {{-- Konfirmasi Penolakan Ortu ─────────────────────────── --}}
                @if ($attendance->parent_review_status === 'pending')
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4 border border-amber-200">
                        <div>
                            <h3 class="font-semibold text-gray-900">Konfirmasi Penolakan Ortu</h3>
                            <p class="text-sm text-gray-500 mt-1">Orangtua sudah menolak presensi ini. Pilih salah satu tindakan di bawah untuk memutuskan status final.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button type="button"
                                @click="openUpholdModal({{ $attendance->id }})"
                                class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-medium hover:bg-rose-700">
                                Konfirmasi Ditolak
                            </button>
                            <button type="button"
                                @click="dismissFromShow({{ $attendance->id }})"
                                class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                Tidak Dikonfirmasi
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Hapus ─────────────────────────────────────────────── --}}
                @if ($attendance->status_validation !== 'ditolak')
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <button type="button"
                            @click="openDeleteModal({{ $attendance->id }})"
                            class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-medium hover:bg-rose-700">
                            Hapus Presensi
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Inline scripts for parent rejection (outside x-data scope) ─────────── --}}
    <script>
        function dismissFromShow(id) {
            if (!confirm('Tolak konfirmasi penolakan ini? Status presensi tidak akan diubah.')) return;
            window.Ajax.post('/admin/notifikasi-presensi/' + id + '/dismiss')
                .then(() => {
                    window.Toast?.success('Penolakan dibatalkan.');
                    setTimeout(() => location.reload(), 500);
                });
        }
    </script>
</x-app-layout>
