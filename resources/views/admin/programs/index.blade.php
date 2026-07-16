<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Program Les</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.programs.inactive') }}" class="px-4 py-2 rounded-md border text-sm">Data tidak aktif</a>
                <a href="{{ route('admin.programs.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Program</a>
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
                        <x-search-form placeholder="Cari nama, tipe, mapel..." />
                    </div>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <x-sortable-header label="Nama" column="name" />
                                <x-sortable-header label="Tipe" column="type" />
                                <th class="py-2">Mapel</th>
                                <x-sortable-header label="Harga Ortu" column="default_parent_rate" />
                                <x-sortable-header label="Gaji Guru" column="default_teacher_rate" />
                                <x-sortable-header label="Status" column="status" />
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($programs as $program)
                                <tr>
                                    <td class="py-2 font-medium">{{ $program->name }}</td>
                                    <td class="py-2">{{ $program->type }}</td>
                                    <td class="py-2">{{ $program->subject ?? '-' }}</td>
                                    <td class="py-2">Rp {{ number_format($program->default_parent_rate) }}</td>
                                    <td class="py-2">Rp {{ number_format($program->default_teacher_rate) }}</td>
                                    <td class="py-2">{{ $program->status }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.programs.edit', $program) }}" class="text-indigo-600">Edit</a>
                                        <form method="POST" action="{{ route('admin.programs.destroy', $program) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600">Hibernasi</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-500">Tidak ada program ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $programs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>