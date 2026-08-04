<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Admin</h2>
                <p class="text-sm text-gray-500 mt-0.5">Selamat datang, {{ auth()->user()?->name ?? 'Admin' }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">

                {{-- DATA MASTER --}}
                <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-data-master')">
                    <div class="text-5xl md:text-6xl mb-3">📂</div>
                    <div class="text-sm font-semibold text-gray-700 text-center">Data Master</div>
                </div>

                {{-- KELAS --}}
                <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-kelas')">
                    <div class="text-5xl md:text-6xl mb-3">🏫</div>
                    <div class="text-sm font-semibold text-gray-700 text-center">Kelas</div>
                </div>

                {{-- WHATSAPP --}}
                <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-wa')">
                    <div class="text-5xl md:text-6xl mb-3">💬</div>
                    <div class="text-sm font-semibold text-gray-700 text-center">WhatsApp</div>
                </div>

                {{-- PEMBAYARAN --}}
                <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-pembayaran')">
                    <div class="text-5xl md:text-6xl mb-3">💰</div>
                    <div class="text-sm font-semibold text-gray-700 text-center">Pembayaran</div>
                </div>

                {{-- LAPORAN --}}
                <div class="bg-white rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.03] transition-all duration-200 p-6 flex flex-col items-center justify-center cursor-pointer aspect-square border border-gray-100" onclick="openModal('modal-laporan')">
                    <div class="text-5xl md:text-6xl mb-3">📋</div>
                    <div class="text-sm font-semibold text-gray-700 text-center">Laporan</div>
                </div>

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
            <a href="{{ route('admin.students.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">👨‍🎓 Murid</a>
            <a href="{{ route('admin.teachers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">👨‍🏫 Guru</a>
            <a href="{{ route('admin.programs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📚 Program</a>
            <a href="{{ route('admin.enrollments.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📝 Enrollment</a>
            <a href="{{ route('admin.lesson-offers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">🎯 Tawaran Les</a>
            <div class="border-t border-gray-100 my-1"></div>
            <a href="{{ route('admin.presensi.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">✅ Validasi Presensi</a>
        </div>
    </div>

    {{-- MODAL KELAS --}}
    <div id="modal-kelas" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-kelas')">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Kelas</h3>
                <button onclick="closeModal('modal-kelas')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
            </div>
            <a href="{{ route('admin.class-student-sessions.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">🏫 Kalender Kelas</a>
            <a href="{{ route('admin.class-student-sessions.table') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">📅 Tabel Kelas</a>
            <a href="{{ route('admin.analysis.ortu-kelas') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">💬 WA Ortu Kelas</a>
        </div>
    </div>

    {{-- MODAL WHATSAPP --}}
    <div id="modal-wa" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center p-4" onclick="closeModalOutside(event, 'modal-wa')">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-1 animate-fade-in">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">WhatsApp</h3>
                <button onclick="closeModal('modal-wa')" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
            </div>
            <a href="{{ route('admin.analysis.ortu') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">💬 WA Ortu Privat</a>
            <a href="{{ route('admin.analysis.guru') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition-colors">💬 WA Guru</a>
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
</x-app-layout>