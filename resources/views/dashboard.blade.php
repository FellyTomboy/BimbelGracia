<x-app-layout>
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

            $adminActions = [
                ['href' => route('admin.students.index'), 'icon' => '👨‍🎓', 'label' => 'Murid', 'description' => 'Kelola data murid'],
                ['href' => route('admin.teachers.index'), 'icon' => '👨‍🏫', 'label' => 'Guru', 'description' => 'Kelola data guru'],
                ['href' => route('admin.programs.index'), 'icon' => '📚', 'label' => 'Program', 'description' => 'Atur program les'],
                ['href' => route('admin.enrollments.index'), 'icon' => '📝', 'label' => 'Enrollment', 'description' => 'Kelola pendaftaran aktif'],
                ['href' => route('admin.lesson-offers.index'), 'icon' => '🎯', 'label' => 'Tawaran Les', 'description' => 'Atur tawaran les'],
                ['href' => route('admin.class-students.index'), 'icon' => '🏫', 'label' => 'Murid Kelas', 'description' => 'Data murid kelas'],
                ['href' => route('admin.class-student-sessions.index'), 'icon' => '📅', 'label' => 'Jadwal Murid', 'description' => 'Jadwal sesi kelas'],
                ['href' => route('admin.analysis.ortu-kelas'), 'icon' => '💬', 'label' => 'WA Ortu Kelas', 'description' => 'Pesan otomatis ortu kelas'],
                ['href' => route('admin.analysis.ortu'), 'icon' => '💬', 'label' => 'WA Ortu Privat', 'description' => 'Pesan otomatis ortu privat'],
                ['href' => route('admin.analysis.guru'), 'icon' => '💬', 'label' => 'WA Guru', 'description' => 'Pesan otomatis guru'],
                ['href' => route('admin.payments.ortu'), 'icon' => '💰', 'label' => 'Pembayaran Ortu', 'description' => 'Konfirmasi pembayaran ortu'],
                ['href' => route('admin.payments.guru'), 'icon' => '💳', 'label' => 'Pembayaran Guru', 'description' => 'Konfirmasi pembayaran guru'],
                ['href' => route('admin.class-reports.index'), 'icon' => '📈', 'label' => 'Laporan Kelas', 'description' => 'Pantau laporan kelas'],
                ['href' => route('admin.history.students'), 'icon' => '📋', 'label' => 'Riwayat', 'description' => 'Lihat riwayat murid'],
                ['href' => route('admin.finance.index'), 'icon' => '📊', 'label' => 'Keuangan', 'description' => 'Ringkasan keuangan'],
                ['href' => route('admin.export.index'), 'icon' => '📤', 'label' => 'Export & Backup', 'description' => 'Unduh data dan cadangan'],
            ];

            $guruActions = [
                ['href' => route('guru.presensi.create'), 'icon' => '📝', 'label' => 'Isi Presensi', 'description' => 'Catat presensi les'],
                ['href' => route('guru.presensi.index'), 'icon' => '📋', 'label' => 'Riwayat Presensi', 'description' => 'Lihat presensi yang sudah diisi'],
                ['href' => route('guru.history.index'), 'icon' => '📚', 'label' => 'Riwayat Les', 'description' => 'Pantau riwayat les'],
                ['href' => route('guru.salary-projection.index'), 'icon' => '💰', 'label' => 'Proyeksi Gaji', 'description' => 'Hitung estimasi gaji'],
                ['href' => route('guru.tawaran.index'), 'icon' => '🎯', 'label' => 'Tawaran Les', 'description' => 'Kelola tawaran les'],
            ];

            $muridActions = [
                ['href' => route('murid.history.index'), 'icon' => '📚', 'label' => 'Presensi Les', 'description' => 'Cek dan tolak presensi yang tidak sesuai'],
                ['href' => route('murid.billing.index'), 'icon' => '💰', 'label' => 'Tagihan', 'description' => 'Lihat status pembayaran'],
            ];

            $sectionTitle = match ($role) {
                'admin' => 'Menu Admin',
                'guru' => 'Menu Guru',
                'murid' => 'Menu Murid',
                default => 'Menu Cepat',
            };

            $actions = match ($role) {
                'admin' => $adminActions,
                'guru' => $guruActions,
                'murid' => $muridActions,
                default => [],
            };
        @endphp

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
    </div>
</x-app-layout>
