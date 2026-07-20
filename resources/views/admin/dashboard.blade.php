<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Admin</h2>
                <p class="text-sm text-gray-500 mt-0.5">Selamat datang, {{ auth()->user()?->name ?? 'Admin' }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- KPI CARDS --}}
            @php
                $kpis = [
                    ['icon' => '👨‍🎓', 'label' => 'Murid Aktif', 'value' => \App\Models\Student::where('status', 'active')->count(), 'color' => 'from-blue-500 to-blue-600'],
                    ['icon' => '👨‍🏫', 'label' => 'Guru Aktif', 'value' => \App\Models\Teacher::where('status', 'active')->count(), 'color' => 'from-emerald-500 to-emerald-600'],
                    ['icon' => '📅', 'label' => 'Kelas Hari Ini', 'value' => \App\Models\MonthlyAttendance::whereDate('lesson_date', now())->count(), 'color' => 'from-amber-500 to-amber-600'],
                    ['icon' => '⏳', 'label' => 'Presensi Menunggu', 'value' => \App\Models\MonthlyAttendance::where('status_validation', 'pending')->count(), 'color' => 'from-rose-500 to-rose-600'],
                    ['icon' => '💳', 'label' => 'Pembayaran Pending', 'value' => \App\Models\MonthlyAttendance::where('payment_proof_status', 'pending')->count(), 'color' => 'from-violet-500 to-violet-600'],
                ];
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach ($kpis as $kpi)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $kpi['color'] }} flex items-center justify-center text-2xl shadow-sm shrink-0">
                            {{ $kpi['icon'] }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold text-gray-800">{{ number_format($kpi['value']) }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $kpi['label'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- CATEGORY: Data Master --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">📂 Data Master</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-data-master')">
                        <div class="text-5xl mb-2">📂</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Semua Data</div>
                    </div>
                    <a href="{{ route('admin.students.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">👨‍🎓</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Murid</div>
                    </a>
                    <a href="{{ route('admin.teachers.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">👨‍🏫</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Guru</div>
                    </a>
                    <a href="{{ route('admin.programs.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">📚</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Program</div>
                    </a>
                    <a href="{{ route('admin.enrollments.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">📝</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Enrollment</div>
                    </a>
                    <a href="{{ route('admin.lesson-offers.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">🎯</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Tawaran Les</div>
                    </a>
                </div>
            </div>

            {{-- CATEGORY: Akademik --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">✅ Akademik</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <a href="{{ route('admin.presensi.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">✅</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Validasi Presensi</div>
                    </a>
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-kelas')">
                        <div class="text-5xl mb-2">🏫</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Kelas</div>
                    </div>
                    <a href="{{ route('admin.class-students.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">👥</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Murid Kelas</div>
                    </a>
                    <a href="{{ route('admin.class-student-sessions.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">📅</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Jadwal Murid</div>
                    </a>
                    <a href="{{ route('admin.class-reports.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">📈</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Laporan Kelas</div>
                    </a>
                </div>
            </div>

            {{-- CATEGORY: Komunikasi --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">💬 Komunikasi</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-whatsapp')">
                        <div class="text-5xl mb-2">💬</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">WhatsApp</div>
                    </div>
                    <a href="{{ route('admin.analysis.ortu') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">👪</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">WA Ortu Privat</div>
                    </a>
                    <a href="{{ route('admin.analysis.ortu-kelas') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">🏫</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">WA Ortu Kelas</div>
                    </a>
                    <a href="{{ route('admin.analysis.guru') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">👨‍🏫</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">WA Guru</div>
                    </a>
                </div>
            </div>

            {{-- CATEGORY: Keuangan --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">💰 Keuangan</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <a href="{{ route('admin.payments.ortu') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">💰</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Pembayaran Ortu</div>
                    </a>
                    <a href="{{ route('admin.payments.guru') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">💳</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Pembayaran Guru</div>
                    </a>
                    <a href="{{ route('admin.finance.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">📊</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Keuangan</div>
                    </a>
                    <a href="{{ route('admin.bank-accounts.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">🏦</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Bank</div>
                    </a>
                    <a href="{{ route('admin.discounts.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">🏷️</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Diskon</div>
                    </a>
                </div>
            </div>

            {{-- CATEGORY: Laporan --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">📋 Laporan</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-laporan')">
                        <div class="text-5xl mb-2">📋</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Riwayat</div>
                    </div>
                    <a href="{{ route('admin.export.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-5 flex flex-col items-center justify-center aspect-square border border-gray-100">
                        <div class="text-5xl mb-2">📤</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Export</div>
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- MODALS --}}
    <div id="modal-data-master" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-data-master')">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-2 animate-fade-in">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Data Master</h3>
                <button onclick="closeModal('modal-data-master')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
            </div>
            <a href="{{ route('admin.students.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">👨‍🎓 Murid</a>
            <a href="{{ route('admin.teachers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">👨‍🏫 Guru</a>
            <a href="{{ route('admin.programs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📚 Program</a>
            <a href="{{ route('admin.enrollments.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📝 Enrollment</a>
            <a href="{{ route('admin.lesson-offers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">🎯 Tawaran Les</a>
        </div>
    </div>

    <div id="modal-kelas" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-kelas')">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-2 animate-fade-in">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Kelas</h3>
                <button onclick="closeModal('modal-kelas')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
            </div>
            <a href="{{ route('admin.class-students.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">🏫 Murid Kelas</a>
            <a href="{{ route('admin.class-student-sessions.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📅 Jadwal Murid</a>
        </div>
    </div>

    <div id="modal-whatsapp" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-whatsapp')">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-2 animate-fade-in">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">WhatsApp</h3>
                <button onclick="closeModal('modal-whatsapp')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
            </div>
            <a href="{{ route('admin.analysis.ortu') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">💬 WA Ortu Privat</a>
            <a href="{{ route('admin.analysis.ortu-kelas') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">💬 WA Ortu Kelas</a>
            <a href="{{ route('admin.analysis.guru') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">💬 WA Guru</a>
        </div>
    </div>

    <div id="modal-laporan" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-laporan')">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-2 animate-fade-in">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Riwayat</h3>
                <button onclick="closeModal('modal-laporan')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
            </div>
            <a href="{{ route('admin.history.students') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📋 Riwayat Murid</a>
            <a href="{{ route('admin.history.teachers') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📋 Riwayat Guru</a>
            <a href="{{ route('admin.history.payments') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📋 Riwayat Pembayaran</a>
            <a href="{{ route('admin.history.audit') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📋 Audit Log</a>
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
</x-app-layout>