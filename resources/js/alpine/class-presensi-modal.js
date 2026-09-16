/**
 * Alpine factory for class-student-sessions (kalender presensi kelas) AJAX modal.
 * - Extends crudModal for modal infrastructure (openCreate, openEdit, close, etc.)
 * - Overrides submit() to send JSON (no file upload in this form)
 * - Manages program → teachers + students cascade via window globals injected by controller
 * - Teacher multi-select with search, student multi-select with "Pilih Semua"
 *
 * Used on admin/class-student-sessions/calendar.blade.php.
 */
import { crudModal } from './crud-modal';

export function classPresensiModal(config) {
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

        // ── Lookup data (injected by controller as window globals) ─────────
        teachersByProgram: window.__css_teachersByProgram__ || {},
        studentsByProgram: window.__css_studentsByProgram__ || {},

        // ── Form state ────────────────────────────────────────────────────
        programId: '',
        teachers: [],
        students: [],
        selectedTeacherIds: [],
        selectedStudentIds: [],

        // Pre-populated state from edit mode (injected by controller as window globals)
        _existingTeacherIds: window.__css_session_teachers__?.map(t => t.id) || [],
        _existingStudentIds: window.__css_session_student_ids__ || [],
        _existingEnrollmentMap: window.__css_session_enrollment_map__ || {},

        // ── Program change ───────────────────────────────────────────────
        onProgramChange() {
            const pid = parseInt(this.programId);
            this.teachers = this.teachersByProgram[pid] || [];
            this.students = this.studentsByProgram[pid] || [];
            this.selectedTeacherIds = [];
            this.selectedStudentIds = [];
            this.syncHiddenInputs();
        },

        // ── Teacher toggle ───────────────────────────────────────────────
        toggleTeacher(teacherId) {
            const idx = this.selectedTeacherIds.indexOf(teacherId);
            if (idx >= 0) {
                this.selectedTeacherIds.splice(idx, 1);
            } else {
                this.selectedTeacherIds.push(teacherId);
            }
            this.syncHiddenInputs();
        },

        // ── Student toggle ───────────────────────────────────────────────
        toggleStudent(studentId) {
            const idx = this.selectedStudentIds.indexOf(studentId);
            if (idx >= 0) {
                this.selectedStudentIds.splice(idx, 1);
            } else {
                this.selectedStudentIds.push(studentId);
            }
            this.syncHiddenInputs();
        },

        selectAllStudents() {
            this.students.forEach(s => {
                if (!this.selectedStudentIds.includes(s.student_id)) {
                    this.selectedStudentIds.push(s.student_id);
                }
            });
            this.syncHiddenInputs();
        },

        // ── Hidden inputs sync ───────────────────────────────────────────
        syncHiddenInputs() {
            const teacherContainer = document.getElementById('teacher-hidden-container');
            if (teacherContainer) {
                teacherContainer.innerHTML = '';
                this.selectedTeacherIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'teacher_ids[]';
                    input.value = id;
                    teacherContainer.appendChild(input);
                });
            }

            const studentContainer = document.getElementById('student-hidden-container');
            if (studentContainer) {
                studentContainer.innerHTML = '';
                this.selectedStudentIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'student_enrollment_map[]';
                    input.value = id;
                    studentContainer.appendChild(input);
                });
            }
        },

        // ── Post-edit-load initialization ─────────────────────────────────
        // After openEdit renders the form partial, populate from window globals
        initFormFromGlobals() {
            // Restore selected teachers from global
            this.selectedTeacherIds = this._existingTeacherIds.filter(id =>
                this.teachers.some(t => t.id === id)
            );

            // Restore selected students
            this.selectedStudentIds = [...this._existingStudentIds];

            // Set program from existing session
            const form = document.getElementById('crud-form');
            if (form) {
                const programSelect = form.querySelector('[name="program_id"]');
                if (programSelect && programSelect.value) {
                    this.programId = programSelect.value;
                    const pid = parseInt(this.programId);
                    this.teachers = this.teachersByProgram[pid] || [];
                    this.students = this.studentsByProgram[pid] || [];
                }
            }

            this.syncHiddenInputs();
            window.__cssRenderAll?.();
        },

        // ── Open edit ────────────────────────────────────────────────────
        async openEdit(id) {
            await base.openEdit.call(this, id);
            this.$nextTick(() => {
                this.initFormFromGlobals();
            });
        },

        // ── Open create with optional params ─────────────────────────────
        openCreateWithDate(date, programId) {
            // Dynamically build the create URL with query params
            const params = new URLSearchParams();
            if (date) params.set('session_date', date);
            if (programId) params.set('program_id', programId);
            const query = params.toString();
            const baseUrl = this.createUrl.call ? this.createUrl() : this.createUrl;
            const url = query ? `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}${query}` : baseUrl;

            // Override createUrl for this call only
            const originalCreateUrl = this.createUrl;
            this.createUrl = url;
            this.openCreate().finally(() => {
                this.createUrl = originalCreateUrl;
            });
        },

        // ── Submit ───────────────────────────────────────────────────────
        async submit() {
            const form = document.getElementById('crud-form');
            if (!form) {
                console.error('[classPresensiModal] #crud-form not found');
                return;
            }

            this.submitting = true;
            this.errors = {};

            // Clear previous error display
            form.querySelectorAll('[class^="crud-error-"]').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });
            form.querySelectorAll('[class^="crud-field-"]').forEach(el => {
                el.classList.remove('border-rose-400', 'ring-1', 'ring-rose-300');
                el.classList.add('border-gray-300');
            });

            // Build plain object — sent as JSON body (Laravel decodes natively).
            // Strip trailing "[]" so multiple hidden inputs with array names
            // (e.g. teacher_ids[]) accumulate into one JSON array, and the
            // resulting key matches Laravel's validation rules (which expect
            // "teacher_ids", not "teacher_ids[]"). When the name has "[]", the
            // value is ALWAYS an array — even for a single input — because
            // Laravel's `array` validation rule rejects a bare string.
            const data = {};
            for (const el of form.querySelectorAll('input[name], select[name], textarea[name]')) {
                let value;
                if (el.type === 'checkbox') {
                    if (!el.checked) continue;
                    value = el.value || 'on';
                } else if (el.type === 'radio') {
                    if (!el.checked) continue;
                    value = el.value;
                } else if (el.tagName === 'SELECT' && el.multiple) {
                    value = Array.from(el.selectedOptions).map(o => o.value);
                } else if (el.type === 'hidden' || (el.value !== undefined && el.value !== '')) {
                    value = el.value;
                } else {
                    continue;
                }
                const isArray = el.name.endsWith('[]');
                const key = isArray ? el.name.slice(0, -2) : el.name;
                if (key in data) {
                    if (!Array.isArray(data[key])) data[key] = [data[key]];
                    data[key].push(value);
                } else if (isArray) {
                    data[key] = [value];
                } else {
                    data[key] = value;
                }
            }

            const url = this.isEdit
                ? (typeof this.updateUrl === 'function' ? this.updateUrl(this.currentId) : `${this.updateUrl}/${this.currentId}`)
                : this.storeUrl;
            const method = this.isEdit ? 'put' : 'post';

            try {
                await window.Ajax[method](url, data);
                window.Toast?.success(this.isEdit ? 'Presensi kelas berhasil diperbarui.' : 'Presensi kelas berhasil dicatat.');
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

window.classPresensiModal = classPresensiModal;
