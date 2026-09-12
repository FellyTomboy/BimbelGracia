/**
 * Alpine.js factory for the Parents Inactive page.
 * Separate file prevents ModPageSpeed from rewriting inline x-data expressions.
 */
export function parentsInactiveModal(bulkForceUrl) {
    return {
        fd: window.forceDeleteActions({
            resource: 'parents',
            label: 'parent',
            itemName: 'Parent',
            modalPrefix: 'fd-modal-parents',
            bulkForceUrl: bulkForceUrl,
            forceDestroyUrl: (id) => '/admin/parents/' + id + '/force-destroy',
            listSelector: 'table',
        }),

        restoreLoading: null,
        bulkRestoreLoading: false,

        init() {
            window.addEventListener('force-delete-success', (e) => {
                if (e.detail?.modalId?.startsWith('fd-modal-parents')) {
                    location.reload();
                }
            });
        },

        async restoreRow(parentId) {
            if (!confirm('Pulihkan parent ini?')) return;
            this.restoreLoading = parentId;
            try {
                await window.Ajax.post('/admin/parents/' + parentId + '/restore');
                window.Toast?.success('Parent berhasil dipulihkan.');
                window.location.reload();
            } catch (e) {
                window.Toast?.error('Gagal memulihkan parent.');
            } finally {
                this.restoreLoading = null;
            }
        },

        async bulkRestore() {
            if (!confirm('Pulihkan semua parent yang dipilih?')) return;
            this.bulkRestoreLoading = true;
            try {
                const ids = this.fd.selectedIds;
                if (!ids || ids.length === 0) return;
                const params = new URLSearchParams();
                params.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
                ids.forEach(id => params.append('ids[]', id));
                await window.Ajax.post('/admin/parents/bulk-restore', params);
                window.Toast?.success('Parent berhasil dipulihkan.');
                window.location.reload();
            } catch (e) {
                window.Toast?.error('Gagal memulihkan parent.');
            } finally {
                this.bulkRestoreLoading = false;
            }
        },
    };
}
