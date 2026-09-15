<form id="crud-form"
      x-data={
          teacherSearch: '',
          showTeacherDropdown: false,
          selectedTeacherIds: [],
          selectedStudentIds: [],
          teachersByProgram: window.__css_teachersByProgram__ || {},
          studentsByProgram: window.__css_studentsByProgram__ || {},
          programId: '',
          teachers: [],
          students: [],
          filteredTeachers() {
              if (!this.teacherSearch) return this.teachers;
              const q = this.teacherSearch.toLowerCase();
              return this.teachers.filter(t => t.name.toLowerCase().includes(q));
          },
          toggleTeacher(teacherId) {
              const idx = this.selectedTeacherIds.indexOf(teacherId);
              if (idx >= 0) {
                  this.selectedTeacherIds.splice(idx, 1);
              } else {
                  this.selectedTeacherIds.push(teacherId);
              }
              this.syncHiddenInputs();
          },
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
          onProgramChange() {
              const pid = parseInt(this.programId);
              this.teachers = (this.teachersByProgram && this.teachersByProgram[pid]) ? this.teachersByProgram[pid] : [];
              this.students = (this.studentsByProgram && this.studentsByProgram[pid]) ? this.studentsByProgram[pid] : [];
              this.selectedTeacherIds = [];
              this.selectedStudentIds = [];
              this.syncHiddenInputs();
          },
          init() {
              if (window.__css_session_teachers__) {
                  this.selectedTeacherIds = window.__css_session_teachers__.map(t => t.id);
              }
              if (window.__css_session_student_ids__) {
                  this.selectedStudentIds = [...window.__css_session_student_ids__];
              }
              this.syncHiddenInputs();
          },
          syncHiddenInputs() {
              const tc = document.getElementById('teacher-hidden-container');
              if (tc) {
                  tc.innerHTML = '';
                  this.selectedTeacherIds.forEach(id => {
                      const i = document.createElement('input');
                      i.type = 'hidden';
                      i.name = 'teacher_ids[]';
                      i.value = id;
                      tc.appendChild(i);
                  });
              }
              const sc = document.getElementById('student-hidden-container');
              if (sc) {
                  sc.innerHTML = '';
                  this.selectedStudentIds.forEach(id => {
                      const i = document.createElement('input');
                      i.type = 'hidden';
                      i.name = 'student_enrollment_map[]';
                      i.value = id;
                      sc.appendChild(i);
                  });
              }
          },
      }
      x-init="init()"
      @submit.prevent="$parent.submit()">

    @csrf
    @if($session) @method('PUT') @endif

    {{-- Program & Tanggal --}}
    <div class="grid md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Program <span class="text-rose-500">*</span></label>
            <select name="program_id"
                    x-model="programId"
                    @change="onProgramChange()"
                    class="crud-field-program_id w-full rounded-xl border border-gray-200 text-sm" required>
                <option value="">Pilih program kelas</option>
                @foreach($programs as $p)
                    <option value="{{ $p->id }}" @selected(($session?->program_id == $p->id) || (old('program_id') == $p->id))>{{ $p->name }}</option>
                @endforeach
            </select>
            <p class="crud-error-program_id text-sm text-rose-500 mt-1" style="display:none"></p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Sesi <span class="text-rose-500">*</span></label>
            <input type="date"
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
                   placeholder="Ketik nama guru untuk mencari..."
                   x-model="teacherSearch"
                   @focusin="showTeacherDropdown = true"
                   @focusout="setTimeout(() => { showTeacherDropdown = false; }, 300)"
                   class="w-full rounded-xl border border-gray-200 text-sm" />
            <div x-show="showTeacherDropdown && filteredTeachers().length > 0"
                 x-transition
                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                <template x-for="teacher in filteredTeachers()" :key="teacher.id">
                    <button type="button"
                            @click="toggleTeacher(teacher.id); teacherSearch = '';"
                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 flex items-center justify-between transition-colors"
                            :class="selectedTeacherIds.includes(teacher.id) ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700'">
                        <span x-text="teacher.name"></span>
                        <svg x-show="selectedTeacherIds.includes(teacher.id)" class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </template>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mt-2">
            <template x-for="teacherId in selectedTeacherIds" :key="teacherId">
                <span class="inline-flex items-center gap-1 pl-3 pr-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                    <span x-text="teachers.find(t => t.id === teacherId) ? teachers.find(t => t.id === teacherId).name : teacherId"></span>
                    <button type="button" @click="toggleTeacher(teacherId)" class="hover:text-rose-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </span>
            </template>
        </div>
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
                    x-show="students.length > 0"
                    @click="selectAllStudents()"
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                Pilih Semua
            </button>
        </div>
        <div class="border rounded-xl p-4 bg-gray-50 max-h-72 overflow-y-auto">
            <template x-if="students.length === 0 && !programId">
                <div class="text-center text-sm text-gray-400 py-4">Pilih program kelas terlebih dahulu.</div>
            </template>
            <template x-if="students.length === 0 && programId">
                <div class="text-center text-sm text-gray-400 py-4">Tidak ada murid aktif di enrollment program ini.</div>
            </template>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <template x-for="s in students" :key="s.student_id">
                    <label class="flex items-center gap-2 text-sm p-2.5 bg-white rounded-lg border transition-colors cursor-pointer"
                           :class="selectedStudentIds.includes(s.student_id) ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'">
                        <input type="checkbox"
                               :checked="selectedStudentIds.includes(s.student_id)"
                               @change="toggleStudent(s.student_id)"
                               class="rounded border-gray-300 text-indigo-600" />
                        <span x-text="s.student_name"></span>
                    </label>
                </template>
            </div>
        </div>
        <div id="student-hidden-container"></div>
        <p class="crud-error-student_enrollment_map text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Notes --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
        <textarea name="notes"
                  rows="2"
                  class="w-full rounded-xl border border-gray-200 text-sm">{{ $session?->notes ?? old('notes') }}</textarea>
    </div>
</form>
