/**
 * Alpine.js factory for the Teachers Inactive page.
 * Separate file prevents ModPageSpeed from rewriting inline x-data expressions.
 */
export function teachersInactiveModal() {
    return {
        fd: window.forceDeleteActions({
            resource: 'teachers',
            label: 'guru',
            itemName: 'Guru',
            modalPrefix: 'fd-modal-teachers',
            bulkForceUrl: window.__bulkForceUrls?.teachers ?? '/admin/teachers/bulk-force-destroy',
            forceDestroyUrl: (id) => '/admin/teachers/' + id + '/force-destroy',
            listSelector: 'table',
        }),

        restoreLoading: null,

        init() {
            window.addEventListener('force-delete-success', (e) => {
                if (e.detail?.modalId?.startsWith('fd-modal-teachers')) {
                    location.reload();
                }
            });
        },

        async restoreRow(teacherId) {
            if (!confirm('Pulihkan guru ini?')) return;
            this.restoreLoading = teacherId;
            try {
                await window.Ajax.post('/admin/teachers/' + teacherId + '/restore');
                window.Toast?.success('Guru berhasil dipulihkan.');
                window.location.reload();
            } catch (e) {
                window.Toast?.error('Gagal memulihkan guru.');
            } finally {
                this.restoreLoading = null;
            }
        },
    };
}
