<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Presensi</h2>
            <a href="{{ route('admin.presensi.index') }}" class="text-sm text-gray-500">Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                <p><span class="font-semibold">Periode:</span> {{ sprintf('%02d', $attendance->month) }}/{{ $attendance->year }}</p>
                <p><span class="font-semibold">Program:</span> {{ $attendance->enrollment?->program?->name ?? '-' }}</p>
                <p><span class="font-semibold">Guru:</span> {{ $attendance->enrollment?->teacher?->name ?? '-' }}</p>
                <p><span class="font-semibold">Murid:</span> {{ $attendance->students->pluck('name')->implode(', ') ?: '-' }}</p>
                <p><span class="font-semibold">Enrollment:</span> #{{ $attendance->enrollment_id }}</p>
                <p><span class="font-semibold">Tanggal:</span> {{ $attendance->dates ? implode(', ', $attendance->dates) : '-' }}</p>
                <p><span class="font-semibold">Total:</span> {{ $attendance->total_lessons }}</p>
                <p><span class="font-semibold">Catatan:</span> {{ $attendance->notes ?? '-' }}</p>
                <p><span class="font-semibold">Status:</span> {{ $attendance->status_validation }}</p>
                <div>
                    <p class="font-semibold">Total Hadir per Murid:</p>
                    <ul class="mt-2 list-disc pl-5 text-sm text-gray-700">
                        @forelse ($attendance->students as $student)
                            <li>{{ $student->name }}: {{ $student->pivot->total_present }}</li>
                        @empty
                            <li>-</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.presensi.enrollment', $attendance) }}" class="flex flex-col gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Perbaiki Enrollment</label>
                        <select name="enrollment_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih enrollment</option>
                            @foreach ($enrollments as $enrollment)
                                <option value="{{ $enrollment->id }}" @selected(old('enrollment_id', $attendance->enrollment_id) == $enrollment->id)>
                                    #{{ $enrollment->id }} - {{ $enrollment->program?->name ?? '-' }} - {{ $enrollment->teacher?->name ?? '-' }} - {{ $enrollment->students->pluck('name')->implode(', ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('enrollment_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        @if ($isClassPlaceholder)
                            <p class="mt-2 text-xs text-amber-600">Presensi ini terkait murid kelas bersama, jadi hanya program bertipe kelas yang bisa dipilih.</p>
                        @endif
                        <p class="mt-2 text-xs text-gray-500">Mengubah enrollment akan mengganti data murid dan mengembalikan status ke pending.</p>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan Enrollment</button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.presensi.validate', $attendance) }}" class="flex flex-col gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Validasi</label>
                        <select name="status" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="valid" @selected(old('status', $attendance->status_validation) === 'valid')>Valid</option>
                            <option value="revisi" @selected(old('status', $attendance->status_validation) === 'revisi')>Perlu Perbaikan</option>
                        </select>
                        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
