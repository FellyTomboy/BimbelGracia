<form id="cls-att-form" x-on:submit.prevent="$dispatch('cls-att-submit')">
    @csrf
    @method('PUT')

    <div class="space-y-4">
        <p class="text-sm text-gray-600">
            Centang murid yang hadir pada sesi ini.
        </p>

        <div class="grid grid-cols-1 gap-2 max-h-64 overflow-y-auto">
            @forelse ($allStudents as $student)
                <label class="flex items-center gap-2 text-sm p-2 rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors cursor-pointer @if(in_array($student->id, $selectedStudentIds)) border-indigo-400 bg-indigo-50 @endif">
                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                        @checked(in_array($student->id, $selectedStudentIds))
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span>{{ $student->display_name }}</span>
                </label>
            @empty
                <p class="text-gray-400 col-span-3 text-sm">Tidak ada murid aktif.</p>
            @endforelse
        </div>
    </div>

    {{-- Actions ─────────────────────────────────────────────────────────────── --}}
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button"
            @click="$dispatch('cls-att-close')"
            class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
            Batal
        </button>
        <button type="submit"
            :disabled="submitting"
            class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors flex items-center gap-2">
            <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span x-text="submitting ? 'Menyimpan...' : 'Simpan'">Simpan</span>
        </button>
    </div>
</form>
