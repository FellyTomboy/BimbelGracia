@props([
    'name' => 'crud-modal',
])

{{--
  Wrapper for x-modal that connects to the parent crudModal Alpine component.
  The parent Alpine component calls openCreate()/openEdit() which dispatch
  'open-modal.{name}' events. This wrapper listens and forwards to x-modal.
--}}
<div x-on:open-modal.{{ $name }}.window="document.dispatchEvent(new CustomEvent('open-modal', { detail: '{{ $name }}' }))"
     x-on:close-modal.{{ $name }}.window="document.dispatchEvent(new CustomEvent('close-modal', { detail: '{{ $name }}' }))">

    <x-modal :name="$name" maxWidth="2xl" :show="false">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="modalTitle"></h3>
                <button
                    type="button"
                    @click="close()"
                    class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                    aria-label="Tutup"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Form body populated by Alpine -->
            <div id="modal-form-container" x-html="modalBody"></div>

            <!-- Form actions (shown when form has no submit button) -->
            <div x-show="modalBody && !loading && (modalBody.indexOf('type=\&quot;submit\&quot;') === -1)"
                 class="mt-6 flex justify-end gap-3">
                <button type="button" @click="close()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="button" @click="submit()"
                        :disabled="submitting"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                    <template x-if="submitting">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </template>
                    <span x-text="isEdit ? 'Simpan Perubahan' : 'Simpan'"></span>
                </button>
            </div>

            <!-- Loading state -->
            <div x-show="loading || !modalBody" x-cloak class="flex justify-center py-8">
                <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
        </div>
    </x-modal>
</div>
