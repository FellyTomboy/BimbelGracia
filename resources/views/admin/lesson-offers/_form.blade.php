<form id="crud-form"
      x-data='{
          schedules: @json($lessonOffer?->schedules ?? [["day"=>"","time"=>""]]),
          addSchedule() {
              this.schedules.push({day: "", time: ""});
          },
          removeSchedule(i) {
              if (this.schedules.length > 1) {
                  this.schedules.splice(i, 1);
              }
          },
          errors: {},
      }'
      @submit.prevent="$parent.submit()">

    @csrf

    {{-- ID Tawaran (read-only) --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">ID Tawaran</label>
        <input value="{{ $lessonOffer?->code ?? 'Auto-generated' }}"
               class="w-full border border-gray-300 rounded-lg bg-gray-50 text-gray-500 px-3 py-2 text-sm"
               disabled />
    </div>

    {{-- Tingkat Pendidikan --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Tingkat Pendidikan <span class="text-rose-500">*</span>
        </label>
        <select name="education_level"
                class="crud-field-education_level w-full border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-sm"
                required>
            <option value="">Pilih tingkat pendidikan</option>
            @foreach ($educationLevels as $level)
                <option value="{{ $level }}"
                        @selected(old('education_level', $lessonOffer?->education_level ?? '') === $level)>
                    {{ $level }}
                </option>
            @endforeach
        </select>
        <p class="crud-error-education_level text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Mapel --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Mapel <span class="text-rose-500">*</span>
        </label>
        <input name="subject"
               value="{{ old('subject', $lessonOffer?->subject ?? '') }}"
               class="crud-field-subject w-full border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-sm"
               required />
        <p class="crud-error-subject text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Jadwal (dynamic rows via Alpine) --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Jadwal <span class="text-rose-500">*</span>
        </label>
        <div class="space-y-2">
            <template x-for="(sch, i) in schedules" :key="i">
                <div class="flex items-center gap-2">
                    <select :name="'schedules[' + i + '][day]'"
                            x-model="sch.day"
                            class="crud-field-schedules w-full border border-gray-300 rounded-lg text-sm"
                            required>
                        <option value="">Hari</option>
                        @foreach ($days as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                    <select :name="'schedules[' + i + '][time]'"
                            x-model="sch.time"
                            class="crud-field-schedules w-full border border-gray-300 rounded-lg text-sm"
                            required>
                        <option value="">Waktu</option>
                        @foreach ($times as $time)
                            <option value="{{ $time }}">{{ ucfirst($time) }}</option>
                        @endforeach
                    </select>
                    <button type="button"
                            @click="removeSchedule(i)"
                            :disabled="schedules.length <= 1"
                            class="text-rose-600 text-sm font-medium px-2 disabled:opacity-30 hover:text-rose-800 transition-colors">✕</button>
                </div>
            </template>
        </div>
        <button type="button" @click="addSchedule()"
                class="mt-3 inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition-all">
            + Tambah jadwal
        </button>
        <p class="crud-error-schedules text-sm text-rose-500 mt-1" style="display:none"></p>
        <p class="crud-error-schedules.*.day text-sm text-rose-500 mt-1" style="display:none"></p>
        <p class="crud-error-schedules.*.time text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Kontak WhatsApp --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Kontak WhatsApp (opsional)</label>
        <input name="contact_whatsapp"
               value="{{ old('contact_whatsapp', $lessonOffer?->contact_whatsapp ?? '') }}"
               class="crud-field-contact_whatsapp w-full border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-sm" />
        <p class="crud-error-contact_whatsapp text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Catatan --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
        <textarea name="note" rows="2"
                  class="crud-field-note w-full border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-sm">{{ old('note', $lessonOffer?->note ?? '') }}</textarea>
        <p class="crud-error-note text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Status --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Status <span class="text-rose-500">*</span>
        </label>
        <select name="status"
                class="crud-field-status w-full border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-sm"
                required>
            <option value="open" @selected(old('status', $lessonOffer?->status ?? 'open') === 'open')>open</option>
            <option value="closed" @selected(old('status', $lessonOffer?->status ?? '') === 'closed')>closed</option>
        </select>
        <p class="crud-error-status text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</form>
