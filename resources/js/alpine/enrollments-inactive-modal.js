/**
 * Alpine.js factory for the Enrollments Inactive page.
 * Separate file prevents ModPageSpeed from rewriting inline x-data expressions.
 */

export function enrollmentsInactiveModal(bulkForceUrl, flashMessage, showFlash) {
    return {
        // ── Force Delete State ───────────────────────────────────────────────────
        fd: window.forceDeleteActions({
            resource: 'enrollments',
            label: 'enrollment',
            itemName: 'Enrollment',
            modalPrefix: 'fd-modal-enrollments',
            bulkForceUrl: bulkForceUrl,
            forceDestroyUrl: (id) => '/admin/enrollments/' + id + '/force-destroy',
            listSelector: 'table',
        }),

        // ── Flash ───────────────────────────────────────────────────────────────
        flashMessage: flashMessage,
        showFlash: showFlash,
        flashTimer: null,
        init() { if (this.showFlash) this._startTimer(); },
        _startTimer() {
            if (this.flashTimer) clearTimeout(this.flashTimer);
            this.showFlash = true;
            this.flashTimer = setTimeout(() => { this.showFlash = false; }, 4000);
        },
        setFlash(msg) { this.flashMessage = msg; this._startTimer(); },

        // ── Restore modal ───────────────────────────────────────────────────────
        restoreConfirmId: null,
        restoreLoading: false,
        confirmRestore(id) { this.restoreConfirmId = id; },
        cancelRestore() { this.restoreConfirmId = null; },

        async restoreEnrollment(enrollmentId) {
            this.restoreLoading = true;
            try {
                const resp = await window.Ajax.post('/admin/enrollments/' + enrollmentId + '/restore');
                window.Toast?.success(resp.data?.message || 'Berhasil dipulihkan.');
                this.restoreConfirmId = null;
                this.removeRow(enrollmentId);
                this.setFlash(resp.data?.message || 'Berhasil dipulihkan.');
            } catch (e) {
                if (e.response?.status !== 422) window.Toast?.error('Gagal memulihkan enrollment.');
            } finally {
                this.restoreLoading = false;
            }
        },

        removeRow(id) {
            const row = document.querySelector("[data-enrollment-id='" + id + "']");
            if (!row) return;
            row.style.transition = 'opacity 0.3s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        },
    };
}
