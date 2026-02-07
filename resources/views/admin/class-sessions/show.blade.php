<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Jadwal Kelas</h2>
            <a href="{{ route('admin.class-sessions.index') }}" class="text-sm text-gray-500">Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2">
                <p><span class="font-semibold">Kelas:</span> {{ $classSession->classGroup?->name ?? '-' }}</p>
                <p><span class="font-semibold">Guru:</span> {{ $classSession->teacher?->name ?? '-' }}</p>
                <p><span class="font-semibold">Tanggal:</span> {{ $classSession->session_date->format('d M Y') }}</p>
                <p><span class="font-semibold">Jam:</span> {{ $classSession->session_time?->format('H:i') ?? '-' }}</p>
                <p><span class="font-semibold">Mapel:</span> {{ $classSession->subject ?? '-' }}</p>
                <p><span class="font-semibold">Catatan:</span> {{ $classSession->notes ?? '-' }}</p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.class-sessions.attendance', $classSession) }}" class="space-y-4">
                    @csrf
                    <h3 class="text-lg font-semibold">Kehadiran Murid</h3>
                    <div class="grid md:grid-cols-2 gap-2">
                        @foreach ($classSession->students as $student)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="present_ids[]" value="{{ $student->id }}" @checked($student->pivot->is_present) />
                                <span>{{ $student->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan Kehadiran</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
