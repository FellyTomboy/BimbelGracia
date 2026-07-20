<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Guru']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Guru</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola data guru pengajar</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.teachers.inactive') }}" class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-all">Data tidak aktif</a>
                <a href="{{ route('admin.teachers.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Guru
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4">
                    <x-search-form placeholder="Cari nama, email, WA..." />
                    <div class="text-sm text-gray-400">{{ $teachers->total() }} guru</div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <x-sortable-header label="Nama" column="teachers.name" />
                                <th class="py-3 px-4 font-medium">Email</th>
                                <th class="py-3 px-4 font-medium">WA</th>
                                <th class="py-3 px-4 font-medium">Rekening</th>
                                <x-sortable-header label="Status" column="teachers.status" />
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($teachers as $teacher)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ $teacher->name }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $teacher->user?->email ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $teacher->whatsapp ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">
                                        @if ($teacher->bank_name)
                                            <span class="text-xs">{{ $teacher->bank_name }} {{ $teacher->bank_account }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($teacher->status === 'active')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $teacher->status }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.teachers.edit', $teacher) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">Edit</a>
                                            <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" onsubmit="return confirm('Hibernasi guru ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-empty-state icon="👨‍🏫" title="Belum ada guru" description="Tambahkan guru baru." action="Tambah Guru" actionUrl="{{ route('admin.teachers.create') }}" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($teachers->hasPages())
                    <div class="p-4 border-t border-gray-100">{{ $teachers->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>