/**
 * Alpine.js factory for presensi show/detail page.
 * Handles: validate status, fix enrollment, delete attendance.
 * Uses modalAction discriminator ('validate'|'fix-enrollment'|'delete').
 */
export function attendanceDetailModal({}) {
    return {
        // ── Modal state ──────────────────────────────────────────────────────────
        modalOpen: false,
        modalAction: '',   // 'validate' | 'fix-enrollment' | 'delete'
        modalTitle: '',
        modalBody: '',
        submitting: false,
        attendanceId: null,

        // ── Open validate modal ───────────────────────────────────────────────────
        async openValidateModal(id) {
            this.attendanceId = id;
            this.modalAction = 'validate';
            this.modalTitle = 'Validasi Presensi';
            try {
                const resp = await window.Ajax.get(`/admin/presensi/${id}/preview-validate`);
                this.modalBody = resp.data.html;
                this.modalOpen = true;
                this.$nextTick(() => {
                    const form = document.getElementById('att-detail-form');
                    if (form) window.Alpine.initTree(form);
                });
            } catch (e) {
                // handled by Ajax utility
            }
        },

        // ── Open fix enrollment modal ───────────────────────────────────────────
        async openFixEnrollmentModal(id) {
            this.attendanceId = id;
            this.modalAction = 'fix-enrollment';
            this.modalTitle = 'Perbaiki Enrollment';
            try {
                const resp = await window.Ajax.get(`/admin/presensi/${id}/preview-fix-enrollment`);
                this.modalBody = resp.data.html;
                this.modalOpen = true;
                this.$nextTick(() => {
                    const form = document.getElementById('att-detail-form');
                    if (form) window.Alpine.initTree(form);
                });
            } catch (e) {
                // handled by Ajax utility
            }
        },

        // ── Open delete confirmation modal ───────────────────────────────────────
        async openDeleteModal(id) {
            this.attendanceId = id;
            this.modalAction = 'delete';
            this.modalTitle = 'Hapus Presensi?';
            try {
                const resp = await window.Ajax.get(`/admin/presensi/${id}/preview-delete`);
                this.modalBody = resp.data.html;
                this.modalOpen = true;
                this.$nextTick(() => {
                    const form = document.getElementById('att-detail-form');
                    if (form) window.Alpine.initTree(form);
                });
            } catch (e) {
                // handled by Ajax utility
            }
        },

        // ── Submit modal (routes by action) ─────────────────────────────────────
        async submitModal() {
            this.submitting = true;
            const id = this.attendanceId;

            try {
                if (this.modalAction === 'validate') {
                    await this._doValidate(id);
                } else if (this.modalAction === 'fix-enrollment') {
                    await this._doFixEnrollment(id);
                } else if (this.modalAction === 'delete') {
                    await this._doDelete(id);
                }
            } catch (e) {
                // handled by Ajax utility
            } finally {
                this.submitting = false;
            }
        },

        async _doValidate(id) {
            const form = document.getElementById('att-detail-form');
            if (!form) return;
            const formData = new FormData(form);
            const resp = await window.Ajax.post(
                `/admin/presensi/${id}/validate`,
                formData
            );
            window.Toast?.success(resp.data?.message ?? 'Presensi divalidasi.');
            this.modalOpen = false;
            // Update status badge inline
            const statusVal = formData.get('status');
            const badgeEl = document.querySelector('[data-status-badge]');
            if (badgeEl && statusVal) {
                const labels = { terima: 'Diterima', terlambat: 'Terlambat', ditolak: 'Ditolak' };
                const colors = { terima: 'emerald', terlambat: 'amber', ditolak: 'rose' };
                badgeEl.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${colors[statusVal]}-50 text-${colors[statusVal]}-700 border border-${colors[statusVal]}-200`;
                badgeEl.textContent = labels[statusVal] ?? statusVal;
            }
        },

        async _doFixEnrollment(id) {
            const form = document.getElementById('att-detail-form');
            if (!form) return;
            const formData = new FormData(form);
            const resp = await window.Ajax.post(
                `/admin/presensi/${id}/enrollment`,
                formData
            );
            window.Toast?.success(resp.data?.message ?? 'Enrollment diperbarui.');
            this.modalOpen = false;
            setTimeout(() => location.reload(), 500);
        },

        async _doDelete(id) {
            const form = document.getElementById('att-detail-form');
            if (!form) return;
            const formData = new FormData(form);
            const resp = await window.Ajax.delete(
                `/admin/presensi/${id}`,
                formData
            );
            window.Toast?.success(resp.data?.message ?? 'Presensi dihapus.');
            this.modalOpen = false;
            window.location.href = '/admin/presensi';
        },

        // ── Uphold rejection modal (for parent rejection confirmation) ─────────
        upholdModalOpen: false,
        upholdModalBody: '',
        upholdSubmitting: false,
        upholdAttendanceId: null,

        async openUpholdModal(id) {
            this.upholdAttendanceId = id;
            try {
                const resp = await window.Ajax.get(`/admin/notifikasi-presensi/${id}/preview-uphold`);
                this.upholdModalBody = resp.data.html;
                this.upholdModalOpen = true;
                this.$nextTick(() => {
                    const form = document.getElementById('att-val-modal-form');
                    if (form) window.Alpine.initTree(form);
                });
            } catch (e) {}
        },

        async submitUphold() {
            const form = document.getElementById('att-val-modal-form');
            if (!form) return;
            this.upholdSubmitting = true;
            try {
                const formData = new FormData(form);
                const resp = await window.Ajax.post(
                    `/admin/notifikasi-presensi/${this.upholdAttendanceId}/confirm`,
                    formData
                );
                window.Toast?.success(resp.data?.message ?? 'Penolakan dikonfirmasi.');
                this.upholdModalOpen = false;
                setTimeout(() => location.reload(), 500);
            } catch (e) {
            } finally {
                this.upholdSubmitting = false;
            }
        },

        closeUphold() {
            this.upholdModalOpen = false;
            this.upholdModalBody = '';
        },

        // ── Close modal ─────────────────────────────────────────────────────────
        close() {
            this.modalOpen = false;
            this.modalBody = '';
            this.submitting = false;
        },
    };
}
