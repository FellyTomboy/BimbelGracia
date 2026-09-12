/**
 * Alpine.js factory for the force-delete confirmation modal.
 * Lives in a separate .js file — ModPageSpeed corrupts inline x-data
 * expressions by truncating at quote characters inside attribute values.
 */

export function forceDeleteModal(id, csrfToken, needAcknowledge = false) {
    return {
        open: false,
        loading: false,
        acknowledged: !needAcknowledge,

        _parent: null,

        get cascadeCount() {
            return this._parent?.pendingCascadeCount ?? 0;
        },
        get cascadeList() {
            return this._parent?.cascadeList ?? [];
        },
        get pendingId() {
            return this._parent?.pendingId ?? null;
        },
        get pendingName() {
            return this._parent?.pendingName ?? '';
        },
        get selectedIds() {
            return this._parent?.selectedIds ?? [];
        },

        init() {
            window.addEventListener('force-delete-open', (e) => {
                if (e.detail?.modalId === id) {
                    if (e.detail._alpineParent) {
                        this._parent = e.detail._alpineParent;
                    }
                    this.open = true;
                    this.acknowledged = !needAcknowledge;
                }
            });
        },

        async submit() {
            if (needAcknowledge && !this.acknowledged) {
                window.Toast?.warning('Centang persetujuan terlebih dahulu.');
                return;
            }
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('_token', csrfToken);
                const ids = this.selectedIds;
                const isBulk = ids && ids.length > 0;

                const resourceMatch = id.match(/fd-modal-(\w+)-/);
                const resource = resourceMatch ? resourceMatch[1] : 'items';
                let ajaxUrl;

                if (isBulk) {
                    ajaxUrl = '/admin/' + resource + '/bulk-force-destroy';
                    ids.forEach(id => params.append('ids[]', id));
                } else {
                    if (!this.pendingId) {
                        window.Toast?.error('ID item tidak ditemukan.');
                        this.loading = false;
                        return;
                    }
                    ajaxUrl = '/admin/' + resource + '/' + this.pendingId + '/force-destroy';
                }

                await window.Ajax.post(ajaxUrl, params);
                window.Toast?.success('Berhasil dihapus permanen.');
                this.open = false;
                window.dispatchEvent(new CustomEvent('force-delete-success', { detail: { modalId: id } }));
            } catch (e) {
                // error toast handled by Ajax utility
            } finally {
                this.loading = false;
            }
        },
    };
}
