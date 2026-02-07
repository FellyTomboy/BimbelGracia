<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Presensi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('guru.presensi.update', $attendance) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="bg-slate-50 rounded-md px-4 py-3 text-sm text-gray-600">
                        Periode: {{ sprintf('%02d', $attendance->month) }}/{{ $attendance->year }}
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                        <div class="mt-2 grid grid-cols-8 gap-2 text-sm">
                            @for ($day = 1; $day <= 31; $day++)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="dates[]" value="{{ $day }}" @checked(in_array($day, $attendance->dates ?? [])) />
                                    <span>{{ $day }}</span>
                                </label>
                            @endfor
                        </div>
                        @error('dates')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Les</label>
                        <input type="number" name="total_lessons" value="{{ old('total_lessons', $attendance->total_lessons) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('total_lessons')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
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
