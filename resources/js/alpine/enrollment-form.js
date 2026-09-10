/**
 * Enrollment Form Alpine Component
 * Used by both create.blade.php / edit.blade.php (standalone) and
 * _form.blade.php (modal). Supports idPrefix for environments where
 * the same IDs exist in the surrounding page.
 *
 * Handles:
 * - Type/program/student mode toggling (kelas vs privat)
 * - Pricing tier dynamic rows
 * - AJAX form submission
 * - Inline validation error display
 * - Success redirect
 */

export function EnrollmentForm(config = {}) {
    const id = (suffix) => (config.idPrefix || '') + suffix;

    return {
        // ── Config ──────────────────────────────────────────────────────────
        formUrl: config.formUrl || '',
        enrollmentId: config.enrollmentId || null,
        initialType: config.initialType || 'privat',
        initialStudentCount: config.initialStudentCount || 3,
        initialParentTiers: config.initialParentTiers || {},
        initialTeacherTiers: config.initialTeacherTiers || {},

        // ── Form state ──────────────────────────────────────────────────────
        submitting: false,
        errors: {},

        // ── Dynamic field state ─────────────────────────────────────────────
        isKelasMode: false,

        // ── Pricing tiers ────────────────────────────────────────────────────
        tierCount: 3,
        parentTiers: {},
        teacherTiers: {},

        // ── Init ────────────────────────────────────────────────────────────
        init() {
            this.tierCount = this.initialStudentCount;
            this.parentTiers = { ...this.initialParentTiers };
            this.teacherTiers = { ...this.initialTeacherTiers };
            this.isKelasMode = this.checkKelasMode();
            this.updateFieldVisibility();
            this.filterPrograms();
            this.updatePricingVisibility();
        },

        // ── Computed ────────────────────────────────────────────────────────
        checkKelasMode() {
            // Returns true if type-select is 'kelas', regardless of program selection.
            // Originally also required the selected program's data-type to be 'kelas',
            // but that meant create-kelas (where no program is pre-selected) showed
            // as PRIVAT mode until the user picked a program — confusing UX and
            // inconsistent with edit-kelas (which is always kelas mode from the start).
            // Server-side validation in EnrollmentController::isKelasMode() still
            // rejects submissions where type=kelas but program.type !== 'kelas'.
            const select = document.getElementById(id('type-select'));
            return select?.value === 'kelas';
        },

        getCheckedStudentCount() {
            return document.querySelectorAll('#' + id('student-checkbox-section') + ' .student-checkbox:checked').length;
        },

        // ── Tier helpers ───────────────────────────────────────────────────
        getParentTier(i) {
            return this.parentTiers[i] ?? '';
        },

        getTeacherTier(i) {
            return this.teacherTiers[i] ?? '';
        },

        setParentTier(i, val) {
            this.parentTiers[i] = val ? parseFloat(val) : null;
        },

        setTeacherTier(i, val) {
            this.teacherTiers[i] = val ? parseFloat(val) : null;
        },

        // ── Type / program change ──────────────────────────────────────────
        onTypeChange() {
            this.isKelasMode = this.checkKelasMode();
            this.filterPrograms();
            this.updateFieldVisibility();
            this.applyDefaults();
        },

        onProgramChange() {
            this.isKelasMode = this.checkKelasMode();
            this.applyDefaults();
            this.updateFieldVisibility();
        },

        onStudentChange() {
            if (!this.isKelasMode) {
                this.updatePricingVisibility();
            }
        },

        filterPrograms() {
            const select = document.getElementById(id('program-select'));
            if (!select) return;
            const selectedType = (document.getElementById(id('type-select')) || { value: 'privat' }).value;
            let hasSelected = false;
            Array.from(select.options).forEach(option => {
                if (!option.value) { option.style.display = ''; return; }
                const show = option.dataset.type === selectedType;
                option.style.display = show ? '' : 'none';
                if (show && option.selected) hasSelected = true;
            });
            if (!hasSelected) select.value = '';
        },

        applyDefaults() {
            const select = document.getElementById(id('program-select'));
            const parentInput = document.getElementById(id('parent-rate'));
            const teacherInput = document.getElementById(id('teacher-rate'));
            if (!select || !parentInput) return;
            const option = Array.from(select.options).find(o => o.value === select.value);
            if (!option) return;
            const defaultParent = option.dataset.defaultParent ?? '';
            const defaultTeacher = option.dataset.defaultTeacher ?? '';
            if (!parentInput.dataset.touched && !parentInput.value) {
                parentInput.value = defaultParent;
            }
            if (!teacherInput?.dataset.touched && !teacherInput?.value) {
                if (teacherInput) teacherInput.value = defaultTeacher;
            }
        },

        markTouched(e) {
            e.target.dataset.touched = 'true';
        },

        // ── Field visibility ───────────────────────────────────────────────
        updateFieldVisibility() {
            const typeSelect = document.getElementById(id('type-select'));
            const teacherSelect = document.getElementById(id('teacher-select'));
            const teacherField = document.getElementById(id('teacher-field'));
            const pricingTiers = document.getElementById(id('pricing-tiers-section'));
            const rateFields = document.getElementById(id('rate-fields'));
            const teacherRateField = document.getElementById(id('teacher-rate-field'));
            const sessionsPrivat = document.getElementById(id('sessions-privat'));
            const sessionsKelas = document.getElementById(id('sessions-kelas'));
            const studentCheckboxSection = document.getElementById(id('student-checkbox-section'));
            const studentDropdownSection = document.getElementById(id('student-dropdown-section'));
            const parentRateLabel = document.getElementById(id('parent-rate-label'));
            const parentRateInput = document.getElementById(id('parent-rate'));
            const teacherRateInput = document.getElementById(id('teacher-rate'));

            if (!typeSelect) return;
            const kelasMode = this.isKelasMode;

            // Teacher field
            if (kelasMode) {
                teacherField?.classList.add('hidden');
                teacherSelect.required = false;
                teacherSelect.value = '';
            } else {
                teacherField?.classList.remove('hidden');
                teacherSelect.required = typeSelect.value === 'privat';
            }

            // Agreed sessions
            if (kelasMode) {
                sessionsPrivat?.classList.add('hidden');
                sessionsKelas?.classList.remove('hidden');
                const sel1 = document.getElementById(id('agreed-sessions-select'));
                const sel2 = document.getElementById(id('agreed-sessions-select-kelas'));
                if (sel1) { sel1.disabled = true; sel1.required = false; }
                if (sel2) { sel2.disabled = false; sel2.required = true; }
            } else {
                sessionsPrivat?.classList.remove('hidden');
                sessionsKelas?.classList.add('hidden');
                const sel1 = document.getElementById(id('agreed-sessions-select'));
                const sel2 = document.getElementById(id('agreed-sessions-select-kelas'));
                if (sel1) { sel1.disabled = false; sel1.required = true; }
                if (sel2) { sel2.disabled = true; sel2.required = false; }
            }

            // Student selection
            if (kelasMode) {
                studentCheckboxSection?.classList.add('hidden');
                studentDropdownSection?.classList.remove('hidden');
                studentCheckboxSection?.querySelectorAll('.student-checkbox').forEach(cb => cb.disabled = true);
                const dropdown = document.getElementById(id('student-dropdown'));
                if (dropdown) dropdown.disabled = false;
                pricingTiers?.querySelectorAll('input[name^="pricing_tiers"]').forEach(el => el.disabled = true);
            } else {
                studentCheckboxSection?.classList.remove('hidden');
                studentDropdownSection?.classList.add('hidden');
                studentCheckboxSection?.querySelectorAll('.student-checkbox').forEach(cb => cb.disabled = false);
                const dropdown = document.getElementById(id('student-dropdown'));
                if (dropdown) dropdown.disabled = true;
                pricingTiers?.querySelectorAll('input[name^="pricing_tiers"]').forEach(el => el.disabled = false);
            }

            this.updatePricingVisibility();
        },

        updatePricingVisibility() {
            const pricingTiers = document.getElementById(id('pricing-tiers-section'));
            const rateFields = document.getElementById(id('rate-fields'));
            const teacherRateField = document.getElementById(id('teacher-rate-field'));
            const parentRateLabel = document.getElementById(id('parent-rate-label'));
            const parentRateInput = document.getElementById(id('parent-rate'));
            const teacherRateInput = document.getElementById(id('teacher-rate'));

            if (!pricingTiers) return;

            if (this.isKelasMode) {
                pricingTiers.classList.add('hidden');
                pricingTiers.querySelectorAll('input[name^="pricing_tiers"]').forEach(el => el.disabled = true);
                teacherRateField?.classList.add('hidden');
                if (teacherRateInput) teacherRateInput.required = false;
                rateFields?.classList.remove('hidden');
                if (parentRateLabel) parentRateLabel.textContent = 'Harga Paket Sebulan';
                if (parentRateInput) parentRateInput.required = true;
                return;
            }

            const checkedCount = this.getCheckedStudentCount();
            if (checkedCount <= 1) {
                pricingTiers.classList.add('hidden');
                pricingTiers.querySelectorAll('input[name^="pricing_tiers"]').forEach(el => el.disabled = true);
                rateFields?.classList.remove('hidden');
                teacherRateField?.classList.remove('hidden');
                if (teacherRateInput) teacherRateInput.required = true;
                if (parentRateLabel) parentRateLabel.textContent = 'Harga Ortu Default (1 murid)';
                if (parentRateInput) parentRateInput.required = true;
            } else {
                pricingTiers.classList.remove('hidden');
                pricingTiers.querySelectorAll('input[name^="pricing_tiers"]').forEach(el => el.disabled = false);
                rateFields?.classList.add('hidden');
                teacherRateField?.classList.add('hidden');
                if (teacherRateInput) teacherRateInput.required = false;
                if (parentRateLabel) parentRateLabel.textContent = 'Harga Ortu Default (1 murid)';
                if (parentRateInput) parentRateInput.required = true;
            }
        },

        // ── Student search ─────────────────────────────────────────────────
        onStudentSearch(e) {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('#' + id('student-checkbox-section') + ' .student-label').forEach(label => {
                const name = label.querySelector('span')?.textContent?.toLowerCase() || '';
                label.style.display = name.includes(query) ? '' : 'none';
            });
        },

        // ── Form submission (standalone pages only) ─────────────────────────
        // When used inside crudModal, the modal intercepts submit via
        // document.addEventListener and calls crudModal.submit() instead.
        // This method serves as fallback for non-modal usage.
        async submit(e) {
            if (e) e.preventDefault();
            const form = e?.target ?? document.getElementById('enrollment-form');
            if (!form) return;

            this.errors = {};
            const params = new URLSearchParams();
            for (const el of form.querySelectorAll('input[name], select[name], textarea[name]')) {
                if (el.disabled || el.offsetParent === null) continue;
                if (el.type === 'checkbox') {
                    if (el.checked) params.append(el.name, el.value || 'on');
                } else if (el.type === 'radio') {
                    if (el.checked) params.append(el.name, el.value);
                } else if (el.tagName === 'SELECT' && el.multiple) {
                    for (const opt of el.selectedOptions) params.append(el.name, opt.value);
                } else {
                    params.append(el.name, el.value);
                }
            }

            const method = this.enrollmentId ? 'put' : 'post';
            this.submitting = true;

            try {
                const resp = await window.Ajax[method](this.formUrl, params);
                window.Toast?.success(resp.data?.message || 'Berhasil disimpan.');
                if (resp.data?.redirect) window.location.href = resp.data.redirect;
            } catch (e) {
                if (e.response?.status === 422) {
                    this.errors = e.response.data.errors || {};
                    this.showErrors();
                    window.Toast?.error('Mohon perbaiki kesalahan pada form.');
                } else {
                    window.Toast?.error('Gagal menyimpan enrollment.');
                }
            } finally {
                this.submitting = false;
            }
        },

        showErrors() {
            const form = document.getElementById('enrollment-form') || document.getElementById('crud-form');
            if (!form) return;

            const topError = form.querySelector('#form-error-alert');
            const hasErrors = Object.keys(this.errors).length > 0;
            if (topError) {
                topError.style.display = hasErrors ? 'block' : 'none';
                const ul = topError.querySelector('ul');
                if (ul) ul.innerHTML = Object.values(this.errors).flat().map(m => `<li>${m}</li>`).join('');
            }

            form.querySelectorAll('[class*="crud-error-"]').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });
            form.querySelectorAll('[class*="crud-field-"]').forEach(el => {
                el.classList.remove('border-rose-400', 'ring-1', 'ring-rose-300');
                el.classList.add('border-gray-300');
            });

            Object.entries(this.errors).forEach(([field, messages]) => {
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
        },
    };
}
