/**
 * Alpine.js CRUD Modal Component Factory
 *
 * Usage in Blade:
 *   <div x-data="crudModal({
 *     createUrl: '/admin/programs/form',
 *     storeUrl: '/admin/programs',
 *     editUrl: (id) => `/admin/programs/${id}/form`,
 *     updateUrl: (id) => `/admin/programs/${id}`,
 *     deleteUrl: (id) => `/admin/programs/${id}`,
 *     listSelector: 'table',
 *   })">
 *
 * The modal HTML is rendered directly in the Blade template using Alpine x-show
 * on modalOpen. No external modal component needed.
 *
 * Methods exposed:
 *   openCreate()  — fetch create form, populate modal, open modal
 *   openEdit(id)  — fetch edit form, populate modal, open modal
 *   submit()      — submit form via Ajax.post/put
 *   confirmDelete(id) — show delete confirmation overlay
 *   deleteRow(id) — perform delete via Ajax.delete
 *   close()       — close modal, reset state
 *   refreshTable() — re-fetch table body from index page
 */

export function crudModal(config) {
    return {
        // Modal visibility
        modalOpen: false,

        // Modal content
        modalTitle: '',
        modalBody: '',
        loading: false,
        submitting: false,
        errors: {},
        isEdit: false,
        currentId: null,

        // Delete confirmation state
        deleteConfirmId: null,
        deleteLoading: false,

        // Config
        createUrl: config.createUrl || '',
        storeUrl: config.storeUrl || '',
        editUrl: config.editUrl || ((id) => ''),
        updateUrl: config.updateUrl || ((id) => ''),
        deleteUrl: config.deleteUrl || ((id) => ''),
        deleteMethod: config.deleteMethod || 'delete',
        listSelector: config.listSelector || 'table',

        init() {
            // Delegate submit: intercept form submissions from within x-html content.
            // Uses a lightweight approach: find the Alpine component from the DOM,
            // then call its submit() method.
            document.addEventListener('submit', (e) => {
                if (!e.target || e.target.id !== 'crud-form') return;
                e.preventDefault();
                // Find the closest Alpine component that has our crudModal methods
                const el = document.querySelector('[x-data^="crudModal"]');
                if (el && window.Alpine && window.Alpine.$data) {
                    const data = Alpine.$data(el);
                    if (data && typeof data.submit === 'function') {
                        data.submit();
                    }
                }
            }, true);
        },

        // Inject lookup data from JSON response into window globals (called before setting modalBody).
        // Data keys like teachersByProgram, studentsByProgram, sessionTeachers, existingStudentIds
        // are written to window.__css_<key>__ so the form partial can read them.
        _injectResponseData(data) {
            if (!data || typeof data !== 'object') return;
            // Find keys that look like lookup data (not 'html', 'title', etc.)
            const skipKeys = new Set(['html', 'title', 'success', 'message']);
            Object.keys(data).forEach(key => {
                if (skipKeys.has(key)) return;
                window['__css_' + key + '__'] = data[key];
            });
        },

        // Returns HTML without <script> tags (pure transformation, no side effects).
        // Use this when assigning to modalBody so x-html injects a clean DOM.
        _stripScripts(html) {
            return html.replace(/<script(\s[^>]*)?>[\s\S]*?<\/script>/gi, '');
        },

        // Extracts and evaluates all <script> tags from HTML.
        // Must be called AFTER the HTML is in the DOM (e.g. inside $nextTick),
        // because inline scripts may query DOM elements via getElementById.
        _runScripts(html) {
            const fullRegex = /<script(\s[^>]*)?>([\s\S]*?)<\/script>/gi;
            let match;
            while ((match = fullRegex.exec(html)) !== null) {
                const inner = (match[2] || '').trim();
                if (inner) {
                    try {
                        // eslint-disable-next-line no-eval
                        (0, eval)(inner);
                    } catch (e) {
                        console.error('[crudModal] script eval error:', e, '\nCode:', inner);
                    }
                }
            }
        },

        // ── Open create form ──────────────────────────────────────────────
        async openCreate() {
            this.isEdit = false;
            this.currentId = null;
            this.errors = {};
            this.loading = true;
            this.modalTitle = 'Memuat...';
            this.modalBody = `
                <div class="flex justify-center py-8">
                    <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            `;
            this.modalOpen = true;

            try {
                const response = await window.Ajax.get(this.createUrl);
                const data = response.data;
                // Set lookup data globals BEFORE setting modalBody so form can read them
                this._injectResponseData(data);
                this.modalTitle = data.title || 'Tambah Data';
                // Strip scripts from HTML, inject into DOM, then run scripts and init Alpine
                // (deferred to $nextTick so inline scripts can query the freshly-mounted DOM)
                const rawHtml = data.html || data;
                this.modalBody = this._stripScripts(rawHtml);
                this.$nextTick(() => {
                    this._runScripts(rawHtml);
                    const form = document.getElementById('crud-form');
                    if (form) window.Alpine.initTree(form);
                });
            } catch (e) {
                this.modalBody = `
                    <div class="text-center py-8">
                        <p class="text-rose-500 font-medium">Gagal memuat form.</p>
                        <button @click="close()" class="mt-4 text-sm text-indigo-600 hover:underline">Tutup</button>
                    </div>
                `;
            } finally {
                this.loading = false;
            }
        },

        // ── Open edit form ─────────────────────────────────────────────────
        async openEdit(id) {
            this.isEdit = true;
            this.currentId = id;
            this.errors = {};
            this.loading = true;
            this.modalTitle = 'Memuat...';
            this.modalBody = `
                <div class="flex justify-center py-8">
                    <svg class="animate-spin w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            `;
            this.modalOpen = true;

            try {
                const url = typeof this.editUrl === 'function' ? this.editUrl(id) : `${this.editUrl}/${id}`;
                const response = await window.Ajax.get(url);
                const data = response.data;
                // Set lookup data globals BEFORE setting modalBody so form can read them
                this._injectResponseData(data);
                this.modalTitle = data.title || 'Edit Data';
                // Strip scripts from HTML, inject into DOM, then run scripts and init Alpine
                // (deferred to $nextTick so inline scripts can query the freshly-mounted DOM)
                const rawHtml = data.html || data;
                this.modalBody = this._stripScripts(rawHtml);
                this.$nextTick(() => {
                    this._runScripts(rawHtml);
                    const form = document.getElementById('crud-form');
                    if (form) window.Alpine.initTree(form);
                });
            } catch (e) {
                this.modalBody = `
                    <div class="text-center py-8">
                        <p class="text-rose-500 font-medium">Gagal memuat form.</p>
                        <button @click="close()" class="mt-4 text-sm text-indigo-600 hover:underline">Tutup</button>
                    </div>
                `;
            } finally {
                this.loading = false;
            }
        },

        // ── Submit form (also called by button @click) ──────────────────
        async submit() {
            const form = document.getElementById('crud-form');
            if (!form) {
                console.error('[crudModal] #crud-form not found');
                return;
            }

            this.submitting = true;
            this.errors = {};

            // Clear previous error display (vanilla JS — no Alpine bindings inside x-html)
            form.querySelectorAll('[class^="crud-error-"]').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });
            form.querySelectorAll('[class^="crud-field-"]').forEach(el => {
                el.classList.remove('border-rose-400', 'ring-1', 'ring-rose-300');
                el.classList.add('border-gray-300');
            });

            const params = new URLSearchParams();
            for (const el of form.querySelectorAll('input[name], select[name], textarea[name]')) {
                if (el.type === 'checkbox') {
                    if (el.checked) params.append(el.name, el.value || 'on');
                } else if (el.type === 'radio') {
                    if (el.checked) params.append(el.name, el.value);
                } else if (el.tagName === 'SELECT' && el.multiple) {
                    for (const opt of el.selectedOptions) params.append(el.name, opt.value);
                } else if (el.value.trim()) {
                    params.append(el.name, el.value);
                }
            }

            const url = this.isEdit
                ? (typeof this.updateUrl === 'function' ? this.updateUrl(this.currentId) : `${this.updateUrl}/${this.currentId}`)
                : this.storeUrl;
            const method = this.isEdit ? 'put' : 'post';

            try {
                const response = await window.Ajax[method](url, params);
                window.Toast?.success(this.isEdit ? 'Berhasil diperbarui.' : 'Berhasil disimpan.');
            } catch (e) {
                console.error('[crudModal] submit error', e.response?.status, e.response?.data);
                if (e.response?.status === 422) {
                    this.errors = e.response.data.errors || {};
                    // Show errors via vanilla JS (Alpine $parent bindings don't work inside x-html)
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
                }
            } finally {
                this.submitting = false;
            }
            // Always close modal after attempt (success or validation error shown)
            this.close();
            // Refresh table without await so reload failures don't block modal close
            this.refreshTable().catch(() => {});
        },

        // ── Delete ─────────────────────────────────────────────────────────
        confirmDelete(id) {
            this.deleteConfirmId = id;
        },

        cancelDelete() {
            this.deleteConfirmId = null;
        },

        async deleteRow(id) {
            this.deleteLoading = true;
            try {
                const url = typeof this.deleteUrl === 'function' ? this.deleteUrl(id) : `${this.deleteUrl}/${id}`;
                const method = this.deleteMethod || 'delete';
                await window.Ajax[method](url);
                window.Toast?.success('Berhasil dihapus.');
                this.deleteConfirmId = null;
                await this.refreshTable();
            } catch (e) {
                // Error toast handled by Ajax utility
            } finally {
                this.deleteLoading = false;
            }
        },

        // ── Close modal ────────────────────────────────────────────────────
        close() {
            this.modalOpen = false;
            this.modalBody = '';
            this.modalTitle = '';
            this.errors = {};
            this.submitting = false;
            this.isEdit = false;
            this.currentId = null;
        },

        // ── Refresh table ──────────────────────────────────────────────────
        async refreshTable() {
            const table = document.querySelector(this.listSelector);
            if (!table) return;

            try {
                const response = await window.Ajax.get(window.location.href);
                const parser = new DOMParser();
                const doc = parser.parseFromString(response.data, 'text/html');

                const newTable = doc.querySelector(this.listSelector);
                if (newTable) {
                    table.innerHTML = newTable.innerHTML;
                }

                // Also refresh pagination if present
                const pagination = document.querySelector('.paging-links');
                const newPagination = doc.querySelector('.paging-links');
                if (pagination && newPagination) {
                    pagination.innerHTML = newPagination.innerHTML;
                }
            } catch (e) {
                // Silent fail — do NOT reload page, modal is already closed
            }
        },

        // ── Student modal (for parent/student management) ──────────────────
        studentModalOpen: false,
        studentModalParent: '',
        studentModalParentId: null,
        studentLoading: false,
        studentList: [],
        studentEditId: null,
        studentEditForm: { nickname: '', full_name: '', sekolah: '', kelas: '' },
        studentEditErrors: {},
        studentEditSubmitting: false,
        addStudentForm: { nickname: '', full_name: '', sekolah: '', kelas: '' },
        addStudentErrors: {},
        addStudentSubmitting: false,

        async openStudentModal(parentId, parentName) {
            this.studentModalParentId = parentId;
            this.studentModalParent = parentName;
            this.studentModalOpen = true;
            this.studentLoading = true;
            this.studentList = [];
            this.studentEditId = null;
            this.addStudentErrors = {};
            this.addStudentForm = { nickname: '', full_name: '', sekolah: '', kelas: '' };
            try {
                const resp = await window.Ajax.get(`/admin/parents/${parentId}/students-json`);
                this.studentList = resp.data?.students || [];
            } catch {
                window.Toast?.error('Gagal memuat daftar murid.');
            } finally {
                this.studentLoading = false;
            }
        },

        showStudentEdit(student) {
            this.studentEditId = student.id;
            this.studentEditForm = {
                nickname: student.nickname || '',
                full_name: student.full_name || '',
                sekolah: student.sekolah || '',
                kelas: student.kelas || '',
            };
            this.studentEditErrors = {};
        },

        async submitStudentEdit() {
            this.studentEditSubmitting = true;
            this.studentEditErrors = {};
            const params = new URLSearchParams();
            Object.entries(this.studentEditForm).forEach(([k, v]) => {
                if (v) params.append(k, v);
            });
            try {
                await window.Ajax.put(`/admin/parents/${this.studentModalParentId}/students/${this.studentEditId}`, params);
                window.Toast?.success('Data murid berhasil diperbarui.');
                this.studentEditId = null;
                await this.reloadStudentList();
            } catch (e) {
                if (e.response?.status === 422) {
                    this.studentEditErrors = e.response.data.errors || {};
                } else {
                    window.Toast?.error('Gagal memperbarui data murid.');
                }
            } finally {
                this.studentEditSubmitting = false;
            }
        },

        async removeStudentRow(studentId, displayName) {
            if (!confirm(`Hibernasi murid ${displayName}?`)) return;
            try {
                await window.Ajax.delete(`/admin/parents/${this.studentModalParentId}/students/${studentId}`);
                window.Toast?.success('Murid berhasil dihibernasi.');
                this.studentList = this.studentList.filter(s => s.id != studentId);
            } catch {
                window.Toast?.error('Gagal menghapus murid.');
            }
        },

        async submitAddStudent() {
            this.addStudentSubmitting = true;
            this.addStudentErrors = {};
            const params = new URLSearchParams();
            Object.entries(this.addStudentForm).forEach(([k, v]) => {
                if (v) params.append(k, v);
            });
            try {
                await window.Ajax.post(`/admin/parents/${this.studentModalParentId}/add-student`, params);
                window.Toast?.success('Murid berhasil ditambahkan.');
                this.addStudentForm = { nickname: '', full_name: '', sekolah: '', kelas: '' };
                await this.reloadStudentList();
            } catch (e) {
                if (e.response?.status === 422) {
                    this.addStudentErrors = e.response.data.errors || {};
                } else {
                    window.Toast?.error('Gagal menambahkan murid.');
                }
            } finally {
                this.addStudentSubmitting = false;
            }
        },

        async reloadStudentList() {
            try {
                const resp = await window.Ajax.get(`/admin/parents/${this.studentModalParentId}/students-json`);
                this.studentList = resp.data?.students || [];
            } catch { /* ignore */ }
        },

        // ── Password modal ────────────────────────────────────────────────
        passwordModalOpen: false,
        passwordModalParent: '',
        passwordModalParentId: null,
        passwordForm: { password: '', password_confirmation: '' },
        passwordErrors: {},
        passwordSubmitting: false,

        openPasswordModal(parentId, parentName) {
            this.passwordModalParentId = parentId;
            this.passwordModalParent = parentName;
            this.passwordForm = { password: '', password_confirmation: '' };
            this.passwordErrors = {};
            this.passwordSubmitting = false;
            this.passwordModalOpen = true;
        },

        async submitPassword() {
            if (this.passwordForm.password !== this.passwordForm.password_confirmation) {
                this.passwordErrors = { password: ['Konfirmasi password tidak cocok.'] };
                return;
            }
            this.passwordSubmitting = true;
            this.passwordErrors = {};
            const params = new URLSearchParams();
            Object.entries(this.passwordForm).forEach(([k, v]) => {
                if (v) params.append(k, v);
            });
            try {
                await window.Ajax.post(`/admin/parents/${this.passwordModalParentId}/change-password`, params);
                window.Toast?.success('Password berhasil diubah.');
                this.passwordModalOpen = false;
                this.passwordForm = { password: '', password_confirmation: '' };
            } catch (e) {
                if (e.response?.status === 422) {
                    this.passwordErrors = e.response.data.errors || {};
                } else {
                    window.Toast?.error('Gagal mengubah password.');
                }
            } finally {
                this.passwordSubmitting = false;
            }
        },
    };
}
