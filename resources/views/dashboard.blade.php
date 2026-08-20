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
                        <div class="text-5xl md:text-6xl mb-3">📂</div>
                        <div class="text-sm font-semibold text-gray-700 text-center">Data Master</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-kelas')">
                        <div class="text-5xl md:text-6xl mb-3">🏫</div>
                        <div class="text-sm font-semibold text-gray-700 text-center">Kelas</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-presensi')">
                        <div class="text-5xl md:text-6xl mb-3">✅</div>
                        <div class="text-sm font-semibold text-gray-700 text-center">Presensi</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-wa')">
                        <div class="text-5xl md:text-6xl mb-3">💬</div>
                        <div class="text-sm font-semibold text-gray-700 text-center">WA & Promo</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-pembayaran')">
                        <div class="text-5xl md:text-6xl mb-3">💰</div>
                        <div class="text-sm font-semibold text-gray-700 text-center">Pembayaran</div>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-laporan')">
                        <div class="text-5xl md:text-6xl mb-3">📋</div>
                        <div class="text-sm font-semibold text-gray-700 text-center">Laporan</div>
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
                    <a href="{{ route('admin.parents.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">👨‍👩‍👧‍👦 Parent</a>
                    <a href="{{ route('admin.students.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">👨‍🎓 Murid</a>
                    <a href="{{ route('admin.teachers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">👨‍🏫 Guru</a>
                    <a href="{{ route('admin.programs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📚 Program</a>
                    <a href="{{ route('admin.enrollments.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📝 Enrollment</a>
                    <a href="{{ route('admin.lesson-offers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">🎯 Tawaran Les</a>
                    <a href="{{ route('admin.documents.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📄 Dokumen</a>
                    <a href="{{ route('admin.new-students.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">🆕 Pendaftar Murid Baru</a>
                    <a href="{{ route('admin.teacher-registrants.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">🧑‍🏫 Pendaftar Guru Baru</a>
                </div>
            </div>

            {{-- MODAL KELAS --}}
            <div id="modal-kelas" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-kelas')">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold">Kelas</h3>
                        <button onclick="closeModal('modal-kelas')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                    </div>
                    <a href="{{ route('admin.class-student-sessions.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">🏫 Presensi & Jadwal Kelas</a>
                    <a href="{{ route('admin.class-student-sessions.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">➕ Tambah Sesi</a>
                </div>
            </div>

            {{-- MODAL PRESENSI --}}
            <div id="modal-presensi" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-presensi')">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold">Presensi</h3>
                        <button onclick="closeModal('modal-presensi')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                    </div>
                    <a href="{{ route('admin.presensi.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">✅ Validasi Presensi Privat</a>
                </div>
            </div>

            {{-- MODAL WA & PROMO --}}
            <div id="modal-wa" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-wa')">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold">WA & Promo</h3>
                        <button onclick="closeModal('modal-wa')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                    </div>
                    <a href="{{ route('admin.analysis.ortu') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">💬 Template WA Ortu</a>
                    <a href="{{ route('admin.analysis.guru') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">💬 Template WA Guru</a>
                    <a href="{{ route('admin.discounts.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">🏷️ Diskon/Promo</a>
                </div>
            </div>

            {{-- MODAL PEMBAYARAN --}}
            <div id="modal-pembayaran" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-pembayaran')">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold">Pembayaran</h3>
                        <button onclick="closeModal('modal-pembayaran')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                    </div>
                    <a href="{{ route('admin.payments.ortu') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">💰 Pembayaran Ortu</a>
                    <a href="{{ route('admin.payments.guru') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">💳 Pembayaran Guru</a>
                </div>
            </div>

            {{-- MODAL LAPORAN --}}
            <div id="modal-laporan" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-laporan')">
                <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold">Laporan</h3>
                        <button onclick="closeModal('modal-laporan')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                    </div>
                    <a href="{{ route('admin.class-reports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📈 Laporan Kelas</a>
                    <a href="{{ route('admin.history.students') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📋 Riwayat</a>
                    <a href="{{ route('admin.finance.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📊 Keuangan</a>
                    <a href="{{ route('admin.export.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📤 Export & Backup</a>
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
                    ['href' => route('guru.presensi.create'), 'icon' => '📝', 'label' => 'Isi Presensi', 'description' => 'Catat presensi les'],
                    ['href' => route('guru.presensi.index'), 'icon' => '📋', 'label' => 'Riwayat Presensi', 'description' => 'Lihat presensi yang sudah diisi'],
                    ['href' => route('guru.history.index'), 'icon' => '📚', 'label' => 'Riwayat Les', 'description' => 'Pantau riwayat les'],
                    ['href' => route('guru.salary-projection.index'), 'icon' => '💰', 'label' => 'Proyeksi Gaji', 'description' => 'Hitung estimasi gaji'],
                    ['href' => route('guru.tawaran.index'), 'icon' => '🎯', 'label' => 'Tawaran Les', 'description' => 'Kelola tawaran les'],
                ];

                $muridActions = [
                    ['href' => route('parent.history.index'), 'icon' => '📚', 'label' => 'Presensi Les', 'description' => 'Cek dan tolak presensi yang tidak sesuai'],
                    ['href' => route('parent.billing.index'), 'icon' => '💰', 'label' => 'Tagihan', 'description' => 'Lihat status pembayaran'],
                ];

                $sectionTitle = match ($role) {
                    'guru' => 'Menu Guru',
                    'murid' => 'Menu Murid',
                    default => 'Menu Cepat',
                };

                $actions = match ($role) {
                    'guru' => $guruActions,
                    'murid' => $muridActions,
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
                                <div class="text-5xl mb-2">{{ $action['icon'] }}</div>
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
