<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jadwal Kelas</h2>
            <a href="{{ route('admin.class-sessions.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Jadwal</a>
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
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Tanggal</th>
                                <th class="py-2">Jam</th>
                                <th class="py-2">Kelas</th>
                                <th class="py-2">Guru</th>
                                <th class="py-2">Mapel</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($sessions as $session)
                                <tr>
                                    <td class="py-2">{{ $session->session_date->format('d M Y') }}</td>
                                    <td class="py-2">{{ $session->session_time?->format('H:i') ?? '-' }}</td>
                                    <td class="py-2">{{ $session->classGroup?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $session->teacher?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $session->subject ?? '-' }}</td>
                                    <td class="py-2">{{ $session->students_count }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.class-sessions.show', $session) }}" class="text-indigo-600">Detail</a>
                                        <a href="{{ route('admin.class-sessions.edit', $session) }}" class="text-indigo-600">Edit</a>
                                        @if ($session->deleted_at)
                                            <form method="POST" action="{{ route('admin.class-sessions.restore', $session->id) }}">
                                                @csrf
                                                <button type="submit" class="text-emerald-600">Restore</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.class-sessions.destroy', $session) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600">Hibernasi</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
