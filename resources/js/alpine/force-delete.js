/**
 * Alpine.js Force Delete Factory
 *
 * Usage in Blade:
 *   <div x-data="forceDeleteActions({
 *     resource: 'students',
 *     label: 'murid',
 *     itemName: 'Murid',
 *     bulkForceUrl: '{{ route('admin.students.bulk-force-destroy') }}',
 *     forceDestroyUrl: (id) => `/admin/students/${id}/force-destroy`,
 *     listSelector: 'table',
 *     modalPrefix: 'fd-modal',
 *   })">
 *
 *   <input type="checkbox" class="row-checkbox" value="{{ $s->id }}"
 *          data-name="{{ $s->display_name }}"
 *          data-cascade-count="{{ $s->students_count ?? 0 }}"
 *          data-cascade-list='@json($s->students_names ?? [])'
 *          @change="toggleOne($event)" />
 *
 *   <button @click="openBulkModal()">
 *   <button @click="openPerRowModal({{ $s->id }}, '{{ addslashes($s->display_name) }}', {{ $s->students_count ?? 0 }}, @json($s->students_names ?? []))">
 *
 *   <x-force-delete-modal id="fd-modal-students-bulk" ... />
 *   <x-force-delete-modal id="fd-modal-students-single" ... />
 *   </div>
 *
 * Communication: factory dispatches `force-delete-open` CustomEvent with modalId.
 * Modal's x-data init() picks up the triggering Alpine component via the event
 * and sets _parent reference, enabling cascade getters without a shared store.
 */

export function forceDeleteActions(config) {
    return {
        // ── State ──────────────────────────────────────────────────────────
        selectedIds: [],
        selectedNames: [],
        selectedCascadeCounts: [],
        selectedCascadeLists: [],

        // ── Config ─────────────────────────────────────────────────────────
        resource: config.resource || 'items',
        label: config.label || 'item',
        itemName: config.itemName || 'Item',
        bulkForceUrl: config.bulkForceUrl || '',
        forceDestroyUrl: config.forceDestroyUrl || ((id) => `#`),
        listSelector: config.listSelector || 'table',
        modalPrefix: config.modalPrefix || 'fd-modal',

        // ── Init ────────────────────────────────────────────────────────────
        init() {
            window.addEventListener('force-delete-success', (e) => this.onDeleteSuccess(e));
            this.restoreCheckedState();
        },

        // ── Checkbox helpers ────────────────────────────────────────────────
        toggleOne(event) {
            const cb = event.target;
            const id = parseInt(cb.value);
            const idx = this.selectedIds.indexOf(id);

            if (cb.checked) {
                if (idx === -1) {
                    this.selectedIds.push(id);
                    this.selectedNames.push(cb.dataset.name || '');
                    this.selectedCascadeCounts.push(parseInt(cb.dataset.cascadeCount || 0));
                    try {
                        this.selectedCascadeLists.push(JSON.parse(cb.dataset.cascadeList || '[]'));
                    } catch {
                        this.selectedCascadeLists.push([]);
                    }
                }
            } else {
                if (idx !== -1) {
                    this.selectedIds.splice(idx, 1);
                    this.selectedNames.splice(idx, 1);
                    this.selectedCascadeCounts.splice(idx, 1);
                    this.selectedCascadeLists.splice(idx, 1);
                }
            }
        },

        toggleAll(event) {
            const checked = event.target.checked;
            document.querySelectorAll('.row-checkbox').forEach((cb) => {
                if (checked !== cb.checked) {
                    cb.checked = checked;
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        },

        restoreCheckedState() {
            // Re-check any checkboxes that match previously selected IDs (session restore)
        },

        // ── Modal openers — dispatch CustomEvent for modal to pick up ───────
        openPerRowModal(id, name, cascadeCount, cascadeList) {
            this.pendingId = id;
            this.pendingName = name;
            this.pendingCascadeCount = cascadeCount || 0;
            this.cascadeList = cascadeList || [];
            window.dispatchEvent(new CustomEvent('force-delete-open', {
                bubbles: true,
                detail: {
                    modalId: `${this.modalPrefix}-single`,
                    id,
                    name,
                    cascadeCount: cascadeCount || 0,
                    cascadeList: cascadeList || [],
                    _alpineParent: this,   // reference to the triggering Alpine component
                }
            }));
        },

        openBulkModal() {
            if (this.selectedIds.length === 0) return;

            window.dispatchEvent(new CustomEvent('force-delete-open', {
                bubbles: true,
                detail: {
                    modalId: `${this.modalPrefix}-bulk`,
                    bulkCount: this.selectedIds.length,
                    totalCascadeCount: this.selectedCascadeCounts.reduce((a, b) => a + b, 0),
                    allCascadeLists: this.selectedCascadeLists,
                    _alpineParent: this,
                }
            }));
        },

        // ── Success handler ─────────────────────────────────────────────────
        onDeleteSuccess(e) {
            const modalId = e.detail?.modalId || '';
            const isBulk = modalId.includes('bulk');

            if (isBulk) {
                this.selectedIds.forEach((id) => {
                    document.querySelector(`[data-row-id="${id}"]`)?.remove();
                });
                this.selectedIds = [];
                this.selectedNames = [];
                this.selectedCascadeCounts = [];
                this.selectedCascadeLists = [];
                document.querySelectorAll('.row-checkbox').forEach((cb) => { cb.checked = false; });
            } else {
                const deletedId = e.detail?.deletedId;
                if (deletedId) {
                    document.querySelector(`[data-row-id="${deletedId}"]`)?.remove();
                }
            }

            this.refreshTable().catch(() => {});
        },

        async refreshTable() {
            const listEl = document.querySelector(this.listSelector);
            if (!listEl) return;
            try {
                const resp = await window.Ajax.get(window.location.href);
                const parser = new DOMParser();
                const doc = parser.parseFromString(resp.data, 'text/html');
                const newTable = doc.querySelector(this.listSelector);
                if (newTable) listEl.innerHTML = newTable.innerHTML;
                const pagination = document.querySelector('.paging-links');
                const newPagination = doc.querySelector('.paging-links');
                if (pagination && newPagination) pagination.innerHTML = newPagination.innerHTML;
            } catch {
                // Silent fail
            }
        },
    };
}
