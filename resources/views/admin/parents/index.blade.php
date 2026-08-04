<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Parent</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-rose-50 text-rose-700 px-4 py-3 rounded-md">{{ $errors->first() }}</div>
            @endif

            <div class="flex justify-between items-center">
                <a href="{{ route('admin.parents.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">+ Tambah Parent</a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 pr-4">Nama Parent</th>
                                <th class="py-2 pr-4">No HP</th>
                                <th class="py-2 pr-4">Jumlah Murid</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($parents as $parent)
                                <tr>
                                    <td class="py-2 pr-4">{{ $parent->name }}</td>
                                    <td class="py-2 pr-4">{{ $parent->user?->phone ?? '-' }}</td>
                                    <td class="py-2 pr-4">{{ $parent->students->count() }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.parents.edit', $parent->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        <form method="POST" action="{{ route('admin.parents.destroy', $parent->id) }}" onsubmit="return confirm('Hapus parent ini?')" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-900">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @if ($parent->students->isNotEmpty())
                                    <tr class="bg-gray-50">
                                        <td colspan="4" class="py-1 pl-6 text-xs text-gray-500">
                                            Murid: {{ $parent->students->pluck('name')->implode(', ') }}
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-gray-400">Belum ada data parent.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $parents->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>