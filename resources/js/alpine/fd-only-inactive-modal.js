/**
 * Alpine.js factory for inactive pages that use forceDeleteActions methods
 * at the top level (no fd. prefix) — e.g. bank-accounts, lesson-offers, programs.
 * Separate file prevents ModPageSpeed from rewriting inline x-data expressions.
 * Bulk URL read from window.__bulkForceUrls so the x-data contains no URLs.
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

        init() {
            window.addEventListener('force-delete-success', (e) => {
                if (e.detail?.modalId?.startsWith(modalPrefix)) {
                    location.reload();
                }
            });
        },
    };
}
