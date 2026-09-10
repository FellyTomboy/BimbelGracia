/**
 * Enrollment Form Alpine Component
 * Used by both create.blade.php and edit.blade.php
 *
 * Handles:
 * - Type/program/student mode toggling (kelas vs privat)
 * - Pricing tier dynamic rows
 * - AJAX form submission
 * - Inline validation error display
 * - Success redirect
 */

export function EnrollmentForm(config = {}) {
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

        // ── Pricing tiers (lifted from nested x-data) ────────────────────────
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
            const select = document.getElementById('type-select');
            const programSelect = document.getElementById('program-select');
            if (!select || !programSelect) return false;
            const programOption = Array.from(programSelect.options).find(o => o.value === programSelect.value);
            return select.value === 'kelas' && programOption?.dataset?.type === 'kelas';
        },

        getCheckedStudentCount() {
            return document.querySelectorAll('#student-checkbox-section .student-checkbox:checked').length;
        },

        // ── Tier helpers (called from Alpine template) ──────────────────────
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
            const select = document.getElementById('program-select');
            if (!select) return;
            const selectedType = document.getElementById('type-select')?.value || 'privat';
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
            const select = document.getElementById('program-select');
            const parentInput = document.getElementById('parent-rate');
            const teacherInput = document.getElementById('teacher-rate');
            if (!select || !parentInput) return;
            const option = Array.from(select.options).find(o => o.value === select.value);
            if (!option) return;
            const defaultParent = option.dataset.defaultParent ?? '';
            const defaultTeacher = option.dataset.defaultTeacher ?? '';
            if (!parentInput.dataset.touched && !parentInput.value) {
                parentInput.value = defaultParent;
            }
            if (!teacherInput.dataset.touched && !teacherInput.value) {
                teacherInput.value = defaultTeacher;
            }
        },

        markTouched(e) {
            e.target.dataset.touched = 'true';
        },

        // ── Field visibility ───────────────────────────────────────────────
        updateFieldVisibility() {
            const typeSelect = document.getElementById('type-select');
            const teacherSelect = document.getElementById('teacher-select');
            const teacherField = document.getElementById('teacher-field');
            const pricingTiers = document.getElementById('pricing-tiers-section');
            const rateFields = document.getElementById('rate-fields');
            const teacherRateField = document.getElementById('teacher-rate-field');
            const sessionsPrivat = document.getElementById('sessions-privat');
            const sessionsKelas = document.getElementById('sessions-kelas');
            const studentCheckboxSection = document.getElementById('student-checkbox-section');
            const studentDropdownSection = document.getElementById('student-dropdown-section');
            const parentRateLabel = document.getElementById('parent-rate-label');
            const parentRateInput = document.getElementById('parent-rate');
            const teacherRateInput = document.getElementById('teacher-rate');

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
                document.getElementById('agreed-sessions-select').disabled = true;
                document.getElementById('agreed-sessions-select').required = false;
                document.getElementById('agreed-sessions-select-kelas').disabled = false;
                document.getElementById('agreed-sessions-select-kelas').required = true;
            } else {
                sessionsPrivat?.classList.remove('hidden');
                sessionsKelas?.classList.add('hidden');
                document.getElementById('agreed-sessions-select').disabled = false;
                document.getElementById('agreed-sessions-select').required = true;
                document.getElementById('agreed-sessions-select-kelas').disabled = true;
                document.getElementById('agreed-sessions-select-kelas').required = false;
            }

            // Student selection
            if (kelasMode) {
                studentCheckboxSection?.classList.add('hidden');
                studentDropdownSection?.classList.remove('hidden');
                studentCheckboxSection?.querySelectorAll('.student-checkbox').forEach(cb => cb.disabled = true);
                document.getElementById('student-dropdown').disabled = false;
                pricingTiers?.querySelectorAll('input[name^="pricing_tiers"]').forEach(el => el.disabled = true);
            } else {
                studentCheckboxSection?.classList.remove('hidden');
                studentDropdownSection?.classList.add('hidden');
                studentCheckboxSection?.querySelectorAll('.student-checkbox').forEach(cb => cb.disabled = false);
                document.getElementById('student-dropdown').disabled = true;
                pricingTiers?.querySelectorAll('input[name^="pricing_tiers"]').forEach(el => el.disabled = false);
            }

            this.updatePricingVisibility();
        },

        updatePricingVisibility() {
            const pricingTiers = document.getElementById('pricing-tiers-section');
            const rateFields = document.getElementById('rate-fields');
            const teacherRateField = document.getElementById('teacher-rate-field');
            const parentRateLabel = document.getElementById('parent-rate-label');
            const parentRateInput = document.getElementById('parent-rate');
            const teacherRateInput = document.getElementById('teacher-rate');

            if (!pricingTiers) return;

            if (this.isKelasMode) {
                pricingTiers.classList.add('hidden');
                pricingTiers.querySelectorAll('input[name^="pricing_tiers"]').forEach(el => el.disabled = true);
                teacherRateField?.classList.add('hidden');
                teacherRateInput.required = false;
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
                teacherRateInput.required = true;
                if (parentRateLabel) parentRateLabel.textContent = 'Harga Ortu Default (1 murid)';
                if (parentRateInput) parentRateInput.required = true;
            } else {
                pricingTiers.classList.remove('hidden');
                pricingTiers.querySelectorAll('input[name^="pricing_tiers"]').forEach(el => el.disabled = false);
                rateFields?.classList.add('hidden');
                teacherRateField?.classList.add('hidden');
                teacherRateInput.required = false;
                if (parentRateLabel) parentRateLabel.textContent = 'Harga Ortu Default (1 murid)';
                if (parentRateInput) parentRateInput.required = true;
            }
        },

        // ── Student search ─────────────────────────────────────────────────
        onStudentSearch(e) {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('#student-checkbox-section .student-label').forEach(label => {
                const name = label.querySelector('span')?.textContent?.toLowerCase() || '';
                label.style.display = name.includes(query) ? '' : 'none';
            });
        },

        // ── Form submission ─────────────────────────────────────────────────
        async submit(e) {
            e.preventDefault();
            this.errors = {};

            const form = e.target;
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

            // Pricing tiers: collect from reactive state, not DOM inputs directly
            // to avoid stale disabled-field issues
            for (let i = 1; i <= this.tierCount; i++) {
                if (this.parentTiers[i] != null) {
                    params.set(`pricing_tiers_parent[${i}]`, this.parentTiers[i]);
                }
                if (this.teacherTiers[i] != null) {
                    params.set(`pricing_tiers_teacher[${i}]`, this.teacherTiers[i]);
                }
            }

            const method = this.enrollmentId ? 'put' : 'post';

            this.submitting = true;

            try {
                const resp = await window.Ajax[method](this.formUrl, params);
                window.Toast?.success(resp.data?.message || 'Berhasil disimpan.');
                window.location.href = resp.data?.redirect || '{{ route('admin.enrollments.index') }}';
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
            const form = document.getElementById('enrollment-form');
            if (!form) return;

            // Show/hide top-level error banner
            const topError = form.querySelector('#form-error-alert');
            const hasErrors = Object.keys(this.errors).length > 0;
            if (topError) {
                topError.style.display = hasErrors ? 'block' : 'none';
                const ul = topError.querySelector('ul');
                if (ul) {
                    ul.innerHTML = Object.values(this.errors).flat().map(m => `<li>${m}</li>`).join('');
                }
            }

            // Reset all error indicators
            form.querySelectorAll('[class*="crud-error-"]').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });
            form.querySelectorAll('[class*="crud-field-"]').forEach(el => {
                el.classList.remove('border-rose-400', 'ring-1', 'ring-rose-300');
                el.classList.add('border-gray-300');
            });

            // Apply per-field errors
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
