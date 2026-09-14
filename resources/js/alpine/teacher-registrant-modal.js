/**
 * Alpine.js Teacher Registrant Modal Factory
 *
 * Handles convert + delete actions for teacher registrations via AJAX + modal.
 *
 * Usage in Blade:
 *   <div x-data="teacherRegistrantModal({
 *     previewConvertUrl: '/admin/teacher-registrants/{id}/preview-convert',
 *     convertUrl: '/admin/teacher-registrants/{id}/convert',
 *     deleteUrl: '/admin/teacher-registrants/{id}',
 *     deleteAllUrl: '{{ route('admin.teacher-registrants.destroy-all') }}',
 *     listSelector: 'table',
 *   })">
 */

export function teacherRegistrantModal(config) {
    return {
        modalOpen: false,
        modalTitle: '',
        modalBody: '',
        loading: false,
        submitting: false,
        modalAction: 'convert', // 'convert' | 'delete' | 'deleteAll'

        previewConvertUrl: config.previewConvertUrl || ((id) => `/admin/teacher-registrants/${id}/preview-convert`),
        convertUrl: config.convertUrl || ((id) => `/admin/teacher-registrants/${id}/convert`),
        deleteUrl: config.deleteUrl || ((id) => `/admin/teacher-registrants/${id}`),
        deleteAllUrl: config.deleteAllUrl || '/admin/teacher-registrants/all',
        listSelector: config.listSelector || 'table',

        // ── Open convert preview modal ─────────────────────────────────────
        async openConvertModal(id) {
            this.modalAction = 'convert';
            this.loading = true;
            this.modalTitle = 'Memuat...';
            this.modalBody = `
                <div class="flex justify-center py-8">
                    <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            `;
            this.modalOpen = true;

            try {
                const url = typeof this.previewConvertUrl === 'function'
                    ? this.previewConvertUrl(id)
                    : this.previewConvertUrl.replace('{id}', id);
                const response = await window.Ajax.get(url);
                const data = response.data;
                this.modalTitle = data.title || 'Konfirmasi';
                this.modalBody = data.html || '';
                this.$nextTick(() => {
                    const form = document.getElementById('tr-modal-form');
                    if (form && window.Alpine) {
                        window.Alpine.initTree(form);
                    }
                });
            } catch (e) {
                this.modalBody = `
                    <div class="text-center py-8">
                        <p class="text-rose-500 font-medium">Gagal memuat data.</p>
                        <button @click="close()" class="mt-4 text-sm text-indigo-600 hover:underline">Tutup</button>
                    </div>
                `;
            } finally {
                this.loading = false;
            }
        },

        // ── Open delete confirmation modal ─────────────────────────────────
        async openDeleteModal(id) {
            this.modalAction = 'delete';
            this.loading = true;
            this.modalTitle = 'Hapus Pendaftar';
            this.modalBody = `
                <div class="flex justify-center py-8">
                    <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            `;
            this.modalOpen = true;

            try {
                const url = typeof this.deleteUrl === 'function'
                    ? this.deleteUrl(id)
                    : this.deleteUrl.replace('{id}', id);
                const response = await window.Ajax.get(url + '/preview-delete');
                this.modalBody = response.data.html || '';
                this.$nextTick(() => {
                    const form = document.getElementById('tr-modal-form');
                    if (form && window.Alpine) {
                        window.Alpine.initTree(form);
                    }
                });
            } catch (e) {
                this.modalBody = `
                    <div class="text-center py-8">
                        <p class="text-rose-500 font-medium">Gagal memuat data.</p>
                        <button @click="close()" class="mt-4 text-sm text-indigo-600 hover:underline">Tutup</button>
                    </div>
                `;
            } finally {
                this.loading = false;
            }
        },

        // ── Open delete-all warning modal ───────────────────────────────────
        async openDeleteAllModal() {
            this.modalAction = 'deleteAll';
            this.loading = true;
            this.modalTitle = 'Hapus Semua Pendaftar';
            this.modalBody = `
                <div class="flex justify-center py-8">
                    <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            `;
            this.modalOpen = true;

            try {
                const response = await window.Ajax.get('/admin/teacher-registrants/preview-delete-all');
                this.modalBody = response.data.html || '';
                this.$nextTick(() => {
                    const form = document.getElementById('tr-modal-form');
                    if (form && window.Alpine) {
                        window.Alpine.initTree(form);
                    }
                });
            } catch (e) {
                this.modalBody = `
                    <div class="text-center py-8">
                        <p class="text-rose-500 font-medium">Gagal memuat data.</p>
                        <button @click="close()" class="mt-4 text-sm text-indigo-600 hover:underline">Tutup</button>
                    </div>
                `;
            } finally {
                this.loading = false;
            }
        },

        // ── Generic submit — routes by modalAction ─────────────────────────
        async submitModal() {
            if (this.modalAction === 'convert') {
                await this._doConvert();
            } else if (this.modalAction === 'delete') {
                await this._doDelete();
            } else if (this.modalAction === 'deleteAll') {
                await this._doDeleteAll();
            }
        },

        // ── Internal action handlers ────────────────────────────────────────
        async _doConvert() {
            const form = document.getElementById('tr-modal-form');
            const id = form?.querySelector('input[name="_action"]')?.value;
            this.submitting = true;
            try {
                const url = typeof this.convertUrl === 'function'
                    ? this.convertUrl(id)
                    : this.convertUrl.replace('{id}', id);
                await window.Ajax.post(url);
                window.Toast?.success('Berhasil ditambahkan ke data guru.');
                this.close();
                await this.refreshTable();
            } catch (e) {
                window.Toast?.error('Gagal mengkonversi data.');
            } finally {
                this.submitting = false;
            }
        },

        async _doDelete() {
            const form = document.getElementById('tr-modal-form');
            const id = form?.querySelector('input[name="_action"]')?.value;
            this.submitting = true;
            try {
                const url = typeof this.deleteUrl === 'function'
                    ? this.deleteUrl(id)
                    : this.deleteUrl.replace('{id}', id);
                await window.Ajax.delete(url);
                window.Toast?.success('Data pendaftar dihapus.');
                this.close();
                await this.refreshTable();
            } catch (e) {
                window.Toast?.error('Gagal menghapus data.');
            } finally {
                this.submitting = false;
            }
        },

        async _doDeleteAll() {
            this.submitting = true;
            try {
                await window.Ajax.delete(this.deleteAllUrl);
                window.Toast?.success('Semua data pendaftar guru dihapus.');
                this.close();
                await this.refreshTable();
            } catch (e) {
                window.Toast?.error('Gagal menghapus semua data.');
            } finally {
                this.submitting = false;
            }
        },

        // ── Close modal ─────────────────────────────────────────────────────
        close() {
            this.modalOpen = false;
            this.modalBody = '';
            this.modalTitle = '';
            this.submitting = false;
            this.modalAction = 'convert';
        },

        // ── Refresh table ───────────────────────────────────────────────────
        async refreshTable() {
            const table = document.querySelector(this.listSelector);
            if (!table) return;

            try {
                const response = await window.Ajax.get(window.location.href);
                const parser = new DOMParser();
                const doc = parser.parseFromString(response.data, 'text/html');

                const newTable = doc.querySelector(this.listSelector);
                if (newTable) {
                    table.innerHTML = newTable.innerHTML;
                }

                const pagination = document.querySelector('.paging-links');
                const newPagination = doc.querySelector('.paging-links');
                if (pagination && newPagination) {
                    pagination.innerHTML = newPagination.innerHTML;
                }
            } catch (e) {
                // Silent fail
            }
        },
    };
}
