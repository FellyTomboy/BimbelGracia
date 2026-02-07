<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Export & Backup</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->has('backup'))
                <div class="bg-rose-50 text-rose-700 px-4 py-3 rounded-md">
                    {{ $errors->first('backup') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Export CSV</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <a href="{{ route('admin.export.students') }}" class="px-4 py-2 rounded-md border">Export Murid</a>
                        <a href="{{ route('admin.export.teachers') }}" class="px-4 py-2 rounded-md border">Export Guru</a>
                        <a href="{{ route('admin.export.lessons') }}" class="px-4 py-2 rounded-md border">Export Enrollment</a>
                        <a href="{{ route('admin.export.attendances') }}" class="px-4 py-2 rounded-md border">Export Presensi</a>
                        <a href="{{ route('admin.export.class-groups') }}" class="px-4 py-2 rounded-md border">Export Kelas Bersama</a>
                        <a href="{{ route('admin.export.class-sessions') }}" class="px-4 py-2 rounded-md border">Export Jadwal Kelas</a>
                        <a href="{{ route('admin.export.audit') }}" class="px-4 py-2 rounded-md border">Export Audit Log</a>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Export Excel</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <a href="{{ route('admin.export.students.excel') }}" class="px-4 py-2 rounded-md border">Export Murid</a>
                        <a href="{{ route('admin.export.teachers.excel') }}" class="px-4 py-2 rounded-md border">Export Guru</a>
                        <a href="{{ route('admin.export.lessons.excel') }}" class="px-4 py-2 rounded-md border">Export Enrollment</a>
                        <a href="{{ route('admin.export.attendances.excel') }}" class="px-4 py-2 rounded-md border">Export Presensi</a>
                        <a href="{{ route('admin.export.class-groups.excel') }}" class="px-4 py-2 rounded-md border">Export Kelas Bersama</a>
                        <a href="{{ route('admin.export.class-sessions.excel') }}" class="px-4 py-2 rounded-md border">Export Jadwal Kelas</a>
                        <a href="{{ route('admin.export.audit.excel') }}" class="px-4 py-2 rounded-md border">Export Audit Log</a>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Export PDF</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <a href="{{ route('admin.export.students.pdf') }}" class="px-4 py-2 rounded-md border">Export Murid</a>
                        <a href="{{ route('admin.export.teachers.pdf') }}" class="px-4 py-2 rounded-md border">Export Guru</a>
                        <a href="{{ route('admin.export.lessons.pdf') }}" class="px-4 py-2 rounded-md border">Export Enrollment</a>
                        <a href="{{ route('admin.export.attendances.pdf') }}" class="px-4 py-2 rounded-md border">Export Presensi</a>
                        <a href="{{ route('admin.export.class-groups.pdf') }}" class="px-4 py-2 rounded-md border">Export Kelas Bersama</a>
                        <a href="{{ route('admin.export.class-sessions.pdf') }}" class="px-4 py-2 rounded-md border">Export Jadwal Kelas</a>
                        <a href="{{ route('admin.export.audit.pdf') }}" class="px-4 py-2 rounded-md border">Export Audit Log</a>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Laporan Bulanan (Excel / PDF)</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="border rounded-md p-4 space-y-3">
                            <p class="font-medium">Presensi Enrollment</p>
                            <form method="GET" class="grid md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-sm text-gray-600">Bulan</label>
                                    <input type="number" name="month" value="{{ request('month', now()->month) }}" min="1" max="12" class="mt-1 w-full border-gray-300 rounded-md" required />
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600">Tahun</label>
                                    <input type="number" name="year" value="{{ request('year', now()->year) }}" min="2020" max="2100" class="mt-1 w-full border-gray-300 rounded-md" required />
                                </div>
                                <div class="flex items-end gap-2">
                                    <button type="submit" formaction="{{ route('admin.export.attendances.monthly.excel') }}" class="px-3 py-2 rounded-md border">Excel</button>
                                    <button type="submit" formaction="{{ route('admin.export.attendances.monthly.pdf') }}" class="px-3 py-2 rounded-md border">PDF</button>
                                </div>
                            </form>
                        </div>
                        <div class="border rounded-md p-4 space-y-3">
                            <p class="font-medium">Kelas Bersama</p>
                            <form method="GET" class="grid md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-sm text-gray-600">Bulan</label>
                                    <input type="number" name="month" value="{{ request('month', now()->month) }}" min="1" max="12" class="mt-1 w-full border-gray-300 rounded-md" required />
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600">Tahun</label>
                                    <input type="number" name="year" value="{{ request('year', now()->year) }}" min="2020" max="2100" class="mt-1 w-full border-gray-300 rounded-md" required />
                                </div>
                                <div class="flex items-end gap-2">
                                    <button type="submit" formaction="{{ route('admin.export.class-sessions.monthly.excel') }}" class="px-3 py-2 rounded-md border">Excel</button>
                                    <button type="submit" formaction="{{ route('admin.export.class-sessions.monthly.pdf') }}" class="px-3 py-2 rounded-md border">PDF</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Export Presensi per Murid / Guru (Excel / PDF)</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="border rounded-md p-4 space-y-3">
                            <p class="font-medium">Per Murid</p>
                            <form method="GET" class="grid md:grid-cols-3 gap-3">
                                <div class="md:col-span-2">
                                    <label class="block text-sm text-gray-600">Murid</label>
                                    <select name="student_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                                        <option value="">Pilih murid</option>
                                        @foreach ($students as $student)
                                            <option value="{{ $student->id }}" @selected(request('student_id') == $student->id)>{{ $student->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex items-end gap-2">
                                    <button type="submit" formaction="{{ route('admin.export.attendances.excel') }}" class="px-3 py-2 rounded-md border">Excel</button>
                                    <button type="submit" formaction="{{ route('admin.export.attendances.pdf') }}" class="px-3 py-2 rounded-md border">PDF</button>
                                </div>
                            </form>
                        </div>
                        <div class="border rounded-md p-4 space-y-3">
                            <p class="font-medium">Per Guru</p>
                            <form method="GET" class="grid md:grid-cols-3 gap-3">
                                <div class="md:col-span-2">
                                    <label class="block text-sm text-gray-600">Guru</label>
                                    <select name="teacher_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                                        <option value="">Pilih guru</option>
                                        @foreach ($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" @selected(request('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex items-end gap-2">
                                    <button type="submit" formaction="{{ route('admin.export.attendances.excel') }}" class="px-3 py-2 rounded-md border">Excel</button>
                                    <button type="submit" formaction="{{ route('admin.export.attendances.pdf') }}" class="px-3 py-2 rounded-md border">PDF</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-500">Export per murid/guru berlaku untuk presensi enrollment.</p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold">Backup Database</h3>
                <p class="text-sm text-gray-600">Backup otomatis tersedia untuk sqlite. File akan diunduh langsung.</p>
                <form method="POST" action="{{ route('admin.export.backup') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Download Backup</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
