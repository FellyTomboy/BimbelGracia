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
                <p><span class="font-semibold">Guru:</span> {{ $attendance->teacher?->name ?? '-' }}</p>
                <p><span class="font-semibold">Murid:</span> {{ $attendance->student?->name ?? '-' }}</p>
                <p><span class="font-semibold">ID Les:</span> {{ $attendance->lesson?->code ?? '-' }}</p>
                <p><span class="font-semibold">Tanggal:</span> {{ $attendance->dates ? implode(', ', $attendance->dates) : '-' }}</p>
                <p><span class="font-semibold">Total:</span> {{ $attendance->total_lessons }}</p>
                <p><span class="font-semibold">Catatan:</span> {{ $attendance->notes ?? '-' }}</p>
                <p><span class="font-semibold">Status:</span> {{ $attendance->status }}</p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.presensi.lesson', $attendance) }}" class="flex flex-col gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Perbaiki ID Les</label>
                        <select name="lesson_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih les</option>
                            @foreach ($lessons as $lesson)
                                <option value="{{ $lesson->id }}" @selected(old('lesson_id', $attendance->lesson_id) == $lesson->id)>
                                    {{ $lesson->code }} - {{ $lesson->teacher?->name ?? '-' }} - {{ $lesson->student?->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        @error('lesson_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-2 text-xs text-gray-500">Mengubah ID les akan menyamakan guru & murid dari les terpilih dan mengembalikan status ke pending.</p>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan ID Les</button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.presensi.validate', $attendance) }}" class="flex flex-col gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Validasi</label>
                        <select name="status" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="validated" @selected(old('status', $attendance->status) === 'validated')>Valid</option>
                            <option value="needs_fix" @selected(old('status', $attendance->status) === 'needs_fix')>Perlu Perbaikan</option>
                        </select>
                        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
