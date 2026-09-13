/**
 * Alpine factory combining bulkHibernateActions + crudModal for lesson-offers.
 * Used on admin/lesson-offers/index.blade.php.
 */
import { crudModal } from './crud-modal';

export function lessonOfferModal(config) {
    const bulk = {
        selectedIds: [],

        toggleAll(event) {
            const checked = event.target.checked;
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = checked;
                if (checked && !this.selectedIds.includes(cb.value)) {
                    this.selectedIds.push(cb.value);
                } else if (!checked) {
                    this.selectedIds = [];
                }
            });
        },

        toggleOne(event) {
            const val = event.target.value;
            if (event.target.checked) {
                if (!this.selectedIds.includes(val)) this.selectedIds.push(val);
            } else {
                this.selectedIds = this.selectedIds.filter(id => id !== val);
            }
        },

        async submitBulkHibernate() {
            if (!this.selectedIds.length) return;
            if (!confirm(`Hibernasi ${this.selectedIds.length} tawaran les?`)) return;
            try {
                const res = await window.Ajax.post(config.bulk.bulkHibernateUrl, {
                    ids: this.selectedIds.map(id => parseInt(id, 10)),
                });
                window.Toast?.success(res.message ?? `${this.selectedIds.length} tawaran les berhasil dihibernasi.`);
                this.selectedIds = [];
                await this.refreshTable();
            } catch (e) {
                window.Toast?.error('Gagal hibernasi. Silakan coba lagi.');
            }
        },
    };

    const crud = crudModal({
        createUrl: config.crud.createUrl,
        storeUrl: config.crud.storeUrl,
        editUrl: config.crud.editUrl,
        updateUrl: config.crud.updateUrl,
        deleteUrl: config.crud.deleteUrl,
        deleteMethod: config.crud.deleteMethod,
        listSelector: config.crud.listSelector || 'table',
    });

    return {
        ...bulk,
        ...crud,
    };
}

window.lessonOfferModal = lessonOfferModal;
