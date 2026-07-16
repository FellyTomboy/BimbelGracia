<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Guru</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.teachers.inactive') }}" class="px-4 py-2 rounded-md border text-sm">Data tidak aktif</a>

                <form method="POST" action="{{ route('admin.finance.snapshot.teachers') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="number" name="month" value="{{ now()->month }}" min="1" max="12" class="w-20 rounded-md border-gray-300 text-sm" aria-label="Bulan snapshot guru" />
                    <input type="number" name="year" value="{{ now()->year }}" min="2020" max="2100" class="w-24 rounded-md border-gray-300 text-sm" aria-label="Tahun snapshot guru" />
                    <button type="submit" class="px-4 py-2 rounded-md border text-sm">Snapshot jumlah guru</button>
                </form>

                <a href="{{ route('admin.teachers.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Guru</a>
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
                        <x-search-form placeholder="Cari nama, email, WA, jurusan..." />
                    </div>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <x-sortable-header label="Nama" column="teachers.name" />
                                <th class="py-2">Email</th>
                                <th class="py-2">WhatsApp</th>
                                <th class="py-2">Jurusan</th>
                                <th class="py-2">Mapel</th>
                                <x-sortable-header label="Tarif Kelas" column="teachers.class_rate" />
                                <x-sortable-header label="Status" column="teachers.status" />
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($teachers as $teacher)
                                <tr>
                                    <td class="py-2 font-medium">{{ $teacher->name }}</td>
                                    <td class="py-2">{{ $teacher->user?->email ?? '-' }}</td>
                                    <td class="py-2">{{ $teacher->whatsapp_number ?? '-' }}</td>
                                    <td class="py-2">{{ $teacher->major ?? '-' }}</td>
                                    <td class="py-2">{{ $teacher->subjects ?? '-' }}</td>
                                    <td class="py-2">Rp {{ number_format($teacher->class_rate ?? 0) }}</td>
                                    <td class="py-2">{{ $teacher->status }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-indigo-600">Edit</a>
                                        <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600">Hibernasi</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-gray-500">Tidak ada data guru ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $teachers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>