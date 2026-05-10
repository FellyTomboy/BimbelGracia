<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Presensi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('guru.presensi.update', $attendance) }}" class="p-6 space-y-4" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="bg-slate-50 rounded-md px-4 py-3 text-sm text-gray-600">
                        Tanggal Les: {{ $attendance->lesson_date?->format('d M Y') ?? '-' }}
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Les</label>
                        <input type="date" name="lesson_date" value="{{ old('lesson_date', $attendance->lesson_date?->format('Y-m-d')) }}" class="mt-1 w-full border-gray-300 rounded-md" required max="{{ date('Y-m-d') }}" />
                        @error('lesson_date')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Foto Bukti</label>
                        @if ($attendance->image)
                            <div class="mt-2 mb-2">
                                <img src="{{ asset('storage/' . $attendance->image) }}" class="max-w-xs rounded-md border" alt="Bukti presensi" />
                            </div>
                        @endif
                        <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="mt-1 w-full border-gray-300 rounded-md" />
                        @error('image')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin mengubah foto.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kehadiran Murid</label>
                        @php
                            $attendanceStudents = $attendance->students->keyBy('id');
                        @endphp
                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($attendance->enrollment->students as $student)
                                <label class="flex items-center justify-between gap-3 text-sm">
                                    <span>{{ $student->name }}</span>
                                    <select name="student_totals[{{ $student->id }}]" class="w-24 border-gray-300 rounded-md" required>
                                        <option value="0" @selected(old('student_totals.'.$student->id, $attendanceStudents->get($student->id)?->pivot?->total_present ?? 0) == 0)>Tidak Hadir</option>
                                        <option value="1" @selected(old('student_totals.'.$student->id, $attendanceStudents->get($student->id)?->pivot?->total_present ?? 0) == 1)>Hadir</option>
                                    </select>
                                </label>
                            @endforeach
                        </div>
                        @error('student_totals')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea name="notes" class="mt-1 w-full border-gray-300 rounded-md">{{ old('notes', $attendance->notes) }}</textarea>
                        @error('notes')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('guru.presensi.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
