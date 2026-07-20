<x-app-layout>
    <x-slot name="header">
        <div>
            <x-breadcrumb :items="[['label' => 'Export & Backup']]" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Export & Backup</h2>
            <p class="text-sm text-gray-500 mt-0.5">Download data dalam berbagai format</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if ($errors->has('backup'))
                <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $errors->first('backup') }}
                </div>
            @endif

            {{-- CSV --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">📄 CSV</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <a href="{{ route('admin.export.students') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">👨‍🎓</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Murid</div>
                    </a>
                    <a href="{{ route('admin.export.teachers') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">👨‍🏫</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Guru</div>
                    </a>
                    <a href="{{ route('admin.export.lessons') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">📝</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Enrollment</div>
                    </a>
                    <a href="{{ route('admin.export.attendances') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">✅</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Presensi</div>
                    </a>
                    <a href="{{ route('admin.export.audit') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">📋</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Audit Log</div>
                    </a>
                </div>
            </div>

            {{-- Excel --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">📊 Excel</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <a href="{{ route('admin.export.students.excel') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">👨‍🎓</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Murid</div>
                    </a>
                    <a href="{{ route('admin.export.teachers.excel') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">👨‍🏫</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Guru</div>
                    </a>
                    <a href="{{ route('admin.export.lessons.excel') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">📝</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Enrollment</div>
                    </a>
                    <a href="{{ route('admin.export.attendances.excel') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">✅</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Presensi</div>
                    </a>
                    <a href="{{ route('admin.export.audit.excel') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">📋</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Audit Log</div>
                    </a>
                </div>
            </div>

            {{-- PDF --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">📕 PDF</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <a href="{{ route('admin.export.students.pdf') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">👨‍🎓</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Murid</div>
                    </a>
                    <a href="{{ route('admin.export.teachers.pdf') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">👨‍🏫</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Guru</div>
                    </a>
                    <a href="{{ route('admin.export.lessons.pdf') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">📝</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Enrollment</div>
                    </a>
                    <a href="{{ route('admin.export.attendances.pdf') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">✅</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Presensi</div>
                    </a>
                    <a href="{{ route('admin.export.audit.pdf') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col items-center justify-center aspect-square hover:shadow-md hover:scale-[1.03] transition-all">
                        <div class="text-4xl mb-2">📋</div>
                        <div class="text-xs font-semibold text-gray-700 text-center">Audit Log</div>
                    </a>
                </div>
            </div>

            {{-- Laporan Bulanan --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">📅 Laporan Bulanan</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="text-3xl">✅</div>
                            <div>
                                <p class="font-semibold text-gray-800">Presensi Enrollment</p>
                                <p class="text-xs text-gray-500">Download laporan presensi per bulan</p>
                            </div>
                        </div>
                        <form method="GET" class="flex items-end gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Bulan</label>
                                <input type="number" name="month" value="{{ request('month', now()->month) }}" min="1" max="12" class="w-20 rounded-xl border-gray-200 text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Tahun</label>
                                <input type="number" name="year" value="{{ request('year', now()->year) }}" min="2020" max="2100" class="w-24 rounded-xl border-gray-200 text-sm" required />
                            </div>
                            <button type="submit" formaction="{{ route('admin.export.attendances.monthly.excel') }}" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">Excel</button>
                            <button type="submit" formaction="{{ route('admin.export.attendances.monthly.pdf') }}" class="px-4 py-2 rounded-xl bg-rose-600 text-white text-sm font-medium hover:bg-rose-700 transition-colors">PDF</button>
                        </form>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="text-3xl">🏫</div>
                            <div>
                                <p class="font-semibold text-gray-800">Kelas Bersama</p>
                                <p class="text-xs text-gray-500">Download laporan kelas per bulan</p>
                            </div>
                        </div>
                        <form method="GET" class="flex items-end gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Bulan</label>
                                <input type="number" name="month" value="{{ request('month', now()->month) }}" min="1" max="12" class="w-20 rounded-xl border-gray-200 text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Tahun</label>
                                <input type="number" name="year" value="{{ request('year', now()->year) }}" min="2020" max="2100" class="w-24 rounded-xl border-gray-200 text-sm" required />
                            </div>
                            <button type="submit" formaction="{{ route('admin.export.class-sessions.monthly.excel') }}" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">Excel</button>
                            <button type="submit" formaction="{{ route('admin.export.class-sessions.monthly.pdf') }}" class="px-4 py-2 rounded-xl bg-rose-600 text-white text-sm font-medium hover:bg-rose-700 transition-colors">PDF</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Backup --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">💾 Backup</h3>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <form method="POST" action="{{ route('admin.export.backup') }}" class="flex items-center gap-4">
                        @csrf
                        <div class="text-3xl">💾</div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800">Backup Database</p>
                            <p class="text-xs text-gray-500">Download file SQL backup database</p>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">Download Backup</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>