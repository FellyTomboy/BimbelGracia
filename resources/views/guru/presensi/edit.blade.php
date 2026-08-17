<x-app-layout>
    <x-slot name="title">Edit Presensi</x-slot>
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
                        <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea name="notes" class="mt-1 w-full border-gray-300 rounded-md">{{ old('notes', $attendance->notes) }}</textarea>
                        @error('notes')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    @if ($attendance->enrollment?->isKelas())
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Murid yang Hadir
                                <span class="text-xs text-gray-400">(kosongkan jika hanya ingin mengubah tanggal/foto/keterangan)</span>
                            </label>
                            @php
                                $presentStudentIds = $attendance->students->pluck('id')->toArray();
                            @endphp
                            <div class="grid grid-cols-2 gap-1 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-2">
                                @forelse ($attendance->enrollment->students as $student)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                            {{ in_array($student->id, old('student_ids', $presentStudentIds)) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600" />
                                        {{ $student->display_name }}
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400 col-span-2">Belum ada murid terdaftar.</p>
                                @endforelse
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Kosongkan semua checkbox jika tidak ingin mengubah daftar murid.</p>
                            @error('student_ids.*')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @else
                        {{-- Private session: guru must select attending students --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Murid yang Hadir</label>
                            @php
                                $presentStudentIds = $attendance->students->pluck('id')->toArray();
                            @endphp
                            <div class="grid grid-cols-2 gap-1 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-2">
                                @forelse ($attendance->enrollment->students as $student)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                            {{ in_array($student->id, old('student_ids', $presentStudentIds)) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600" />
                                        {{ $student->display_name }}
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400 col-span-2">Tidak ada murid terdaftar.</p>
                                @endforelse
                            </div>
                            @error('student_ids.*')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('guru.presensi.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
