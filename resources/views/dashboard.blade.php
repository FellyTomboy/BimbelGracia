<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <p class="text-sm text-gray-500">Pilih tindakan yang paling sering Anda pakai.</p>
        </div>
    </x-slot>

    <div class="py-12">
        @php
            $role = auth()->user()?->role?->value;
        @endphp

        {{-- ADMIN: Modal-based 5-tile dashboard --}}
        @if ($role === 'admin')
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-data-master')">
                        <img src="{{ asset('storage/icons/icon-data-master.png') }}" alt="Data Master" class="w-16 h-16 mb-3 object-contain">
                        <div class="text-sm font-semibold text-gray-700 text-center">Data Master</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-presensi')">
                        <img src="{{ asset('storage/icons/icon-presensi.png') }}" alt="Presensi" class="w-16 h-16 mb-3 object-contain">
                        <div class="text-sm font-semibold text-gray-700 text-center">Presensi</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-tagihan')">
                        <img src="{{ asset('storage/icons/icon-tagihan.png') }}" alt="Tagihan" class="w-16 h-16 mb-3 object-contain">
                        <div class="text-sm font-semibold text-gray-700 text-center">Tagihan</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-laporan')">
                        <img src="{{ asset('storage/icons/icon-laporan.png') }}" alt="Laporan" class="w-16 h-16 mb-3 object-contain">
                        <div class="text-sm font-semibold text-gray-700 text-center">Laporan</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="window.location.href='{{ route('admin.bank-accounts.index') }}'">
                        <img src="{{ asset('storage/icons/icon-bank.png') }}" alt="Rekening Bimbel" class="w-16 h-16 mb-3 object-contain">
                        <div class="text-sm font-semibold text-gray-700 text-center">Rekening Bimbel</div>
                    </div>
                </div>
            </div>

            {{-- MODAL DATA MASTER --}}
            <div id="modal-data-master" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-data-master')">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold">Data Master</h3>
                        <button onclick="closeModal('modal-data-master')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                    </div>
                    <a href="{{ route('admin.parents.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-parent.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Parent</a>
                    <a href="{{ route('admin.students.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-murid.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Murid</a>
                    <a href="{{ route('admin.teachers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-guru.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Guru</a>
                    <a href="{{ route('admin.programs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-program.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Program</a>
                    <a href="{{ route('admin.enrollments.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-enrollment.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Enrollment</a>
                    <a href="{{ route('admin.lesson-offers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-tawaran.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Tawaran Les</a>
                    <a href="{{ route('admin.documents.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-dokumen.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Dokumen</a>
                    <a href="{{ route('admin.discounts.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-diskon.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Diskon/Promo</a>
                    <a href="{{ route('admin.new-students.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-pendaftar-murid.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Pendaftar Murid Baru</a>
                    <a href="{{ route('admin.teacher-registrants.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-pendaftar-guru.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Pendaftar Guru Baru</a>
                </div>
            </div>

            {{-- MODAL PRESENSI --}}
            <div id="modal-presensi" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-presensi')">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold">Presensi</h3>
                        <button onclick="closeModal('modal-presensi')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                    </div>
                    <a href="{{ route('admin.presensi.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-validasi-presensi.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Validasi Presensi Privat</a>
                    <a href="{{ route('admin.class-student-sessions.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-kelas.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Presensi & Jadwal Kelas</a>
                </div>
            </div>

            {{-- MODAL TAGIHAN --}}
            <div id="modal-tagihan" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-tagihan')">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold">Tagihan</h3>
                        <button onclick="closeModal('modal-tagihan')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                    </div>
                    <a href="{{ route('admin.analysis.ortu') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-wa-ortu.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Template WA Ortu</a>
                    <a href="{{ route('admin.analysis.guru') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-wa-guru.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Template WA Guru</a>
                    <a href="{{ route('admin.payments.ortu') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-bayar-ortu.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Pembayaran Ortu</a>
                    <a href="{{ route('admin.payments.guru') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-bayar-guru.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Pembayaran Guru</a>
                </div>
            </div>

            {{-- MODAL LAPORAN --}}
            <div id="modal-laporan" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-laporan')">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold">Laporan</h3>
                        <button onclick="closeModal('modal-laporan')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                    </div>
                    <a href="{{ route('admin.class-reports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-laporan-kelas.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Laporan Kelas</a>
                    <a href="{{ route('admin.history.students') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-riwayat-admin-line.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Riwayat</a>
                    <a href="{{ route('admin.finance.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-keuangan.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Keuangan</a>
                    <a href="{{ route('admin.export.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors"><img src="{{ asset('storage/icons/icon-export.png') }}" alt="" class="w-5 h-5 object-contain shrink-0">Export & Backup</a>
                </div>
            </div>

            <style>
                @keyframes fadeIn {
                    from { opacity: 0; transform: scale(0.95); }
                    to { opacity: 1; transform: scale(1); }
                }
                .animate-fade-in {
                    animation: fadeIn 0.15s ease-out;
                }
            </style>

            <script>
                function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
                function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
                function closeModalOutside(event, id) {
                    if (event.target === event.currentTarget) closeModal(id);
                }
            </script>
        @else
        {{-- GURU & PARENT: Tile-based quick actions --}}
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php
                $guruActions = [
                    ['href' => route('guru.presensi.create'), 'icon' => 'icon-isi-presensi.png', 'label' => 'Isi Presensi', 'description' => 'Catat presensi les'],
                    ['href' => route('guru.presensi.index'), 'icon' => 'icon-riwayat-presensi.png', 'label' => 'Riwayat Presensi', 'description' => 'Lihat presensi yang sudah diisi'],
                    ['href' => route('guru.history.index'), 'icon' => 'icon-riwayat.png', 'label' => 'Riwayat Les', 'description' => 'Pantau riwayat les'],
                    ['href' => route('guru.salary-projection.index'), 'icon' => 'icon-proyeksi-gaji.png', 'label' => 'Proyeksi Gaji', 'description' => 'Hitung estimasi gaji'],
                    ['href' => route('guru.tawaran.index'), 'icon' => 'icon-tawaran-colored.png', 'label' => 'Tawaran Les', 'description' => 'Kelola tawaran les'],
                    ['href' => route('guru.documents.index'), 'icon' => 'icon-dokumen-colored.png', 'label' => 'Dokumen', 'description' => 'Lihat dan download dokumen'],
                ];

                $muridActions = [
                    ['href' => route('parent.history.index'), 'icon' => 'icon-presensi-les.png', 'label' => 'Presensi Les', 'description' => 'Cek dan tolak presensi yang tidak sesuai'],
                    ['href' => route('parent.billing.index'), 'icon' => 'icon-tagihan.png', 'label' => 'Tagihan', 'description' => 'Lihat status pembayaran'],
                ];

                $sectionTitle = match ($role) {
                    'guru' => 'Menu Guru',
                    'murid' => 'Menu Murid',
                    'parent' => 'Menu Murid',
                    default => 'Menu Cepat',
                };

                $actions = match ($role) {
                    'guru' => $guruActions,
                    'murid' => $muridActions,
                    'parent' => $muridActions,
                    default => [],
                };
            @endphp

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold">Akses Cepat</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Langsung masuk ke tindakan penting tanpa pindah dashboard.
                        </p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold">Fokus Kerja</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Semua peran melihat aksi yang relevan dengan aksesnya.
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-end justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ $sectionTitle }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $role ? 'Aksi yang tersedia untuk role ' . $role : 'Aksi yang tersedia untuk akun Anda' }}</p>
                    </div>
                </div>

                @if (count($actions) > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        @foreach ($actions as $action)
                            <a href="{{ $action['href'] }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square">
                                <img src="{{ asset('storage/icons/' . $action['icon']) }}" alt="{{ $action['label'] }}" class="w-16 h-16 mb-2 object-contain">
                                <div class="text-xs font-semibold text-gray-700 text-center leading-tight">{{ $action['label'] }}</div>
                                <div class="mt-1 text-[11px] text-gray-500 text-center leading-snug">{{ $action['description'] }}</div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-sm text-gray-600">
                        Tidak ada menu cepat yang tersedia untuk role ini.
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
