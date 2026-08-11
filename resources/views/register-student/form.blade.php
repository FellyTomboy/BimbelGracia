<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pendaftaran Murid - Bimbel Gracia</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/website/logo_bimbel.jpg') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #fdf2f8 100%);
            min-height: 100vh;
        }
        .btn-remove {
            transition: all 0.2s;
        }
    </style>
</head>
<body class="py-12 px-4">
    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white font-bold text-2xl flex items-center justify-center mx-auto shadow-lg mb-4">
                BG
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Formulir Pendaftaran Murid</h1>
            <p class="text-gray-600 mt-1">Bimbel Gracia - Bimbingan Belajar Privat & Kelas</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
            <form method="POST" action="{{ route('register-student.submit', $token ?? \App\Http\Controllers\Admin\NewStudentController::PERMANENT_TOKEN) }}" class="space-y-6">
                @csrf

                {{-- Data Orang Tua --}}
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Data Orang Tua / Wali</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="parent_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Orang Tua / Wali <span class="text-red-500">*</span></label>
                            <input type="text" id="parent_name" name="parent_name" value="{{ old('parent_name') }}" required
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('parent_name') border-red-300 @enderror"
                                placeholder="Nama lengkap orang tua / wali" />
                            @error('parent_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp Orang Tua <span class="text-red-500">*</span></label>
                            <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('whatsapp') border-red-300 @enderror"
                                placeholder="08xxxxxxxxxx (digunakan untuk login)" />
                            @error('whatsapp') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                            <textarea id="address" name="address" rows="2"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('address') border-red-300 @enderror"
                                placeholder="Alamat lengkap">{{ old('address') }}</textarea>
                            @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Data Murid (bisa lebih dari 1) --}}
                <div class="border-t border-gray-100 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Data Murid</h2>
                        <button type="button" onclick="addStudent()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-600 text-sm font-medium hover:bg-indigo-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Murid
                        </button>
                    </div>

                    <div id="students-container" class="space-y-4">
                        @php
                            $oldStudents = old('students', [['name' => '']]);
                        @endphp
                        @foreach ($oldStudents as $index => $student)
                            <div class="student-item relative p-4 rounded-xl border border-gray-200 bg-gray-50/50 {{ $loop->first ? '' : 'student-extra' }}">
                                @if (!$loop->first)
                                    <button type="button" onclick="removeStudent(this)" class="btn-remove absolute top-2 right-2 p-1 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @endif
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold student-number">{{ $loop->iteration }}</span>
                                    <span class="text-sm font-medium text-gray-500">Murid {{ $loop->iteration }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Murid <span class="text-red-500">*</span></label>
                                    <input type="text" name="students[{{ $index }}][name]" value="{{ $student['name'] ?? '' }}" required
                                        class="w-full rounded-xl border-gray-200 bg-white focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                        placeholder="Nama lengkap murid" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('students') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('students.*.name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Catatan --}}
                <div class="border-t border-gray-100 pt-6">
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
                        <textarea id="notes" name="notes" rows="3"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('notes') border-red-300 @enderror"
                            placeholder="Informasi tambahan yang perlu diketahui">{{ old('notes') }}</textarea>
                        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <p class="text-xs text-gray-400">Data akan diverifikasi oleh admin Bimbel Gracia</p>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                        Kirim Pendaftaran
                    </button>
                </div>
            </form>
        </div>

        <p class="text-center text-sm text-gray-400 mt-6">Bimbel Gracia - Bimbingan Belajar Privat & Kelas</p>
    </div>

    <script>
        let studentIndex = {{ count(old('students', [['name' => '']])) }};

        function addStudent() {
            const container = document.getElementById('students-container');
            const template = document.createElement('div');
            template.className = 'student-item student-extra relative p-4 rounded-xl border border-gray-200 bg-gray-50/50';
            template.innerHTML = `
                <button type="button" onclick="removeStudent(this)" class="btn-remove absolute top-2 right-2 p-1 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold student-number"></span>
                    <span class="text-sm font-medium text-gray-500">Murid <span class="student-label-text"></span></span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Murid <span class="text-red-500">*</span></label>
                    <input type="text" name="students[\${studentIndex}][name]" required
                        class="w-full rounded-xl border-gray-200 bg-white focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                        placeholder="Nama lengkap murid" />
                </div>
            `;
            container.appendChild(template);
            studentIndex++;
            updateStudentNumbers();
        }

        function removeStudent(btn) {
            const item = btn.closest('.student-item');
            if (item && item.classList.contains('student-extra')) {
                item.remove();
                updateStudentNumbers();
            }
        }

        function updateStudentNumbers() {
            const items = document.querySelectorAll('.student-item');
            items.forEach((item, index) => {
                const number = item.querySelector('.student-number');
                const labelText = item.querySelector('.student-label-text');
                if (number) number.textContent = index + 1;
                if (labelText) labelText.textContent = index + 1;
            });
        }
    </script>
</body>
</html>