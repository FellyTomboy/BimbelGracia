/**
 * Alpine factory for bulk-hibernate (active view) — moves selected rows to inactive.
 * Used on lesson-offers and bank-accounts active index pages.
 */
export function bulkHibernateActions({ bulkHibernateUrl, resource, label }) {
    return {
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
            if (!confirm(`Hibernasi ${this.selectedIds.length} ${label}?`)) return;

            try {
                const res = await window.Ajax.post(bulkHibernateUrl, {
                    ids: this.selectedIds.map(id => parseInt(id, 10)),
                });
                window.Toast?.success(res.message ?? `${this.selectedIds.length} ${label} berhasil dihibernasi.`);
                window.location.reload();
            } catch (e) {
                window.Toast?.error('Gagal hibernasi. Silakan coba lagi.');
            }
        },
    };
}

window.bulkHibernateActions = bulkHibernateActions;
