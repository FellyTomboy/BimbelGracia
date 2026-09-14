@push('scripts')
<script>
    window.__bulkForceUrls = window.__bulkForceUrls || {};
    window.__bulkForceUrls.bankAccounts = '{{ route('admin.bank-accounts.bulk-force-destroy') }}';
</script>
@endpush

<x-app-layout>
    <x-slot name="title">Rekening Bimbel (Hibernasi)</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rekening Bimbel (Hibernasi)</h2>
            <a href="{{ route('admin.bank-accounts.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
        </div>
    </x-slot>

    <div class="py-12"
         x-data="fdOnlyInactiveModal('bank-accounts', 'rekening', 'Rekening', 'fd-modal-bank-accounts')"
         data-pagespeed-no-transform>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-400">{{ $accounts->count() }} rekening</div>
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
                                <th class="py-2">Bank</th>
                                <th class="py-2">No Rekening</th>
                                <th class="py-2">Pemilik</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($accounts as $account)
                                <tr data-row-id="{{ $account->id }}">
                                    <td class="py-2">
                                        <input type="checkbox"
                                               class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               value="{{ $account->id }}"
                                               data-name="{{ $account->bank_name }} - {{ $account->account_number }}"
                                               data-cascade-count="0"
                                               @change="toggleOne($event)" />
                                    </td>
                                    <td class="py-2 font-medium">{{ $account->bank_name }}</td>
                                    <td class="py-2">{{ $account->account_number }}</td>
                                    <td class="py-2">{{ $account->account_holder }}</td>
                                    <td class="py-2">{{ $account->status }}</td>
                                    <td class="py-2">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    @click="openRestoreModal({{ $account->id }}, '{{ addslashes($account->bank_name) }} - {{ addslashes($account->account_number) }}')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                                Pulihkan
                                            </button>
                                            <button type="button"
                                                    @click="openPerRowModal({{ $account->id }}, '{{ addslashes($account->bank_name) }} - {{ addslashes($account->account_number) }}', 0, [])"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                                Hapus Permanen
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-500">Tidak ada rekening hibernasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Force Delete Modal --}}
        <x-force-delete-modal
            id="fd-modal-bank-accounts-single"
            title="Hapus Permanen Rekening?"
            message="Rekening akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="#"
            :is-bulk="false"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />
        <x-force-delete-modal
            id="fd-modal-bank-accounts-bulk"
            title="Hapus Permanen Rekening?"
            message="Rekening yang dipilih akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="{{ route('admin.bank-accounts.bulk-force-destroy') }}"
            :is-bulk="true"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />

        {{-- Restore Confirmation Modal --}}
        <div x-show="restoreModalOpen"
             x-cloak
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
             @click.self="closeRestoreModal()"
             @keydown.escape.window="closeRestoreModal()">

            <div x-show="restoreModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                {{-- Header --}}
                <div class="px-6 py-4 bg-emerald-600 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <h3 class="text-white font-semibold text-base" x-text="restoreModalTitle">Pulihkan Data?</h3>
                    </div>
                    <button @click="closeRestoreModal()" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-600">
                        Yakin ingin memulihkan data berikut?
                    </p>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                        <p class="text-sm font-medium text-emerald-800" x-text="restoreItemName"></p>
                        <p class="text-xs text-emerald-600 mt-0.5">Rekening akan dikembalikan ke daftar aktif.</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 flex items-center justify-end gap-3 border-t">
                    <button @click="closeRestoreModal()"
                            :disabled="restoreLoading"
                            class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-100 transition-colors disabled:opacity-50">
                        Batal
                    </button>
                    <button @click="submitRestore()"
                            :disabled="restoreLoading"
                            class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2">
                        <svg x-show="restoreLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span x-text="restoreLoading ? 'Memulihkan...' : 'Ya, Pulihkan'">Ya, Pulihkan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
