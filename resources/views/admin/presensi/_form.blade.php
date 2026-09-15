<form id="crud-form"
      x-data='{
          availableStudents: [],
          selectedProgramType: "privat",
          studentIds: [],

          init() {
              // Pre-load students for the currently-selected enrollment (edit mode)
              const select = document.getElementById("enrollment-select");
              if (select && select.value) {
                  this.selectedEnrollmentId = select.value;
                  this.loadStudents(select.value);
              }
          },

          loadStudents(enrollmentId) {
              const option = document.querySelector(`#enrollment-select option[value="${enrollmentId}"]`);
              if (!option) {
                  this.availableStudents = [];
                  this.selectedProgramType = "privat";
                  this.studentIds = [];
                  return;
              }
              try {
                  this.availableStudents = JSON.parse(option.dataset.students || "[]");
              } catch (e) {
                  this.availableStudents = [];
              }
              this.selectedProgramType = option.dataset.type || "privat";
              this.studentIds = this.availableStudents.map(st => st.id);
          },

          toggleStudent(id) {
              const idx = this.studentIds.indexOf(id);
              if (idx >= 0) {
                  this.studentIds.splice(idx, 1);
              } else {
                  this.studentIds.push(id);
              }
          },
      }'
      @submit.prevent="$parent.submit()">

    @csrf
    @if($attendance) @method('PUT') @endif

    {{-- Enrollment selector --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Enrollment</label>
        <select id="enrollment-select"
                name="enrollment_id"
                x-model="selectedEnrollmentId"
                @change="loadStudents(selectedEnrollmentId)"
                {{ $attendance ? 'disabled' : '' }}
                class="crud-field-enrollment_id w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                required>
            @if(!$attendance)
                <option value="">Pilih enrollment</option>
            @endif
            @foreach($enrollments as $e)
                <option value="{{ $e['id'] }}"
                        data-students="{{ json_encode($e['students']) }}"
                        data-type="{{ $e['program']['type'] ?? 'privat' }}"
                        @selected(($attendance?->enrollment_id == $e['id']))>
                    #{{ $e['id'] }} — {{ $e['program']['name'] ?? '-' }} ({{ $e['teacher']['display_name'] ?? '-' }})
                </option>
            @endforeach
        </select>
        {{-- Hidden input so enrollment_id is submitted in edit mode --}}
        @if($attendance)
            <input type="hidden" name="enrollment_id" x-model="selectedEnrollmentId" />
        @endif
        <p class="crud-error-enrollment_id text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Tanggal Les --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Les</label>
        <input type="date"
               name="lesson_date"
               value="{{ $attendance?->lesson_date?->format('Y-m-d') }}"
               class="crud-field-lesson_date w-full rounded-xl border border-gray-200 text-sm"
               max="{{ date('Y-m-d') }}"
               required />
        <p class="crud-error-lesson_date text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Student checkboxes (hidden for kelas programs) --}}
    <div x-show="selectedProgramType !== 'kelas'" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Murid yang Hadir</label>
        <p class="text-xs text-gray-500 mb-3">Centang murid yang hadir.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <template x-for="student in availableStudents" :key="student.id">
                <label class="flex items-center gap-2 text-sm p-2 bg-gray-50 rounded-lg border border-gray-200 hover:border-indigo-300 cursor-pointer transition-colors"
                       :class="studentIds.includes(student.id) ? 'border-indigo-400 bg-indigo-50' : ''">
                    <input type="checkbox"
                           :checked="studentIds.includes(student.id)"
                           @change="toggleStudent(student.id)"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span x-text="student.name"></span>
                </label>
            </template>
        </div>
        <p x-show="availableStudents.length === 0" class="text-xs text-gray-400 italic mt-2">Pilih enrollment terlebih dahulu.</p>
        <p class="crud-error-student_ids text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Image upload --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti (opsional)</label>
        @if($attendance?->image)
            <div class="mb-2">
                <img src="{{ asset('storage/' . $attendance->image) }}" class="h-24 rounded-xl border border-gray-200 object-cover" alt="Bukti presensi" />
            </div>
        @endif
        <input type="file"
               name="image"
               accept="image/*"
               class="crud-field-image w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100" />
        @if(!$attendance)
            <p class="mt-1 text-xs text-gray-500">Maks 5MB.</p>
        @endif
        <p class="crud-error-image text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Notes --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
        <textarea name="notes"
                  rows="2"
                  class="w-full rounded-xl border border-gray-200 text-sm">{{ $attendance?->notes ?? '' }}</textarea>
    </div>
</form>
