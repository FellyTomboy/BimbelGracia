/**
 * Alpine.js factory for inactive pages that use forceDeleteActions methods
 * at the top level (no fd. prefix) — e.g. bank-accounts, lesson-offers, programs.
 * Separate file prevents ModPageSpeed from rewriting inline x-data expressions.
 */
export function fdOnlyInactiveModal(bulkForceUrl, resource, label, itemName, modalPrefix) {
    return {
        ...window.forceDeleteActions({
            resource,
            label,
            itemName,
            modalPrefix,
            bulkForceUrl,
            forceDestroyUrl: (id) => '/admin/' + resource + '/' + id + '/force-destroy',
            listSelector: 'table',
        }),

        init() {
            window.addEventListener('force-delete-success', (e) => {
                if (e.detail?.modalId?.startsWith(modalPrefix)) {
                    location.reload();
                }
            });
        },
    };
}
