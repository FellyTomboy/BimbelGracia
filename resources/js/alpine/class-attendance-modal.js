/**
 * Alpine.js factory for class attendance fill-students modal.
 * Handles: open modal with student checkboxes, submit via AJAX.
 */
export function classAttendanceModal({}) {
    return {
        modalOpen: false,
        modalBody: '',
        submitting: false,
        attendanceId: null,

        async openFillModal(id) {
            this.attendanceId = id;
            try {
                const resp = await window.Ajax.get(`/admin/class-attendance/${id}/preview-fill`);
                this.modalBody = resp.data.html;
                this.modalOpen = true;
                this.$nextTick(() => {
                    const form = document.getElementById('cls-att-form');
                    if (form) window.Alpine.initTree(form);
                });
            } catch (e) {}
        },

        async submitModal() {
            this.submitting = true;
            const form = document.getElementById('cls-att-form');
            if (!form) { this.submitting = false; return; }

            try {
                const formData = new FormData(form);
                const resp = await window.Ajax.put(
                    `/admin/class-attendance/${this.attendanceId}`,
                    formData
                );
                window.Toast?.success(resp.data?.message ?? 'Daftar murid disimpan.');
                this.modalOpen = false;
                setTimeout(() => location.reload(), 500);
            } catch (e) {
            } finally {
                this.submitting = false;
            }
        },

        close() {
            this.modalOpen = false;
            this.modalBody = '';
        },
    };
}
