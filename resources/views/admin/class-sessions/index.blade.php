<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jadwal Kelas</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.class-sessions.calendar') }}" class="px-4 py-2 rounded-md border text-sm">Kalender</a>
                <a href="{{ route('admin.class-sessions.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Jadwal</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <div class="mb-4">
                        <x-search-form placeholder="Cari mapel, kelas, guru..." />
                    </div>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <x-sortable-header label="Tanggal" column="class_sessions.session_date" />
                                <x-sortable-header label="Jam" column="class_sessions.session_time" />
                                <th class="py-2">Kelas</th>
                                <th class="py-2">Guru</th>
                                <x-sortable-header label="Mapel" column="class_sessions.subject" />
                                <th class="py-2">Murid</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($sessions as $session)
                                <tr>
                                    <td class="py-2">{{ $session->session_date->format('d M Y') }}</td>
                                    <td class="py-2">{{ $session->session_time?->format('H:i') ?? '-' }}</td>
                                    <td class="py-2">
                                        <x-hibernated-label :model="$session->classGroup" :label="$session->classGroup?->name ?? '-'" type="kelas" />
                                    </td>
                                    <td class="py-2">
                                        <x-hibernated-label :model="$session->teacher" :label="$session->teacher?->name ?? '-'" type="guru" />
                                    </td>
                                    <td class="py-2">{{ $session->subject ?? '-' }}</td>
                                    <td class="py-2">{{ $session->students_count }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.class-sessions.show', $session) }}" class="text-indigo-600">Detail</a>
                                        <a href="{{ route('admin.class-sessions.edit', $session) }}" class="text-indigo-600">Edit</a>
                                        <form method="POST" action="{{ route('admin.class-sessions.destroy', $session) }}" onsubmit="return confirm('Hapus jadwal kelas ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-500">Tidak ada jadwal kelas ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $sessions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>