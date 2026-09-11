<!--
    Reusable force-delete confirmation modal.

    Props:
    - id                : unique modal DOM id (e.g. 'fd-modal-student-5')
    - title             : modal title
    - message           : warning message
    - itemName          : display name of the item being deleted
    - confirmText       : button label (default: 'Hapus Permanen')
    - ajaxUrl           : POST URL for the delete action
    - itemId            : int, the id of the item (for per-row)
    - isBulk            : bool, true if bulk mode
    - bulkCount         : int, number of items in bulk
    - bulkIds           : array of ids (for bulk mode)
    - cascadeCount      : int, number of related records that will cascade
    - cascadeList       : array of strings, names of related records (optional)
    - needAcknowledge   : bool, require checkbox acknowledgement
    - acknowledgeLabel  : label for acknowledge checkbox
    - csrfToken         : CSRF token (passed from parent view)
-->
@props([
    'id' => 'force-delete-modal',
    'title' => 'Hapus Permanen?',
    'message' => 'Data akan dihapus permanen dan tidak dapat dipulihkan.',
    'itemName' => '',
    'confirmText' => 'Hapus Permanen',
    'ajaxUrl' => '#',
    'itemId' => null,
    'isBulk' => false,
    'bulkCount' => 0,
    'bulkIds' => '[]',
    'cascadeCount' => 0,
    'cascadeList' => '[]',
    'needAcknowledge' => false,
    'acknowledgeLabel' => 'Saya memahami data akan dihapus permanen dan tidak dapat dipulihkan.',
    'csrfToken' => '',
])

@php
    if (empty($csrfToken)) {
        $csrfToken = csrf_token();
    }
    // Serialize cascadeList for Alpine default
    $cascadeListJson = is_array($cascadeList) ? json_encode($cascadeList) : $cascadeList;
@endphp

<div x-data="{
    open: false,
    loading: false,
    acknowledged: false,

    // Parent reference — set via openPerRowModal() / openBulkModal() overrides in parent Alpine.
    // Allows this modal to read dynamic state (pendingId, pendingName, cascadeCount, cascadeList)
    // from the triggering Alpine component without needing a shared store.
    _parent: null,

    // Cascade display: try to get from parent component, fall back to static Blade defaults
    get cascadeCount() {
        return this._parent?.pendingCascadeCount ?? {{ (int) $cascadeCount }};
    },
    get cascadeList() {
        return this._parent?.cascadeList ?? {{ $cascadeListJson }};
    },
    get pendingId() {
        return this._parent?.pendingId ?? {{ $itemId ? (int) $itemId : 'null' }};
    },
    get pendingName() {
        return this._parent?.pendingName ?? '';
    },
    get selectedIds() {
        return this._parent?.selectedIds ?? [];
    },

    init() {
        window.addEventListener('force-delete-open', (e) => {
            if (e.detail?.modalId === '{{ $id }}') {
                // Bridge: set _parent so cascade getters can read triggering Alpine component
                if (e.detail._alpineParent) {
                    this._parent = e.detail._alpineParent;
                }
                this.open = true;
                this.acknowledged = {{ $needAcknowledge ? 'false' : 'true' }};
            }
        });
    },
    async submit() {
        @if ($needAcknowledge)
        if (!this.acknowledged) {
            window.Toast?.warning('Centang persetujuan terlebih dahulu.');
            return;
        }
        @endif
        this.loading = true;
        try {
            const params = new URLSearchParams();
            params.append('_token', '{{ $csrfToken }}');
            const ids = this.selectedIds;
            if (ids && ids.length > 0) {
                ids.forEach(id => params.append('ids[]', id));
            } else {
                params.append('id', this.pendingId);
            }
            await window.Ajax.post('{{ $ajaxUrl }}', params);
            window.Toast?.success('Berhasil dihapus permanen.');
            this.open = false;
            window.dispatchEvent(new CustomEvent('force-delete-success', {detail: {modalId: '{{ $id }}'}}));
        } catch (e) {
            // error toast handled by Ajax utility
        } finally {
            this.loading = false;
        }
    }
}">

    <div x-show="open"
         x-cloak
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500/60 z-[9999] flex items-center justify-center p-4"
         @click.self="open = false">

        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-4 bg-rose-600 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h3 class="text-white font-semibold text-base">{{ $title }}</h3>
                </div>
                <button @click="open = false" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 space-y-4">
                {{-- Item name --}}
                @if (!empty($itemName) || $isBulk)
                    <p class="font-medium text-gray-900">
                        @if ($isBulk)
                            <span class="text-rose-600" x-text="selectedIds.length"></span>
                            <span>{{ $itemName }}</span> akan dihapus permanen.
                        @else
                            <span class="text-rose-600" x-text="pendingName"></span>
                            <span> akan dihapus permanen.</span>
                        @endif
                    </p>
                @endif

                {{-- Warning message --}}
                <div class="bg-rose-50 border border-rose-200 rounded-xl px-4 py-3">
                    <p class="text-sm text-rose-700">{{ $message }}</p>
                </div>

                {{-- Cascade info — reactive via Alpine getters --}}
                <template x-if="cascadeCount > 0">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 space-y-1">
                        <p class="text-sm font-medium text-amber-800 flex items-center gap-1.5">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="cascadeCount"></span> record terkait akan ikut terhapus:
                        </p>
                        <template x-if="cascadeList.length > 0">
                            <ul class="text-xs text-amber-700 pl-5 list-disc space-y-0.5">
                                <template x-for="(item, i) in cascadeList.slice(0, 10)" :key="i">
                                    <li x-text="item"></li>
                                </template>
                                <template x-if="cascadeList.length > 10">
                                    <li class="italic">...dan <span x-text="cascadeList.length - 10"></span> lainnya</li>
                                </template>
                            </ul>
                        </template>
                    </div>
                </template>

                {{-- Acknowledge checkbox --}}
                @if ($needAcknowledge)
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox"
                               x-model="acknowledged"
                               class="mt-0.5 rounded border-gray-300 text-rose-600 focus:ring-rose-500" />
                        <span class="text-sm text-gray-700">{{ $acknowledgeLabel }}</span>
                    </label>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 flex items-center justify-end gap-3 border-t">
                <button @click="open = false"
                        :disabled="loading"
                        class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-100 transition-colors disabled:opacity-50">
                    Batal
                </button>
                <button @click="submit()"
                        :disabled="loading"
                        class="px-4 py-2 rounded-xl bg-rose-600 text-white text-sm font-medium hover:bg-rose-700 transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2">
                    <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ $confirmText }}
                </button>
            </div>
        </div>
    </div>
</div>
