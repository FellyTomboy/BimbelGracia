<form id="crud-form" @submit.prevent="$parent.submit()">

    @csrf
    @if($session) @method('PUT') @endif

    {{-- Program & Tanggal --}}
    <div class="grid md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Program <span class="text-rose-500">*</span>
            </label>
            <select name="program_id"
                    id="program-select"
                    class="crud-field-program_id w-full rounded-xl border border-gray-200 text-sm" required>
                <option value="">Pilih program kelas</option>
                @foreach($programs as $p)
                    <option value="{{ $p->id }}" @selected(($session?->program_id == $p->id) || ($selectedProgramId == $p->id) || (old('program_id') == $p->id))>{{ $p->name }}</option>
                @endforeach
            </select>
            <p class="crud-error-program_id text-sm text-rose-500 mt-1" style="display:none"></p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Tanggal Sesi <span class="text-rose-500">*</span>
            </label>
            <input type="date"
                   id="session-date-input"
                   name="session_date"
                   value="{{ $session?->session_date?->format('Y-m-d') ?? ($sessionDate ?? old('session_date')) }}"
                   max="{{ date('Y-m-d') }}"
                   class="crud-field-session_date w-full rounded-xl border border-gray-200 text-sm" required />
            <p class="crud-error-session_date text-sm text-rose-500 mt-1" style="display:none"></p>
        </div>
    </div>

    {{-- Guru Hadir --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Guru yang Hadir</label>
        <div class="relative">
            <input type="text"
                   id="teacher-search-input"
                   placeholder="Ketik nama guru untuk mencari..."
                   class="w-full rounded-xl border border-gray-200 text-sm" />
            <div id="teacher-dropdown"
                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-56 overflow-y-auto hidden">
            </div>
        </div>
        <div id="teacher-chips" class="flex flex-wrap gap-2 mt-2"></div>
        <div id="teacher-hidden-container"></div>
        <p class="crud-error-teacher_ids text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Murid Hadir --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium text-gray-700">
                Murid yang Hadir <span class="text-rose-500">*</span>
            </label>
            <button type="button"
                    id="select-all-students-btn"
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium hidden">
                Pilih Semua
            </button>
        </div>
        <div class="border rounded-xl p-4 bg-gray-50 max-h-72 overflow-y-auto">
            <div id="students-empty-no-program" class="text-center text-sm text-gray-400 py-4">Pilih program kelas terlebih dahulu.</div>
            <div id="students-empty-no-students" class="text-center text-sm text-gray-400 py-4 hidden">Tidak ada murid aktif di enrollment program ini.</div>
            <div id="students-list" class="grid grid-cols-1 sm:grid-cols-2 gap-2"></div>
        </div>
        <div id="student-hidden-container"></div>
        <p class="crud-error-student_enrollment_map text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Notes --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
        <textarea name="notes"
                  id="notes-textarea"
                  rows="2"
                  class="w-full rounded-xl border border-gray-200 text-sm">{{ $session?->notes ?? old('notes') }}</textarea>
    </div>

    {{-- Init: wire up vanilla JS handlers after DOM is ready --}}
    <script>
    (function() {
        var form = document.getElementById('crud-form');
        if (!form) {
            console.error('[class-session-form] #crud-form not found');
            return;
        }

        var teachersByProgram = window.__css_teachersByProgram__ || {};
        var studentsByProgram = window.__css_studentsByProgram__ || {};
        var selectedTeacherIds = window.__css_session_teachers__
            ? window.__css_session_teachers__.map(function(t) { return t.id; })
            : [];
        var selectedStudentIds = window.__css_session_student_ids__ || [];

        var teacherSearchInput = document.getElementById('teacher-search-input');
        var teacherDropdown = document.getElementById('teacher-dropdown');
        var teacherChips = document.getElementById('teacher-chips');
        var teacherContainer = document.getElementById('teacher-hidden-container');
        var programSelect = document.getElementById('program-select');
        var studentsEmptyNoProgram = document.getElementById('students-empty-no-program');
        var studentsEmptyNoStudents = document.getElementById('students-empty-no-students');
        var studentsList = document.getElementById('students-list');
        var studentContainer = document.getElementById('student-hidden-container');
        var selectAllBtn = document.getElementById('select-all-students-btn');

        // Pre-select program if passed
        var preselectedProgramId = null;
        var opts = programSelect.options;
        for (var i = 0; i < opts.length; i++) {
            if (opts[i].selected) { preselectedProgramId = opts[i].value; break; }
        }

        function getCurrentTeachers() {
            var pid = parseInt(programSelect.value, 10);
            return teachersByProgram[pid] || [];
        }

        function getCurrentStudents() {
            var pid = parseInt(programSelect.value, 10);
            return studentsByProgram[pid] || [];
        }

        function syncTeacherHidden() {
            teacherContainer.innerHTML = '';
            selectedTeacherIds.forEach(function(id) {
                var i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'teacher_ids[]';
                i.value = id;
                teacherContainer.appendChild(i);
            });
        }

        function syncStudentHidden() {
            studentContainer.innerHTML = '';
            selectedStudentIds.forEach(function(id) {
                var i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'student_enrollment_map[]';
                i.value = id;
                studentContainer.appendChild(i);
            });
        }

        function renderTeacherChips() {
            var teachers = getCurrentTeachers();
            var chips = '';
            selectedTeacherIds.forEach(function(id) {
                var t = teachers.find(function(x) { return x.id === id; });
                var name = t ? t.name : id;
                chips += '<span class="inline-flex items-center gap-1 pl-3 pr-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">'
                    + escHtml(name)
                    + '<button type="button" class="hover:text-rose-600" onclick="window.__cssRemoveTeacher(' + id + ')">'
                    + '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
                    + '</button></span>';
            });
            teacherChips.innerHTML = chips;
            syncTeacherHidden();
        }

        function buildTeacherRow(t) {
            var checked = selectedTeacherIds.indexOf(t.id) !== -1 ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700';
            var checkmark = selectedTeacherIds.indexOf(t.id) !== -1
                ? '<svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                : '';
            return '<button type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 flex items-center justify-between transition-colors ' + checked + '" onclick="window.__cssAddTeacher(' + t.id + ')">'
                + '<span>' + escHtml(t.name) + '</span>' + checkmark + '</button>';
        }

        function renderTeacherDropdown(q) {
            var teachers = getCurrentTeachers();
            if (teachers.length === 0) {
                teacherDropdown.innerHTML = '<div class="px-4 py-2.5 text-sm text-gray-400">Tidak ada guru di program ini.</div>';
                teacherDropdown.classList.remove('hidden');
                return;
            }
            var filtered = q
                ? teachers.filter(function(t) { return t.name.toLowerCase().indexOf(q.toLowerCase()) !== -1; })
                : teachers;
            if (filtered.length === 0) {
                teacherDropdown.innerHTML = '<div class="px-4 py-2.5 text-sm text-gray-400">Tidak ada guru yang cocok.</div>';
                teacherDropdown.classList.remove('hidden');
                return;
            }
            teacherDropdown.innerHTML = filtered.map(buildTeacherRow).join('');
            teacherDropdown.classList.remove('hidden');
        }

        function renderStudents() {
            var pid = parseInt(programSelect.value, 10);
            studentsEmptyNoProgram.classList.add('hidden');
            studentsEmptyNoStudents.classList.add('hidden');
            studentsList.innerHTML = '';

            if (!pid) {
                studentsEmptyNoProgram.classList.remove('hidden');
                selectAllBtn.classList.add('hidden');
                return;
            }

            var students = studentsByProgram[pid] || [];
            if (students.length === 0) {
                studentsEmptyNoStudents.classList.remove('hidden');
                selectAllBtn.classList.add('hidden');
                return;
            }

            selectAllBtn.classList.remove('hidden');
            var html = '';
            students.forEach(function(s) {
                var checked = selectedStudentIds.indexOf(s.student_id) !== -1;
                var borderClass = checked ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300';
                var checkAttr = checked ? ' checked' : '';
                html += '<label class="flex items-center gap-2 text-sm p-2.5 bg-white rounded-lg border transition-colors cursor-pointer ' + borderClass + '">'
                    + '<input type="checkbox" class="rounded border-gray-300 text-indigo-600" value="' + s.student_id + '"' + checkAttr + ' onclick="window.__cssToggleStudent(' + s.student_id + ', this.checked)" />'
                    + '<span>' + escHtml(s.student_name) + '</span>'
                    + '</label>';
            });
            studentsList.innerHTML = html;
        }

        function escHtml(str) {
            var d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // Global helpers called by onclick attributes
        window.__cssAddTeacher = function(id) {
            if (selectedTeacherIds.indexOf(id) === -1) {
                selectedTeacherIds.push(id);
            }
            teacherSearchInput.value = '';
            teacherDropdown.classList.add('hidden');
            renderTeacherChips();
        };

        window.__cssRemoveTeacher = function(id) {
            var idx = selectedTeacherIds.indexOf(id);
            if (idx !== -1) selectedTeacherIds.splice(idx, 1);
            renderTeacherChips();
        };

        window.__cssToggleStudent = function(id, checked) {
            if (checked) {
                if (selectedStudentIds.indexOf(id) === -1) selectedStudentIds.push(id);
            } else {
                var idx = selectedStudentIds.indexOf(id);
                if (idx !== -1) selectedStudentIds.splice(idx, 1);
            }
            // Update border color
            var checkboxes = studentsList.querySelectorAll('input[type=checkbox]');
            for (var i = 0; i < checkboxes.length; i++) {
                var cb = checkboxes[i];
                var label = cb.closest('label');
                if (cb.value == id) {
                    if (checked) {
                        label.classList.remove('border-gray-200', 'hover:border-indigo-300');
                        label.classList.add('border-indigo-400', 'bg-indigo-50');
                    } else {
                        label.classList.remove('border-indigo-400', 'bg-indigo-50');
                        label.classList.add('border-gray-200', 'hover:border-indigo-300');
                    }
                }
            }
            syncStudentHidden();
        };

        window.__cssSelectAllStudents = function() {
            var checkboxes = studentsList.querySelectorAll('input[type=checkbox]');
            for (var i = 0; i < checkboxes.length; i++) {
                var cb = checkboxes[i];
                var id = parseInt(cb.value, 10);
                cb.checked = true;
                cb.closest('label').classList.remove('border-gray-200', 'hover:border-indigo-300');
                cb.closest('label').classList.add('border-indigo-400', 'bg-indigo-50');
                if (selectedStudentIds.indexOf(id) === -1) selectedStudentIds.push(id);
            }
            syncStudentHidden();
        };

        // Wire events
        programSelect.addEventListener('change', function() {
            renderTeacherChips();
            renderTeacherDropdown('');
            renderStudents();
        });

        teacherSearchInput.addEventListener('input', function() {
            renderTeacherDropdown(this.value);
        });

        teacherSearchInput.addEventListener('focusin', function() {
            renderTeacherDropdown(this.value);
        });

        teacherSearchInput.addEventListener('focusout', function() {
            setTimeout(function() { teacherDropdown.classList.add('hidden'); }, 300);
        });

        selectAllBtn.addEventListener('click', function() {
            window.__cssSelectAllStudents();
        });

        // Initial render
        if (preselectedProgramId) {
            programSelect.value = preselectedProgramId;
        }
        renderTeacherChips();
        renderTeacherDropdown('');
        renderStudents();
    })();
    </script>
</form>
