<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Guru</h2>
            <a href="{{ route('admin.teachers.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Guru</a>
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
                                <th class="py-2">Nama</th>
                                <th class="py-2">Email</th>
                                <th class="py-2">WhatsApp</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($teachers as $teacher)
                                <tr>
                                    <td class="py-2 font-medium">{{ $teacher->name }}</td>
                                    <td class="py-2">{{ $teacher->user?->email ?? '-' }}</td>
                                    <td class="py-2">{{ $teacher->whatsapp_number ?? '-' }}</td>
                                    <td class="py-2">
                                        {{ $teacher->deleted_at ? 'hibernasi' : $teacher->status }}
                                    </td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-indigo-600">Edit</a>
                                        @if ($teacher->deleted_at)
                                            <form method="POST" action="{{ route('admin.teachers.restore', $teacher->id) }}">
                                                @csrf
                                                <button type="submit" class="text-emerald-600">Restore</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}">
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
