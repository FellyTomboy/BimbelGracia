<x-app-layout>
    <x-slot name="title">Data Parent</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Parent']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Parent</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola data orang tua murid</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.parents.inactive') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-200 hover:border-slate-400 transition-all">Data tidak aktif</a>
                <a href="{{ route('admin.parents.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Parent
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
            @if ($errors->any())
                <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                {{-- Toolbar: Search + Count --}}
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-400">
                        {{ $parents->total() }} parent
                    </div>
                    <form method="GET" action="{{ route('admin.parents.index') }}" class="flex items-center gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, HP, atau nama murid..." class="border-gray-300 rounded-lg text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500" />
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-colors">Cari</button>
                        @if (request('search'))
                            <a href="{{ route('admin.parents.index') }}" class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 text-sm hover:bg-gray-200 transition-colors">Reset</a>
                        @endif
                    </form>
                    <button id="bulk-delete-btn" onclick="submitBulkDelete()" class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                        Hibernasi Massal
                    </button>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <form id="bulk-form" method="POST" action="{{ route('admin.parents.bulk-destroy') }}" onsubmit="return validateBulkDelete()">
                        @csrf
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 bg-gray-50/50">
                                    <th class="py-3 px-4 w-10">
                                        <input type="checkbox" onclick="toggleAll(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    </th>
                                    <th class="py-3 px-4 font-medium">#</th>
                                    <th class="py-3 px-4 font-medium">
                                        <a href="{{ route('admin.parents.index', array_merge(request()->query(), ['sort' => 'name', 'dir' => ($sortBy === 'name' && $sortDir === 'asc') ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-700">
                                            Nama Parent
                                            @if ($sortBy === 'name')
                                                @if ($sortDir === 'asc') ↑@else ↓@endif
                                            @endif
                                        </a>
                                    </th>
                                    <th class="py-3 px-4 font-medium">
                                        <a href="{{ route('admin.parents.index', array_merge(request()->query(), ['sort' => 'phone', 'dir' => ($sortBy === 'phone' && $sortDir === 'asc') ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-gray-700">
                                            No HP
                                            @if ($sortBy === 'phone')
                                                @if ($sortDir === 'asc') ↑@else ↓@endif
                                            @endif
                                        </a>
                                    </th>
                                    <th class="py-3 px-4 font-medium">Alamat</th>
                                    <th class="py-3 px-4 font-medium">Murid</th>
                                    <th class="py-3 px-4 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($parents as $index => $parent)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-4">
                                            <input type="checkbox" name="ids[]" value="{{ $parent->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" onchange="updateBulkButton()" />
                                        </td>
                                        <td class="py-3 px-4 text-gray-400">{{ $parents->firstItem() + $index }}</td>
                                        <td class="py-3 px-4 font-medium text-gray-900">{{ $parent->name ?? '-' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $parent->user?->phone ?? '-' }}</td>
                                        <td class="py-3 px-4 text-gray-600 text-xs max-w-xs truncate">{{ $parent->address ?? '-' }}</td>
                                        <td class="py-3 px-4 text-gray-600 text-xs">
                                            @if ($parent->students->isNotEmpty())
                                                @foreach ($parent->students as $si => $student)
                                                    <span class="mr-1">{{ $si + 1 }}.</span>{{ $student->display_name }}<br/>
                                                @endforeach
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('admin.parents.edit', $parent->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                                    Edit
                                                </a>
                                                <button type="button" onclick="submitDelete('/admin/parents/{{ $parent->id }}/hibernate', 'Hibernasi parent ini? Semua murid di bawahnya juga akan dihibernasi.')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                                        Hibernasi
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <x-empty-state icon="👤" title="Belum ada parent" description="Tambahkan parent baru untuk memulai." action="Tambah Parent" actionUrl="{{ route('admin.parents.create') }}" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </form>
                </div>

                {{-- Pagination --}}
                @if ($parents->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $parents->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function toggleAll(source) {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = source.checked);
            updateBulkButton();
        }

        function updateBulkButton() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const btn = document.getElementById('bulk-delete-btn');
            if (checked.length > 0) {
                btn.classList.remove('hidden');
            } else {
                btn.classList.add('hidden');
            }
        }

        function validateBulkDelete() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            if (checked.length === 0) {
                alert('Pilih minimal 1 data untuk dihibernasi.');
                return false;
            }
            return confirm('Hibernasi ' + checked.length + ' data yang dipilih?');
        }

        function submitBulkDelete() {
            document.getElementById('bulk-form').submit();
        }
        function submitDelete(action, message) {
            if (!confirm(message)) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;
            form.innerHTML = '<input name="_token" value="{{ csrf_token() }}">';
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</x-app-layout>
