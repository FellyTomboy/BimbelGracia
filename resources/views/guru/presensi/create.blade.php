<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Isi Presensi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('guru.presensi.store') }}" class="p-6 space-y-4">
                    @csrf

                    <div class="bg-slate-50 rounded-md px-4 py-3 text-sm text-gray-600">
                        Periode aktif: {{ sprintf('%02d', $openWindow->month) }}/{{ $openWindow->year }}
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Enrollment</label>
                        <select name="enrollment_id" id="enrollment-select" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih enrollment</option>
                            @foreach ($enrollments as $enrollment)
                                <option value="{{ $enrollment->id }}" @selected(old('enrollment_id', $enrollments->first()?->id) == $enrollment->id)>
                                    #{{ $enrollment->id }} - {{ $enrollment->program?->name ?? '-' }} - {{ $enrollment->students->pluck('name')->implode(', ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('enrollment_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Checklist Tanggal</label>
                        <div class="mt-2 grid grid-cols-8 gap-2 text-sm">
                            @for ($day = 1; $day <= 31; $day++)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="dates[]" value="{{ $day }}" @checked(is_array(old('dates')) && in_array($day, old('dates', []))) />
                                    <span>{{ $day }}</span>
                                </label>
                            @endfor
                        </div>
                        @error('dates')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Les</label>
                        <input type="number" name="total_lessons" value="{{ old('total_lessons') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('total_lessons')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-2 text-xs text-gray-500">Total pertemuan harus sama dengan jumlah tanggal.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Hadir per Murid</label>
                        @error('student_totals')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        <div class="mt-2 space-y-3">
                            @foreach ($enrollments as $enrollment)
                                <div class="space-y-2 border border-gray-200 rounded-md p-3" data-enrollment-section="{{ $enrollment->id }}">
                                    <p class="text-xs text-gray-500">#{{ $enrollment->id }} - {{ $enrollment->program?->name ?? '-' }}</p>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        @foreach ($enrollment->students as $student)
                                            <label class="flex items-center justify-between gap-3 text-sm">
                                                <span>{{ $student->name }}</span>
                                                <input type="number" name="student_totals[{{ $student->id }}]" value="{{ old('student_totals.'.$student->id, 0) }}" class="w-24 border-gray-300 rounded-md" min="0" required />
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Gunakan 0 jika murid tidak hadir.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea name="notes" class="mt-1 w-full border-gray-300 rounded-md">{{ old('notes') }}</textarea>
                        @error('notes')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('guru.presensi.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const enrollmentSelect = document.getElementById('enrollment-select');
        const sections = document.querySelectorAll('[data-enrollment-section]');

        const syncSections = () => {
            const activeId = enrollmentSelect.value;
            sections.forEach((section) => {
                const isActive = section.dataset.enrollmentSection === activeId;
                section.classList.toggle('hidden', !isActive);
                section.querySelectorAll('input').forEach((input) => {
                    input.disabled = !isActive;
                });
            });
        };

        enrollmentSelect.addEventListener('change', syncSections);
        syncSections();
    </script>
</x-app-layout>
