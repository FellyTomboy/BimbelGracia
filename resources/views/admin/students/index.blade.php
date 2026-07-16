<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Murid</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.students.inactive') }}" class="px-4 py-2 rounded-md border text-sm">Data tidak aktif</a>

                <form method="POST" action="{{ route('admin.finance.snapshot.students') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="number" name="month" value="{{ now()->month }}" min="1" max="12" class="w-20 rounded-md border-gray-300 text-sm" aria-label="Bulan snapshot murid" />
                    <input type="number" name="year" value="{{ now()->year }}" min="2020" max="2100" class="w-24 rounded-md border-gray-300 text-sm" aria-label="Tahun snapshot murid" />
                    <button type="submit" class="px-4 py-2 rounded-md border text-sm">Snapshot jumlah murid</button>
                </form>

                <a href="{{ route('admin.students.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Murid</a>
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
                        <x-search-form placeholder="Cari nama, email, WA..." />
                    </div>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <x-sortable-header label="Nama" column="students.name" />
                                <th class="py-2">Email</th>
                                <x-sortable-header label="WA Utama" column="students.whatsapp_primary" />
                                <th class="py-2">WA Cadangan</th>
                                <x-sortable-header label="Status" column="students.status" />
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="py-2 font-medium">{{ $student->name }}</td>
                                    <td class="py-2">{{ $student->user?->email ?? '-' }}</td>
                                    <td class="py-2">{{ $student->whatsapp_primary ?? '-' }}</td>
                                    <td class="py-2">{{ $student->whatsapp_secondary ?? '-' }}</td>
                                    <td class="py-2">{{ $student->status }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.students.edit', $student) }}" class="text-indigo-600">Edit</a>
                                        <form method="POST" action="{{ route('admin.students.destroy', $student) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600">Hibernasi</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-500">Tidak ada data murid ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>