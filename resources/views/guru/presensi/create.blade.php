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
                        <label class="block text-sm font-medium text-gray-700">Les Privat</label>
                        <select name="lesson_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih les</option>
                            @foreach ($lessons as $lesson)
                                <option value="{{ $lesson->id }}" @selected(old('lesson_id') == $lesson->id)>
                                    {{ $lesson->code }} - {{ $lesson->student?->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        @error('lesson_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
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
</x-app-layout>
