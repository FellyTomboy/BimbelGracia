/**
 * Alpine.js factory for inactive pages that use forceDeleteActions methods
 * at the top level (no fd. prefix) — e.g. bank-accounts, lesson-offers, programs.
 * Separate file prevents ModPageSpeed from rewriting inline x-data expressions.
 * Bulk URL read from window.__bulkForceUrls so the x-data contains no URLs.
 *
 * Also includes restoreRow() for programs/bank-accounts inactive pages.
 */
export function fdOnlyInactiveModal(resource, label, itemName, modalPrefix) {
    const bulkUrlMap = {
        'bank-accounts': window.__bulkForceUrls?.bankAccounts,
        'lesson-offers': window.__bulkForceUrls?.lessonOffers,
        'programs': window.__bulkForceUrls?.programs,
    };
    return {
        ...window.forceDeleteActions({
            resource,
            label,
            itemName,
            modalPrefix,
            bulkForceUrl: bulkUrlMap[resource] ?? ('/admin/' + resource + '/bulk-force-destroy'),
            forceDestroyUrl: (id) => '/admin/' + resource + '/' + id + '/force-destroy',
            listSelector: 'table',
        }),

        // ── Restore modal state ──────────────────────────────────────────────
        restoreModalOpen: false,
        restoreModalTitle: 'Pulihkan Data?',
        restoreItemName: '',
        restoreUrl: '',
        restoreLoading: false,

        init() {
            window.addEventListener('force-delete-success', (e) => {
                if (e.detail?.modalId?.startsWith(modalPrefix)) {
                    location.reload();
                }
            });
        },

        // ── Open restore confirmation modal ───────────────────────────────────
        openRestoreModal(id, name) {
            this.restoreItemName = name;
            this.restoreUrl = '/admin/' + resource + '/' + id + '/restore';
            this.restoreModalOpen = true;
        },

        // ── Submit restore ───────────────────────────────────────────────────
        async submitRestore() {
            this.restoreLoading = true;
            try {
                await window.Ajax.post(this.restoreUrl);
                window.Toast?.success('Data berhasil dipulihkan.');
                this.restoreModalOpen = false;
                location.reload();
            } catch (e) {
                window.Toast?.error('Gagal memulihkan data.');
            } finally {
                this.restoreLoading = false;
            }
        },

        // ── Close restore modal ──────────────────────────────────────────────
        closeRestoreModal() {
            this.restoreModalOpen = false;
            this.restoreItemName = '';
            this.restoreUrl = '';
            this.restoreLoading = false;
        },
    };
}
