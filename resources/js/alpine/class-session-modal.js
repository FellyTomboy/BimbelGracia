/**
 * Alpine.js factory for class student session delete confirmation.
 */
export function classSessionModal({}) {
    return {
        modalOpen: false,
        modalBody: '',
        submitting: false,
        sessionId: null,

        async openDeleteModal(id) {
            this.sessionId = id;
            try {
                const resp = await window.Ajax.get(`/admin/class-student-sessions/${id}/preview-delete`);
                this.modalBody = resp.data.html;
                this.modalOpen = true;
                this.$nextTick(() => {
                    const form = document.getElementById('css-modal-form');
                    if (form) window.Alpine.initTree(form);
                });
            } catch (e) {}
        },

        async submitModal() {
            this.submitting = true;
            try {
                await window.Ajax.delete(`/admin/class-student-sessions/${this.sessionId}`);
                window.Toast?.success('Sesi kelas berhasil dihapus.');
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
