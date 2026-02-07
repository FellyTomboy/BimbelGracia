<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelas Bersama</h2>
            <a href="{{ route('admin.class-groups.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Kelas</a>
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
                                <th class="py-2">Nama Kelas</th>
                                <th class="py-2">Mapel</th>
                                <th class="py-2">Guru</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($groups as $group)
                                <tr>
                                    <td class="py-2 font-medium">{{ $group->name }}</td>
                                    <td class="py-2">{{ $group->subject ?? '-' }}</td>
                                    <td class="py-2">{{ $group->teacher?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $group->students->count() }}</td>
                                    <td class="py-2">{{ $group->deleted_at ? 'hibernasi' : 'active' }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.class-groups.edit', $group) }}" class="text-indigo-600">Edit</a>
                                        @if ($group->deleted_at)
                                            <form method="POST" action="{{ route('admin.class-groups.restore', $group->id) }}">
                                                @csrf
                                                <button type="submit" class="text-emerald-600">Restore</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.class-groups.destroy', $group) }}">
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
