export function presensiCreateModal({}) {
    return {
        modalOpen: false,
        submitting: false,

        // Session state
        sessions: [{ id: Date.now(), date: '', studentIds: [], notes: '', imagePreview: null }],
        selectedEnrollmentId: '',
        selectedProgramType: 'privat',
        availableStudents: [],
        activeTab: 0,
        maxSessions: 31,
        latePenaltyEnabled: false,
        billingMode: 'monthly',

        init() {
            // Read from data attributes on the root element
            const el = this.$el;
            this.latePenaltyEnabled = el.dataset.latePenalty === 'true';
            this.billingMode = el.dataset.billingMode || 'monthly';
            // React to enrollment changes
            this.$watch('selectedEnrollmentId', () => {
                if (!this.modalOpen) return;
                this.loadStudentsFromSelect();
            });
        },

        initFromData() {
            // Called by x-init, also reads from data attributes
            const el = this.$el;
            this.latePenaltyEnabled = el.dataset.latePenalty === 'true';
            this.billingMode = el.dataset.billingMode || 'monthly';
        },

        openCreateModal() {
            this.resetState();
            this.modalOpen = true;
        },

        async submitModal() {
            if (!this.validateBeforeSubmit()) return;

            const form = document.getElementById('prc-form');
            if (!form) return;

            this.submitting = true;

            try {
                const formData = new FormData(form);
                await window.Ajax.post('/admin/presensi/bulk', formData);
                window.Toast?.success('Presensi berhasil dicatat.');
                this.close();
                setTimeout(() => location.reload(), 500);
            } catch (err) {
                if (err.response?.status === 422) {
                    const errors = err.response.data.errors;
                    this.showErrors(errors);
                }
                this.submitting = false;
            }
        },

        close() {
            this.modalOpen = false;
            this.submitting = false;
            this.resetState();
        },

        resetState() {
            this.sessions = [{ id: Date.now(), date: '', studentIds: [], notes: '', imagePreview: null }];
            this.selectedEnrollmentId = '';
            this.selectedProgramType = 'privat';
            this.availableStudents = [];
            this.activeTab = 0;
        },

        loadStudentsFromSelect() {
            const select = document.getElementById('prc-enrollment-select');
            if (!select) return;
            const option = select.options[select.selectedIndex];
            if (!option) {
                this.availableStudents = [];
                this.selectedProgramType = 'privat';
                return;
            }
            try {
                this.availableStudents = JSON.parse(option.dataset.students || '[]');
            } catch(e) {
                this.availableStudents = [];
            }
            this.selectedProgramType = option.dataset.type || 'privat';
            // Pre-check all students
            this.sessions.forEach(s => {
                s.studentIds = [...this.availableStudents.map(st => st.id)];
            });
        },

        addSession() {
            if (this.sessions.length >= this.maxSessions) return;
            this.sessions.push({
                id: Date.now(),
                date: '',
                studentIds: [...this.availableStudents.map(st => st.id)],
                notes: '',
                imagePreview: null,
            });
            this.activeTab = this.sessions.length - 1;
        },

        removeSession(index) {
            if (this.sessions.length === 1) return;
            this.sessions.splice(index, 1);
            if (this.activeTab >= this.sessions.length) {
                this.activeTab = this.sessions.length - 1;
            }
        },

        handleImageChange(event, index) {
            const file = event.target.files[0];
            if (!file) return;
            this.sessions[index].imagePreview = null;
            const reader = new FileReader();
            const self = this;
            reader.onload = (e) => { self.sessions[index].imagePreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        validateBeforeSubmit() {
            if (!this.selectedEnrollmentId) {
                window.Toast?.error('Pilih enrollment terlebih dahulu.');
                return false;
            }
            const emptyDateSessions = this.sessions.filter(s => !s.date);
            if (emptyDateSessions.length > 0) {
                window.Toast?.error('Semua tab harus memiliki tanggal les.');
                return false;
            }
            if (this.selectedProgramType !== 'kelas') {
                const emptyStudentSessions = this.sessions.filter(s => s.studentIds.length === 0);
                if (emptyStudentSessions.length > 0) {
                    window.Toast?.error('Setiap tanggal harus memiliki minimal 1 murid hadir.');
                    return false;
                }
            }
            return true;
        },

        formatDate(dateStr) {
            if (!dateStr) return 'Baru';
            const d = new Date(dateStr + 'T00:00:00');
            const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            return d.getDate().toString().padStart(2,'0') + ' ' + months[d.getMonth()];
        },

        showErrors(errors) {
            // Clear previous errors
            document.querySelectorAll('.crud-error').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            Object.keys(errors).forEach(field => {
                const el = document.querySelector(`.crud-error-${field.replace(/\./g, '-')}`);
                if (el) {
                    el.textContent = errors[field][0];
                    el.style.display = 'block';
                }
            });
        },
    };
}
