@push('scripts')
<script>
    window.__bulkForceUrls = window.__bulkForceUrls || {};
    window.__bulkForceUrls.programs = '{{ route('admin.programs.bulk-force-destroy') }}';
</script>
@endpush

<x-app-layout>
    <x-slot name="title">Program Les (Hibernasi)</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Program Les (Hibernasi)</h2>
            <a href="{{ route('admin.programs.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
        </div>
    </x-slot>

    <div class="py-12"
         x-data="fdOnlyInactiveModal('programs', 'program', 'Program', 'fd-modal-programs')"
         data-pagespeed-no-transform>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-400">{{ $programs->count() }} program</div>
                    <button type="button"
                            @click="openBulkModal()"
                            x-show="selectedIds.length > 0"
                            x-cloak
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-rose-600 hover:bg-rose-700 transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Permanen (<span x-text="selectedIds.length"></span>)
                    </button>
                </div>
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 w-8">
                                    <input type="checkbox"
                                           class="select-all-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           @change="toggleAll($event)" />
                                </th>
                                <th class="py-2">Nama</th>
                                <th class="py-2">Tipe</th>
                                <th class="py-2">Mapel/Divisi</th>
                                <th class="py-2">Biaya Induk</th>
                                <th class="py-2">Biaya Guru</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($programs as $program)
                                <tr data-row-id="{{ $program->id }}">
                                    <td class="py-2">
                                        <input type="checkbox"
                                               class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               value="{{ $program->id }}"
                                               data-name="{{ $program->name }}"
                                               data-cascade-count="{{ $program->enrollments_count ?? 0 }}"
                                               @change="toggleOne($event)" />
                                    </td>
                                    <td class="py-2">{{ $program->name }}</td>
                                    <td class="py-2">{{ ucfirst($program->type) }}</td>
                                    <td class="py-2">{{ $program->subject ?? ($program->division ?? '-') }}</td>
                                    <td class="py-2">Rp {{ number_format($program->default_parent_rate ?? 0) }}</td>
                                    <td class="py-2">Rp {{ number_format($program->default_teacher_rate ?? 0) }}</td>
                                    <td class="py-2">hibernasi</td>
                                    <td class="py-2">
                                        <div class="flex items-center gap-2">
                                            <form action="{{ route('admin.programs.restore', $program->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                                    Pulihkan
                                                </button>
                                            </form>
                                            <button type="button"
                                                    @click="openPerRowModal({{ $program->id }}, '{{ addslashes($program->name) }}', {{ $program->enrollments_count ?? 0 }}, [])"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                                Hapus Permanen
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Force Delete Modal --}}
        <x-force-delete-modal
            id="fd-modal-programs-single"
            title="Hapus Permanen Program?"
            message="Program akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="#"
            :is-bulk="false"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />
        <x-force-delete-modal
            id="fd-modal-programs-bulk"
            title="Hapus Permanen Program?"
            message="Program yang dipilih akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="{{ route('admin.programs.bulk-force-destroy') }}"
            :is-bulk="true"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />
    </div>
</x-app-layout>
