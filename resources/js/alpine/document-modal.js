/**
 * Alpine factory for documents — wraps crudModal with FormData submission (for file uploads).
 * Used on admin/documents/index.blade.php.
 */
import { crudModal } from './crud-modal';

export function documentModal(config) {
    const crud = crudModal({
        createUrl: config.createUrl || '',
        storeUrl: config.storeUrl || '',
        editUrl: config.editUrl || ((id) => ''),
        updateUrl: config.updateUrl || ((id) => ''),
        deleteUrl: config.deleteUrl || ((id) => ''),
        deleteMethod: config.deleteMethod || 'delete',
        listSelector: config.listSelector || 'table',
    });

    return {
        ...crud,

        // Override submit() to use FormData (required for file upload)
        async submit() {
            const form = document.getElementById('crud-form');
            if (!form) {
                console.error('[documentModal] #crud-form not found');
                return;
            }

            this.submitting = true;
            this.errors = {};

            // Clear previous error display
            form.querySelectorAll('[class^="crud-error-"]').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });
            form.querySelectorAll('[class^="crud-field-"]').forEach(el => {
                el.classList.remove('border-rose-400', 'ring-1', 'ring-rose-300');
                el.classList.add('border-gray-300');
            });

            const url = this.isEdit
                ? (typeof this.updateUrl === 'function' ? this.updateUrl(this.currentId) : `${this.updateUrl}/${this.currentId}`)
                : this.storeUrl;
            const method = this.isEdit ? 'put' : 'post';

            try {
                // Use FormData to include file inputs
                const fd = new FormData(form);
                const response = await window.Ajax[method](url, fd);
                window.Toast?.success(this.isEdit ? 'Berhasil diperbarui.' : 'Berhasil disimpan.');
            } catch (e) {
                console.error('[documentModal] submit error', e.response?.status, e.response?.data);
                if (e.response?.status === 422) {
                    this.errors = e.response.data.errors || {};
                    const errors = e.response.data.errors || {};
                    Object.entries(errors).forEach(([field, messages]) => {
                        const errEl = form.querySelector(`.crud-error-${field}`);
                        if (errEl) {
                            errEl.textContent = Array.isArray(messages) ? messages.join(', ') : messages;
                            errEl.style.display = 'block';
                        }
                        const fieldEl = form.querySelector(`.crud-field-${field}`);
                        if (fieldEl) {
                            fieldEl.classList.remove('border-gray-300');
                            fieldEl.classList.add('border-rose-400', 'ring-1', 'ring-rose-300');
                        }
                    });
                    // Keep modal open on validation error
                    this.submitting = false;
                    return;
                }
            } finally {
                this.submitting = false;
            }

            this.close();
            this.refreshTable().catch(() => {});
        },
    };
}

window.documentModal = documentModal;
