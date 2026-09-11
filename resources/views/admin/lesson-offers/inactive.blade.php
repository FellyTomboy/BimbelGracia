<x-app-layout>
    <x-slot name="title">Tawaran Les (Hibernasi)</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tawaran Les (Hibernasi)</h2>
            <a href="{{ route('admin.lesson-offers.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
        </div>
    </x-slot>

    <div class="py-12"
         x-data="forceDeleteActions({
             resource: 'lesson-offers',
             label: 'tawaran les',
             itemName: 'Tawaran Les',
             bulkForceUrl: '{{ route('admin.lesson-offers.bulk-force-destroy') }}',
             forceDestroyUrl: (id) => `/admin/lesson-offers/${id}/force-destroy`,
             listSelector: 'table',
         })">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-400">{{ $offers->count() }} tawaran les</div>
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
                                <th class="py-2">ID</th>
                                <th class="py-2">Tingkat</th>
                                <th class="py-2">Mapel</th>
                                <th class="py-2">Jadwal</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Kontak WA</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($offers as $offer)
                                <tr data-row-id="{{ $offer->id }}">
                                    <td class="py-2">
                                        <input type="checkbox"
                                               class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               value="{{ $offer->id }}"
                                               data-name="{{ $offer->code }}"
                                               data-cascade-count="0"
                                               @change="toggleOne($event)" />
                                    </td>
                                    <td class="py-2 font-medium">{{ $offer->code }}</td>
                                    <td class="py-2">{{ $offer->education_level }}</td>
                                    <td class="py-2">{{ $offer->subject }}</td>
                                    <td class="py-2">
                                        @if ($offer->schedules)
                                            @foreach ($offer->schedules as $sch)
                                                <div>{{ $sch['day'] ?? '' }} {{ $sch['time'] ?? '' }}</div>
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-2">hibernasi</td>
                                    <td class="py-2">{{ $offer->contact_whatsapp ?? '-' }}</td>
                                    <td class="py-2">
                                        <div class="flex items-center gap-2">
                                            <form method="POST" action="{{ route('admin.lesson-offers.restore', $offer->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                                    Pulihkan
                                                </button>
                                            </form>
                                            <button type="button"
                                                    @click="openPerRowModal({{ $offer->id }}, '{{ addslashes($offer->code) }}', 0, [])"
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
            id="fd-modal-lesson-offers-single"
            title="Hapus Permanen Tawaran Les?"
            message="Tawaran les akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="#"
            :is-bulk="false"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />
        <x-force-delete-modal
            id="fd-modal-lesson-offers-bulk"
            title="Hapus Permanen Tawaran Les?"
            message="Tawaran les yang dipilih akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="{{ route('admin.lesson-offers.bulk-force-destroy') }}"
            :is-bulk="true"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />
    </div>
</x-app-layout>
