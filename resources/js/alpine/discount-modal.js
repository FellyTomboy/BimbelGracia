/**
 * Alpine.js Discount Modal Factory
 *
 * Handles mass discount application via AJAX + modal confirmation.
 *
 * Usage in Blade:
 *   <div x-data="discountModal({
 *     previewUrl: '/admin/discounts/preview',
 *     storeUrl: '{{ route('admin.discounts.store') }}',
 *     listSelector: 'table',
 *   })">
 */

export function discountModal(config) {
    return {
        modalOpen: false,
        modalTitle: '',
        modalBody: '',
        loading: false,
        submitting: false,
        errors: {},

        previewUrl: config.previewUrl || '/admin/discounts/preview',
        storeUrl: config.storeUrl || '/admin/discounts',
        listSelector: config.listSelector || 'table',

        // ── Open preview modal ───────────────────────────────────────────────
        async openPreviewModal() {
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
                const form = this._collectFormData();
                const response = await window.Ajax.post(this.previewUrl, form);
                const data = response.data;
                this.modalTitle = data.title || 'Konfirmasi Diskon';
                this.modalBody = data.html || '';
                this.$nextTick(() => {
                    const f = document.getElementById('discount-modal-form');
                    if (f && window.Alpine) {
                        window.Alpine.initTree(f);
                    }
                });
            } catch (e) {
                this.modalBody = `
                    <div class="text-center py-8">
                        <p class="text-rose-500 font-medium">Gagal memuat form.</p>
                        <button @click="close()" class="mt-4 text-sm text-indigo-600 hover:underline">Tutup</button>
                    </div>
                `;
            } finally {
                this.loading = false;
            }
        },

        // ── Submit discount ─────────────────────────────────────────────────
        async submitDiscount() {
            const form = document.getElementById('discount-modal-form');
            if (!form) return;

            this.submitting = true;
            this.errors = {};

            // Clear previous errors
            form.querySelectorAll('[class^="crud-error-"]').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });

            const params = this._collectFormData();

            try {
                await window.Ajax.post(this.storeUrl, params);
                window.Toast?.success('Diskon massal berhasil diterapkan.');
                this.close();
                await this.refreshTable();
            } catch (e) {
                if (e.response?.status === 422) {
                    const errors = e.response.data.errors || {};
                    Object.entries(errors).forEach(([field, messages]) => {
                        const errEl = form.querySelector(`.crud-error-${field}`);
                        if (errEl) {
                            errEl.textContent = Array.isArray(messages) ? messages.join(', ') : messages;
                            errEl.style.display = 'block';
                        }
                    });
                }
            } finally {
                this.submitting = false;
            }
        },

        // ── Collect form data for preview ───────────────────────────────────
        _collectFormData() {
            const params = new URLSearchParams();
            const container = document.getElementById('discount-form-container');
            if (!container) return params;

            container.querySelectorAll('input[name], select[name]').forEach(el => {
                if (el.type === 'checkbox') {
                    if (el.checked) params.append(el.name, el.value || 'on');
                } else if (el.type === 'radio') {
                    if (el.checked) params.append(el.name, el.value);
                } else if (el.value.trim()) {
                    params.append(el.name, el.value);
                }
            });

            return params;
        },

        // ── Close modal ─────────────────────────────────────────────────────
        close() {
            this.modalOpen = false;
            this.modalBody = '';
            this.modalTitle = '';
            this.errors = {};
            this.submitting = false;
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
            } catch (e) {
                // Silent fail
            }
        },

        // ── Label updater (called from Blade inline JS) ────────────────────
        updateDiscountLabel() {
            const typeSelect = document.getElementById('discount-type-select');
            const label = document.getElementById('discount-value-label');
            const input = document.getElementById('discount-value-input');
            if (!typeSelect || !label || !input) return;

            const type = typeSelect.value;
            if (type === 'percent') {
                label.textContent = 'Nilai Diskon (%)';
                input.placeholder = 'Contoh: 10';
                input.max = 100;
            } else if (type === 'final') {
                label.textContent = 'Harga Final (Rp)';
                input.placeholder = 'Contoh: 150000';
                input.removeAttribute('max');
            } else {
                label.textContent = 'Potongan Nominal (Rp)';
                input.placeholder = 'Contoh: 50000';
                input.removeAttribute('max');
            }
        },

        // ── Enrollment count helper ──────────────────────────────────────────
        selectedCount() {
            const container = document.getElementById('discount-form-container');
            if (!container) return 0;
            return container.querySelectorAll('input[name="enrollment_ids[]"]:checked').length;
        },
    };
}
