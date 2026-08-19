<x-app-layout>
    <x-slot name="title">Dokumen Buku Pelajaran</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dokumen Buku Pelajaran</h2>
                <p class="text-sm text-gray-500 mt-0.5">Upload dan atur akses dokumen untuk guru</p>
            </div>
            <a href="{{ route('admin.documents.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Upload Dokumen
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 font-medium">Judul</th>
                                <th class="py-3 px-4 font-medium">File</th>
                                <th class="py-3 px-4 font-medium">Ukuran</th>
                                <th class="py-3 px-4 font-medium">Akses</th>
                                <th class="py-3 px-4 font-medium">Proteksi</th>
                                <th class="py-3 px-4 font-medium">Password</th>
                                <th class="py-3 px-4 font-medium">Guru</th>
                                <th class="py-3 px-4 font-medium">Upload oleh</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($documents as $doc)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 font-medium text-gray-900">
                                        {{ $doc->title }}
                                        @if ($doc->description)
                                            <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($doc->description, 60) }}</p>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 text-xs">{{ $doc->file_name }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $doc->formatted_size }}</td>
                                    <td class="py-3 px-4">
                                        @if ($doc->access_type === 'teacher')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">Guru Tertentu</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Password</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if (($doc->protection_level ?? 'standard') === 'strict')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Ketat</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">Longgar</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">
                                        @if ($doc->access_type === 'password')
                                            <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $doc->access_password_plain ?? '***' }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 text-xs">
                                        @if ($doc->access_type === 'teacher' && $doc->teachers->isNotEmpty())
                                            {{ $doc->teachers->pluck('name')->implode(', ') }}
                                        @elseif ($doc->access_type === 'teacher')
                                            <span class="text-gray-400">Belum dipilih</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 text-xs">{{ $doc->uploader?->name ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.documents.download', $doc) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">Download</a>
                                            <a href="{{ route('admin.documents.edit', $doc) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">Edit</a>
                                            <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}" onsubmit="return confirm('Hapus dokumen ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="py-8 text-center text-gray-400">Belum ada dokumen.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($documents->hasPages())
                    <div class="p-4 border-t border-gray-100">{{ $documents->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>