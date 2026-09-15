/**
 * Alpine factory for presensi privat — single-session attendance form.
 * - Extends crudModal for modal infrastructure (openCreate, openEdit, close, etc.)
 * - Overrides submit() to use FormData (required for file upload)
 * - Manages enrollment → students cascade and student checkbox selection via Alpine state
 *
 * Used on admin/presensi/index.blade.php when billingMode === 'daily'.
 */
import { crudModal } from './crud-modal';

export function presensiModal(config) {
    const base = crudModal({
        createUrl: config.createUrl || '',
        storeUrl: config.storeUrl || '',
        editUrl: config.editUrl || ((id) => ''),
        updateUrl: config.updateUrl || ((id) => ''),
        deleteUrl: config.deleteUrl || ((id) => ''),
        deleteMethod: config.deleteMethod || 'delete',
        listSelector: config.listSelector || 'table',
    });

    return {
        ...base,

        // ── Enrollment → Students cascade ───────────────────────────────────
        selectedEnrollmentId: '',
        availableStudents: [],
        selectedProgramType: 'privat',
        studentIds: [],

        // Called by the form partial after Alpine.initTree() initializes the x-html content.
        // Finds the selected enrollment option and loads its students into Alpine state.
        loadStudents(enrollmentId) {
            const option = document.querySelector(`#enrollment-select option[value="${enrollmentId}"]`);
            if (!option) {
                this.availableStudents = [];
                this.selectedProgramType = 'privat';
                this.studentIds = [];
                return;
            }
            try {
                this.availableStudents = JSON.parse(option.dataset.students || '[]');
            } catch (e) {
                this.availableStudents = [];
            }
            this.selectedProgramType = option.dataset.type || 'privat';
            // Pre-select all students for the chosen enrollment
            this.studentIds = this.availableStudents.map(st => st.id);
        },

        // Toggle a student in/out of the selected studentIds array
        toggleStudent(id) {
            const idx = this.studentIds.indexOf(id);
            if (idx >= 0) {
                this.studentIds.splice(idx, 1);
            } else {
                this.studentIds.push(id);
            }
        },

        // Override openEdit to load students for the already-selected enrollment
        // after Alpine.initTree() has activated the form partial.
        async openEdit(id) {
            await base.openEdit.call(this, id);
            this.$nextTick(() => {
                // After form is rendered, find the currently-selected enrollment
                const select = document.getElementById('enrollment-select');
                if (select && select.value) {
                    this.selectedEnrollmentId = select.value;
                    this.loadStudents(this.selectedEnrollmentId);
                }
            });
        },

        // ── Submit ───────────────────────────────────────────────────────
        async submit() {
            const form = document.getElementById('crud-form');
            if (!form) {
                console.error('[presensiModal] #crud-form not found');
                return;
            }

            this.submitting = true;
            this.errors = {};

            // Clear previous error display (vanilla DOM — no Alpine bindings inside x-html)
            form.querySelectorAll('[class^="crud-error-"]').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });
            form.querySelectorAll('[class^="crud-field-"]').forEach(el => {
                el.classList.remove('border-rose-400', 'ring-1', 'ring-rose-300');
                el.classList.add('border-gray-300');
            });

            // Build FormData from the form element (required for file upload)
            const fd = new FormData(form);

            // Append student_ids[] from Alpine state — checkboxes with name="student_ids[]"
            // are rendered without the checked attribute (relying on Alpine state), so we
            // manually append each selected ID instead.
            this.studentIds.forEach(id => fd.append('student_ids[]', id));

            const url = this.isEdit
                ? (typeof this.updateUrl === 'function' ? this.updateUrl(this.currentId) : `${this.updateUrl}/${this.currentId}`)
                : this.storeUrl;
            const method = this.isEdit ? 'put' : 'post';

            try {
                await window.Ajax[method](url, fd);
                window.Toast?.success(this.isEdit ? 'Presensi berhasil diperbarui.' : 'Presensi berhasil dicatat.');
            } catch (e) {
                if (e.response?.status === 422) {
                    const errors = e.response.data.errors || {};
                    Object.entries(errors).forEach(([field, messages]) => {
                        const errEl = form.querySelector(`.crud-error-${field}`);
                        if (errEl) {
                            errEl.textContent = Array.isArray(messages) ? messages.join(', ') : messages;
                            errEl.style.display = 'block';
                        }
                        const fieldEl = form.querySelector(`.crud-field-${field}`);
                        if (fieldEl) {
                            fieldEl.classList.remove('border-gray-300');
                            fieldEl.classList.add('border-rose-400', 'ring-1', 'ring-rose-300');
                        }
                    });
                    // Keep modal open so user can fix errors
                    this.submitting = false;
                    return;
                }
            } finally {
                this.submitting = false;
            }

            this.close();
            this.refreshTable().catch(() => {});
        },
    };
}

window.presensiModal = presensiModal;
