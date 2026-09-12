/**
 * Alpine.js factory for the Students Inactive page.
 * Lives in a separate .js file so it is NOT inlined in the HTML <div x-data="...">
 * attribute — this prevents ModPageSpeed from rewriting the JavaScript expression
 * and corrupting it.
 */

export function studentsInactiveModal() {
    return {
        // ── Force Delete State (from factory) ──────────────────────────────────
        fd: window.forceDeleteActions({
            resource: 'students',
            label: 'murid',
            itemName: 'Murid',
            modalPrefix: 'fd-modal-students',
            bulkForceUrl: window.__bulkForceUrls?.students ?? '/admin/students/bulk-force-destroy',
            forceDestroyUrl: (id) => '/admin/students/' + id + '/force-destroy',
            listSelector: 'table',
        }),

        // ── Restore Modal State ─────────────────────────────────────────────────
        restoreModalOpen: false,
        restoreLoading: false,
        restoreError: '',
        restoreStudentId: null,
        restoreStudentName: '',
        restoreOriginalParent: '',
        restoreSelectedParentId: '',
        restoreNewParentName: '',
        restoreNewParentPhone: '',

        init() {
            window.addEventListener('force-delete-success', (e) => {
                if (e.detail?.modalId?.startsWith('fd-modal-students')) {
                    location.reload();
                }
            });
        },

        openRestoreModal(studentId, studentName, originalParent) {
            this.restoreStudentId = studentId;
            this.restoreStudentName = studentName;
            this.restoreOriginalParent = originalParent || 'Tidak ada';
            this.restoreSelectedParentId = '';
            this.restoreNewParentName = '';
            this.restoreNewParentPhone = '';
            this.restoreError = '';
            this.restoreModalOpen = true;
        },

        closeRestoreModal() {
            this.restoreModalOpen = false;
        },

        async submitRestore() {
            if (this.restoreLoading) return;
            this.restoreLoading = true;
            this.restoreError = '';
            const params = new URLSearchParams();
            params.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
            if (this.restoreSelectedParentId) params.append('parent_id', this.restoreSelectedParentId);
            if (this.restoreNewParentName.trim()) params.append('new_parent_name', this.restoreNewParentName);
            if (this.restoreNewParentPhone.trim()) params.append('new_parent_phone', this.restoreNewParentPhone);
            try {
                const resp = await window.Ajax.post('/admin/students/' + this.restoreStudentId + '/restore', params);
                window.Toast?.success(resp.data?.message || 'Murid berhasil dipulihkan.');
                this.closeRestoreModal();
                const row = document.querySelector("[data-row-id='" + this.restoreStudentId + "']");
                if (row) row.remove();
            } catch (e) {
                if (e.response?.status === 422) {
                    const errs = e.response.data?.errors || {};
                    this.restoreError = Object.values(errs).flat().join(', ');
                } else {
                    this.restoreError = 'Gagal memulihkan murid.';
                }
            } finally {
                this.restoreLoading = false;
            }
        },
    };
}
