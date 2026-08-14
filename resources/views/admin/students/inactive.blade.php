<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Murid (Hibernasi)</h2>
            <a href="{{ route('admin.students.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="bg-rose-50 text-rose-700 px-4 py-3 rounded-md">{{ $errors->first() }}</div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Nama</th>
                                <th class="py-2">Parent Awal</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi Restore</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="py-2 font-medium">{{ $student->display_name }}</td>
                                    <td class="py-2">{{ $student->parent?->name ?? ($student->parent_id ? 'Parent dihapus' : 'Tidak ada parent') }}</td>
                                    <td class="py-2">hibernasi</td>
                                    <td class="py-2">
                                        <form method="POST" action="{{ route('admin.students.restore', $student->id) }}" class="space-y-2">
                                            @csrf
                                            <div>
                                                <label class="block text-xs text-gray-500">Kembalikan ke Parent:</label>
                                                <select name="parent_id" class="w-full border-gray-300 rounded-md text-xs">
                                                    <option value="">-- Pilih parent (atau buat baru) --</option>
                                                    @foreach ($parents as $parent)
                                                        <option value="{{ $parent->id }}" @selected($parent->id === $student->parent_id)>
                                                            {{ $parent->name }} ({{ $parent->user?->phone ?? '-' }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="text-xs text-gray-400">Atau buat parent baru:</div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" name="new_parent_name" class="w-full border-gray-300 rounded-md text-xs" placeholder="Nama parent baru" />
                                                <input type="text" name="new_parent_phone" class="w-full border-gray-300 rounded-md text-xs" placeholder="No HP parent baru" />
                                            </div>
                                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">Restore</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-400">Tidak ada murid yang dihibernasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
