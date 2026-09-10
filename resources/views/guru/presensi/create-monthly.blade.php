<x-app-layout>
    <x-slot name="title">Isi Presensi Bulanan</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Isi Presensi Bulanan</h2>
            <p class="text-sm text-gray-500 mt-0.5">Catat kehadiran murid untuk beberapa tanggal sekaligus</p>
        </div>
    </x-slot>

    <div class="py-8"
        x-data="{
            sessions: [{ id: Date.now(), date: '', studentIds: [], notes: '', imagePreview: null }],
            selectedEnrollmentId: '',
            selectedProgramType: 'privat',
            availableStudents: [],
            activeTab: 0,
            maxSessions: 31,
            latePenaltyEnabled: {{ app(\App\Services\AttendanceFineService::class)->isLatePenaltyEnabled() ? 'true' : 'false' }},

            init() {
                this.$watch('selectedEnrollmentId', (val) => this.loadStudents(val));
            },

            loadStudents(enrollmentId) {
                const option = document.querySelector('#enrollment-select option[value=\&quot;' + enrollmentId + '\&quot;]');
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
                // Reset student selections across all sessions
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
                    imagePreview: null
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

            formatDate(dateStr) {
                if (!dateStr) return 'Baru';
                const d = new Date(dateStr + 'T00:00:00');
                const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                return d.getDate().toString().padStart(2,'0') + ' ' + months[d.getMonth()];
            },

            handleImageChange(event, index) {
                const file = event.target.files[0];
                if (!file) return;
                this.sessions[index].imagePreview = null;
                const reader = new FileReader();
                reader.onload = (e) => { this.sessions[index].imagePreview = e.target.result; };
                reader.readAsDataURL(file);
            },

            validateBeforeSubmit() {
                if (!this.selectedEnrollmentId) {
                    alert('Pilih enrollment terlebih dahulu.');
                    return false;
                }
                const emptyDateSessions = this.sessions.filter(s => !s.date);
                if (emptyDateSessions.length > 0) {
                    alert('Semua tab harus memiliki tanggal les.');
                    return false;
                }
                if (this.selectedProgramType !== 'kelas') {
                    const emptyStudentSessions = this.sessions.filter(s => s.studentIds.length === 0);
                    if (emptyStudentSessions.length > 0) {
                        alert('Setiap tanggal harus memiliki minimal 1 murid hadir.');
                        return false;
                    }
                }
                return true;
            }
        }">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('guru.presensi.store-bulk') }}"
                enctype="multipart/form-data"
                @submit.prevent="if (!validateBeforeSubmit()) return; $el.submit();">

                @csrf

                {{-- Enrollment selector — top level, global --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-4 p-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Enrollment</label>
                        <select id="enrollment-select" name="enrollment_id" x-model="selectedEnrollmentId"
                            class="mt-1 w-full rounded-xl border-gray-200 text-sm" required>
                            <option value="">Pilih enrollment</option>
                            @foreach ($enrollments as $enrollment)
                                <option value="{{ $enrollment->id }}"
                                    data-students="{{ $enrollment->students->map(fn($s) => ['id' => $s->id, 'name' => $s->display_name]) }}"
                                    data-type="{{ $enrollment->program?->type }}">
                                    #{{ $enrollment->id }} - {{ $enrollment->program?->name ?? '-' }} - {{ $enrollment->students->map->display_name->implode(', ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('enrollment_id')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Student legend (shown once, updates on enrollment change) --}}
                    <div x-show="selectedEnrollmentId && selectedProgramType !== 'kelas'" class="mt-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Murid Terdaftar</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="student in availableStudents" :key="student.id">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                                    x-text="student.name"></span>
                            </template>
                            <span x-show="availableStudents.length === 0" class="text-xs text-gray-400 italic">Pilih enrollment terlebih dahulu</span>
                        </div>
                    </div>

                    <div x-show="selectedEnrollmentId && selectedProgramType === 'kelas'" class="mt-4">
                        <p class="text-xs text-gray-500 italic">Presensi kelas — tidak perlu memilih murid.</p>
                    </div>

                    {{-- Late penalty notice --}}
                    <div x-show="latePenaltyEnabled" class="mt-3">
                        <p class="text-xs text-amber-600">Presensi maksimal 3 hari setelah les. Jika lebih, status akan menunggu validasi admin.</p>
                    </div>
                </div>

                {{-- Tab bar + content --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-4">
                    <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 overflow-x-auto">
                        <template x-for="(session, index) in sessions" :key="session.id">
                            <button type="button"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors whitespace-nowrap"
                                :class="activeTab === index ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                @click="activeTab = index">
                                <span x-text="formatDate(session.date)"></span>
                                <span x-show="sessions.length > 1" @click.stop="removeSession(index)" class="ml-0.5 hover:text-rose-500 cursor-pointer font-bold text-base leading-none">×</span>
                            </button>
                        </template>

                        <button type="button"
                            class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium border border-dashed border-indigo-300 text-indigo-600 hover:bg-indigo-50 transition-colors"
                            @click="addSession()"
                            :class="{ 'opacity-40 cursor-not-allowed': sessions.length >= maxSessions }"
                            :disabled="sessions.length >= maxSessions">
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
                                        required />
                                </div>

                                {{-- Student checkboxes (privat only) --}}
                                <div x-show="selectedProgramType !== 'kelas'" class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Murid yang Hadir</label>
                                    <p class="text-xs text-gray-500 mb-3">Centang murid yang benar-benar hadir pada sesi ini.</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <template x-for="student in availableStudents" :key="student.id">
                                            <label class="flex items-center gap-2 text-sm p-2 bg-gray-50 rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors cursor-pointer">
                                                <input type="checkbox"
                                                    :name="`sessions[${sessionIndex}][student_ids][]`"
                                                    :value="student.id"
                                                    x-model="session.studentIds"
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                                <span x-text="student.name"></span>
                                            </label>
                                        </template>
                                        <p x-show="availableStudents.length === 0"
                                            class="text-sm text-gray-400 italic col-span-2">Pilih enrollment di atas.</p>
                                    </div>
                                </div>

                                {{-- Photo upload --}}
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti (opsional)</label>
                                    <input type="file"
                                        :name="`sessions[${sessionIndex}][image]`"
                                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                        class="mt-1 w-full rounded-xl border-gray-200 text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"
                                        @change="handleImageChange($event, sessionIndex)" />
                                    <p class="mt-1 text-xs text-gray-500">Maks 5MB per foto.</p>
                                    <div x-show="session.imagePreview" class="mt-2">
                                        <img :src="session.imagePreview" class="max-w-xs rounded-lg border border-gray-200" alt="Preview" />
                                    </div>
                                </div>

                                {{-- Notes --}}
                                <div class="mb-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                                    <textarea
                                        :name="`sessions[${sessionIndex}][notes]`"
                                        x-model="session.notes"
                                        class="mt-1 w-full rounded-xl border-gray-200 text-sm"
                                        rows="2"
                                        placeholder="Opsional"></textarea>
                                </div>

                            </div>
                        </template>
                    </div>
                </div>

                {{-- Submit row --}}
                <div class="flex justify-end gap-3">
                    <a href="{{ route('guru.presensi.index') }}"
                        class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                        Kirim Semua Presensi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
