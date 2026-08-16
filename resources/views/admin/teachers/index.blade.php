<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Guru']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Guru</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola data guru pengajar</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.teachers.inactive') }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-slate-100 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-200 hover:border-slate-400 transition-all">Data tidak aktif</a>
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
                    <div class="flex items-center gap-3">
                        <button id="bulk-delete-btn" onclick="submitBulkDelete()" class="hidden inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                            Hibernasi Massal
                        </button>
                        <span class="text-sm text-gray-400">{{ $teachers->total() }} guru</span>
                    </div>
                </div>
                <form id="bulk-form" method="POST" action="{{ route('admin.teachers.bulk-destroy') }}" onsubmit="return validateBulkDelete()">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 bg-gray-50/50">
                                    <th class="py-3 px-4 w-10">
                                        <input type="checkbox" onclick="toggleAll(this)" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    </th>
                                    <th class="py-3 px-4 font-medium">Foto</th>
                                    <x-sortable-header label="Nama" column="teachers.name" />
                                    <th class="py-3 px-4 font-medium">No. Telepon</th>
                                    <th class="py-3 px-4 font-medium">WA</th>
                                    <th class="py-3 px-4 font-medium">Rekening</th>
                                    <th class="py-3 px-4 font-medium">Status Foto</th>
                                    <x-sortable-header label="Status" column="teachers.status" />
                                    <th class="py-3 px-4 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($teachers as $teacher)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-4">
                                            <input type="checkbox" name="ids[]" value="{{ $teacher->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" onchange="updateBulkButton()" />
                                        </td>
                                        <td class="py-3 px-4">
                                            @if ($teacher->profile_photo_url)
                                                <img src="{{ $teacher->profile_photo_url }}" alt="{{ $teacher->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                            @else
                                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 text-xs">-</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 font-medium text-gray-900">{{ $teacher->name }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $teacher->user?->phone ?? '-' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $teacher->whatsapp_number ?? $teacher->whatsapp ?? '-' }}</td>
                                        <td class="py-3 px-4 text-gray-600">
                                            @if ($teacher->bank_name)
                                                <span class="text-xs">{{ $teacher->bank_name }} {{ $teacher->bank_account }}</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            @if ($teacher->profile_photo_path)
                                                @if ($teacher->profile_photo_approved)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Disetujui</span>
                                                @else
                                                    <button type="button" onclick="submitForm('/admin/teachers/{{ $teacher->id }}/approve-photo')" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors">Setujui</button>
                                                @endif
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
                                                <button type="button" onclick="submitDelete('/admin/teachers/{{ $teacher->id }}', 'Hibernasi guru ini?')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">Hibernasi</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9"><x-empty-state icon="👨‍🏫" title="Belum ada guru" description="Tambahkan guru baru." action="Tambah Guru" actionUrl="{{ route('admin.teachers.create') }}" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
                @if ($teachers->hasPages())
                    <div class="p-4 border-t border-gray-100">{{ $teachers->links() }}</div>
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
            if (checked.length > 0) btn.classList.remove('hidden');
            else btn.classList.add('hidden');
        }
        function validateBulkDelete() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            if (checked.length === 0) { alert('Pilih minimal 1 data untuk dihibernasi.'); return false; }
            return confirm('Hibernasi ' + checked.length + ' data yang dipilih?');
        }
        function submitBulkDelete() { document.getElementById('bulk-form').submit(); }
        function submitDelete(action, message) {
            if (!confirm(message)) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;
            form.innerHTML = '<input name="_token" value="{{ csrf_token() }}"><input name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        }
        function submitForm(action) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;
            form.innerHTML = '<input name="_token" value="{{ csrf_token() }}">';
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</x-app-layout>
