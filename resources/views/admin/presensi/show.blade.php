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
                <p><span class="font-semibold">Tanggal Les:</span> {{ $attendance->lesson_date?->format('d M Y') ?? '-' }}</p>
                <p><span class="font-semibold">Program:</span> <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" /></p>
                <p><span class="font-semibold">Guru:</span> <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->name ?? '-'" type="guru" /></p>
                <p><span class="font-semibold">Murid:</span>
                    @if ($attendance->students->count() > 0)
                        @foreach ($attendance->students as $student)
                            <x-hibernated-label :model="$student" :label="$student->name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    @else
                        -
                    @endif
                </p>
                <p><span class="font-semibold">Enrollment:</span> #{{ $attendance->enrollment_id }}</p>
                <p><span class="font-semibold">Catatan:</span> {{ $attendance->notes ?? '-' }}</p>
                <p><span class="font-semibold">Status:</span>
                    @if ($attendance->status_validation === 'terima')
                        <span class="text-emerald-600 font-semibold">Diterima</span>
                    @elseif ($attendance->status_validation === 'terlambat')
                        <span class="text-amber-600 font-semibold">Terlambat (potongan 10%)</span>
                    @elseif ($attendance->status_validation === 'ditolak')
                        <span class="text-rose-600 font-semibold">Ditolak</span>
                    @else
                        <span class="text-gray-500 font-semibold">Pending</span>
                    @endif
                </p>

                @if ($attendance->image)
                    <div>
                        <p class="font-semibold">Foto Bukti:</p>
                        <img src="{{ asset('storage/' . $attendance->image) }}" class="mt-2 max-w-md rounded-md border" alt="Bukti presensi" />
                    </div>
                @endif

                <div>
                    <p class="font-semibold">Kehadiran per Murid:</p>
                    <ul class="mt-2 list-disc pl-5 text-sm text-gray-700">
                        @forelse ($attendance->students as $student)
                            <li>
                                <x-hibernated-label :model="$student" :label="$student->name" type="murid privat" />:
                                {{ $student->pivot->total_present ? 'Hadir' : 'Tidak Hadir' }}
                            </li>
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
                            <option value="terima" @selected(old('status', $attendance->status_validation) === 'terima')>Terima</option>
                            <option value="terlambat" @selected(old('status', $attendance->status_validation) === 'terlambat')>Terlambat</option>
                            <option value="ditolak" @selected(old('status', $attendance->status_validation) === 'ditolak')>Tolak</option>
                        </select>
                        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
