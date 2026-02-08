<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jadwal Murid Kelas Bersama</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.class-student-sessions.inactive') }}" class="px-4 py-2 rounded-md border text-sm">Data tidak aktif</a>
                <a href="{{ route('admin.class-student-sessions.calendar') }}" class="px-4 py-2 rounded-md border text-sm">Kalender</a>
                <a href="{{ route('admin.class-student-sessions.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Jadwal</a>
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
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Tanggal</th>
                                <th class="py-2">Jam</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Catatan</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($sessions as $session)
                                <tr>
                                    <td class="py-2">{{ $session->session_date->format('d M Y') }}</td>
                                    <td class="py-2">
                                        {{ $session->start_time?->format('H:i') ?? '-' }} - {{ $session->end_time?->format('H:i') ?? '-' }}
                                    </td>
                                    <td class="py-2">{{ $session->student?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $session->notes ?? '-' }}</td>
                                    <td class="py-2">active</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.class-student-sessions.edit', $session) }}" class="text-indigo-600">Edit</a>
                                        <form method="POST" action="{{ route('admin.class-student-sessions.destroy', $session) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600">Hibernasi</button>
                                        </form>
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
