{{-- Enrollment selector (outside form so x-model binds to root scope) --}}
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Enrollment</label>
    <select id="prc-enrollment-select"
            x-model="selectedEnrollmentId"
            class="mt-1 w-full rounded-xl border-gray-200 text-sm" required>
        <option value="">Pilih enrollment</option>
        @foreach ($createEnrollments as $e)
            <option value="{{ $e['id'] }}"
                data-students="{{ json_encode($e['students']) }}"
                data-type="{{ $e['program']['type'] ?? 'privat' }}">
                #{{ $e['id'] }} — {{ $e['program']['name'] ?? '-' }} ({{ $e['teacher']['display_name'] ?? '-' }})
            </option>
        @endforeach
    </select>
    <p class="crud-error-enrollment_id mt-1 text-sm text-rose-600" style="display:none"></p>
</div>

{{-- Student legend --}}
<div x-show="selectedEnrollmentId && selectedProgramType !== 'kelas'" class="mb-4">
    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Murid Terdaftar</p>
    <div class="flex flex-wrap gap-2">
        <template x-for="student in availableStudents" :key="student.id">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                x-text="student.name"></span>
        </template>
        <span x-show="availableStudents.length === 0" class="text-xs text-gray-400 italic">Pilih enrollment terlebih dahulu</span>
    </div>
</div>

<div x-show="selectedEnrollmentId && selectedProgramType === 'kelas'" class="mb-4">
    <p class="text-xs text-gray-500 italic">Presensi kelas — tidak perlu memilih murid.</p>
</div>

{{-- Late penalty notice --}}
<div x-show="latePenaltyEnabled" class="mb-3">
    <p class="text-xs text-amber-600">Presensi maksimal 3 hari setelah les. Jika lebih, status akan menunggu validasi admin.</p>
</div>

{{-- Session form --}}
<form id="prc-form"
      x-on:submit.prevent="submitModal()"
      enctype="multipart/form-data">

    @csrf

    {{-- Tab bar --}}
    <div class="bg-gray-50 rounded-xl border border-gray-200 mb-4">
        <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200 overflow-x-auto">
            <template x-for="(session, index) in sessions" :key="session.id">
                <button type="button"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors whitespace-nowrap"
                    :class="activeTab === index ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                    @click="activeTab = index">
                    <span x-text="formatDate(session.date)"></span>
                    <span x-show="sessions.length > 1" @click.stop="removeSession(index)" class="ml-0.5 hover:text-rose-500 cursor-pointer font-bold text-base leading-none">×</span>
                </button>
            </template>

            <button type="button"
                x-show="billingMode === 'monthly'"
                class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium border border-dashed border-indigo-300 text-indigo-600 hover:bg-indigo-50 transition-colors"
                @click="addSession()"
                :class="{ 'opacity-40 cursor-not-allowed': sessions.length >= maxSessions }"
                :disabled="sessions.length >= maxSessions || billingMode !== 'monthly'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Tanggal
            </button>

            <span class="ml-auto text-xs text-gray-400" x-text="sessions.length + ' tanggal'"></span>
        </div>

        {{-- Tab panels --}}
        <div class="p-6">
            <template x-for="(session, sessionIndex) in sessions" :key="session.id">
                <div x-show="activeTab === sessionIndex"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100">

                    {{-- Date input --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Les</label>
                        <input type="date"
                            :name="`sessions[${sessionIndex}][lesson_date]`"
                            x-model="session.date"
                            class="mt-1 w-full rounded-xl border-gray-200 text-sm"
                            max="{{ date('Y-m-d') }}"
                            required />
                    </div>

                    {{-- Student checkboxes (privat only) --}}
                    <div x-show="selectedProgramType !== 'kelas'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Murid yang Hadir</label>
                        <p class="text-xs text-gray-500 mb-3">Centang murid yang benar-benar hadir pada sesi ini.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="student in availableStudents" :key="student.id">
                                <label class="flex items-center gap-2 text-sm p-2 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors cursor-pointer">
                                    <input type="checkbox"
                                        :name="`sessions[${sessionIndex}][student_ids][]`"
                                        :value="student.id"
                                        x-model="session.studentIds"
                                        class="rounded border-gray-300 text-indigo-600" />
                                    <span x-text="student.name"></span>
                                </label>
                            </template>
                        </div>
                        <p x-show="availableStudents.length === 0" class="text-xs text-gray-400 italic mt-2">Pilih enrollment terlebih dahulu.</p>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea
                            :name="`sessions[${sessionIndex}][notes]`"
                            x-model="session.notes"
                            rows="2"
                            class="mt-1 w-full rounded-xl border-gray-200 text-sm"
                            placeholder="Opsional..."></textarea>
                    </div>

                    {{-- Photo --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti</label>
                        <input type="file"
                            :name="`sessions[${sessionIndex}][image]`"
                            @change="handleImageChange($event, sessionIndex)"
                            accept="image/*"
                            class="mt-1 w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100" />
                        <div x-show="session.imagePreview" class="mt-2">
                            <img :src="session.imagePreview" class="h-24 rounded-lg border border-gray-200 object-cover" />
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <p class="crud-error-sessions mt-1 text-sm text-rose-600" style="display:none"></p>

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-3">
        <button type="button"
            @click="close()"
            class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
            Batal
        </button>
        <button type="submit"
            class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2">
            <span x-show="!submitting">Simpan</span>
            <span x-show="submitting" class="flex items-center gap-1.5">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Menyimpan...
            </span>
        </button>
    </div>
</form>
