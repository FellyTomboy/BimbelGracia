<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Les Privat</h2>
            <a href="{{ route('admin.lessons.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Les</a>
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
                                <th class="py-2">ID Les</th>
                                <th class="py-2">Guru</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Biaya Ortu</th>
                                <th class="py-2">Gaji Guru</th>
                                <th class="py-2">Validasi</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($lessons as $lesson)
                                <tr>
                                    <td class="py-2 font-medium">{{ $lesson->code }}</td>
                                    <td class="py-2">{{ $lesson->teacher?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $lesson->student?->name ?? '-' }}</td>
                                    <td class="py-2">{{ number_format($lesson->parent_rate) }}</td>
                                    <td class="py-2">{{ number_format($lesson->teacher_rate) }}</td>
                                    <td class="py-2">{{ $lesson->validation_status }}</td>
                                    <td class="py-2">{{ $lesson->deleted_at ? 'hibernasi' : $lesson->status }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.lessons.edit', $lesson) }}" class="text-indigo-600">Edit</a>
                                        @if ($lesson->deleted_at)
                                            <form method="POST" action="{{ route('admin.lessons.restore', $lesson->id) }}">
                                                @csrf
                                                <button type="submit" class="text-emerald-600">Restore</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.lessons.destroy', $lesson) }}">
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
