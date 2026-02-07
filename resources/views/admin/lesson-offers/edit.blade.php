<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Tawaran Les</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.lesson-offers.update', $lessonOffer) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    @php
                        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                    @endphp

                    <div>
                        <label class="block text-sm font-medium text-gray-700">ID Tawaran</label>
                        <input name="code" value="{{ old('code', $lessonOffer->code) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('code')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Murid</label>
                        <select name="student_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected(old('student_id', $lessonOffer->student_id) == $student->id)>{{ $student->name }}</option>
                            @endforeach
                        </select>
                        @error('student_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mapel</label>
                        <input name="subject" value="{{ old('subject', $lessonOffer->subject) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('subject')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hari</label>
                            <select name="schedule_day" class="mt-1 w-full border-gray-300 rounded-md" required>
                                @foreach ($days as $day)
                                    <option value="{{ $day }}" @selected(old('schedule_day', $lessonOffer->schedule_day) === $day)>{{ $day }}</option>
                                @endforeach
                            </select>
                            @error('schedule_day')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jam</label>
                            <input type="time" name="schedule_time" value="{{ old('schedule_time', $lessonOffer->schedule_time) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('schedule_time')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kontak WhatsApp (opsional)</label>
                        <input name="contact_whatsapp" value="{{ old('contact_whatsapp', $lessonOffer->contact_whatsapp) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        <p class="text-xs text-gray-500 mt-1">Jika kosong, gunakan kontak default di config.</p>
                        @error('contact_whatsapp')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan</label>
                        <textarea name="note" rows="3" class="mt-1 w-full border-gray-300 rounded-md">{{ old('note', $lessonOffer->note) }}</textarea>
                        @error('note')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="open" @selected(old('status', $lessonOffer->status) === 'open')>open</option>
                            <option value="closed" @selected(old('status', $lessonOffer->status) === 'closed')>closed</option>
                        </select>
                        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.lesson-offers.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
