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

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold">Export CSV</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <a href="{{ route('admin.export.students') }}" class="px-4 py-2 rounded-md border">Export Murid</a>
                    <a href="{{ route('admin.export.teachers') }}" class="px-4 py-2 rounded-md border">Export Guru</a>
                    <a href="{{ route('admin.export.lessons') }}" class="px-4 py-2 rounded-md border">Export Les Privat</a>
                    <a href="{{ route('admin.export.attendances') }}" class="px-4 py-2 rounded-md border">Export Presensi</a>
                    <a href="{{ route('admin.export.class-groups') }}" class="px-4 py-2 rounded-md border">Export Kelas Bersama</a>
                    <a href="{{ route('admin.export.class-sessions') }}" class="px-4 py-2 rounded-md border">Export Jadwal Kelas</a>
                    <a href="{{ route('admin.export.audit') }}" class="px-4 py-2 rounded-md border">Export Audit Log</a>
                </div>
                <p class="text-sm text-gray-500">Gunakan query string student_id atau teacher_id untuk export presensi per murid/guru.</p>
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
