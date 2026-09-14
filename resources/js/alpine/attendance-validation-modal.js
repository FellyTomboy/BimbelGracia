/**
 * Alpine.js factory for attendance notification review page.
 * Handles:
 * - Uphold rejection: modal confirmation → AJAX POST
 * - Dismiss: quick AJAX POST → remove row
 */
export function attendanceValidationModal({}) {
    return {
        // ── Modal state ──────────────────────────────────────────────────────────
        modalOpen: false,
        modalTitle: 'Konfirmasi Ditolak',
        modalBody: '',
        submitting: false,

        // ── Open uphold confirmation modal ───────────────────────────────────────
        async openUpholdModal(attendanceId) {
            try {
                const resp = await window.Ajax.get(
                    `/admin/notifikasi-presensi/${attendanceId}/preview-uphold`
                );
                this.modalBody = resp.data.html;
                this.modalTitle = resp.data.title ?? 'Konfirmasi Ditolak';
                this.modalOpen = true;

                this.$nextTick(() => {
                    const form = document.getElementById('att-val-modal-form');
                    if (form) window.Alpine.initTree(form);
                });
            } catch (e) {
                // error already handled by Ajax utility
            }
        },

        // ── Submit uphold ───────────────────────────────────────────────────────
        async submitUphold() {
            const form = document.getElementById('att-val-modal-form');
            if (!form) return;

            this.submitting = true;
            try {
                const formData = new FormData(form);
                const resp = await window.Ajax.post(
                    formData.get('_action') // holds URL
                        ? `/admin/notifikasi-presensi/${formData.get('_action')}/confirm`
                        : `/admin/notifikasi-presensi/${form.dataset.id}/confirm`,
                    formData
                );
                window.Toast?.success(resp.data?.message ?? 'Penolakan dikonfirmasi.');
                this.modalOpen = false;
                // Remove row from table
                const row = document.querySelector(`tr[data-attendance-id="${form.dataset.id}"]`);
                if (row) row.remove();
            } catch (e) {
                // handled by Ajax utility
            } finally {
                this.submitting = false;
            }
        },

        // ── Dismiss (quick action, no modal) ────────────────────────────────────
        async dismiss(attendanceId) {
            if (!confirm('Tolak konfirmasi penolakan ini? Status presensi tidak akan diubah.')) return;

            try {
                await window.Ajax.post(`/admin/notifikasi-presensi/${attendanceId}/dismiss`);
                window.Toast?.success('Penolakan dibatalkan.');
                const row = document.querySelector(`tr[data-attendance-id="${attendanceId}"]`);
                if (row) row.remove();
            } catch (e) {
                // handled by Ajax utility
            }
        },

        // ── Close modal ────────────────────────────────────────────────────────
        close() {
            this.modalOpen = false;
            this.modalBody = '';
            this.submitting = false;
        },
    };
}
