<x-app-layout>
    <x-slot name="title">Isi Murid Hadir - Kelas</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Isi Murid Hadir - Kelas</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-100">
                    <p class="text-sm text-gray-600">
                        <strong>Tanggal:</strong> {{ $attendance->lesson_date?->format('d/m/Y') ?? '-' }} |
                        <strong>Program:</strong> {{ $enrollment?->program?->name ?? '-' }} |
                        <strong>Guru:</strong> {{ $enrollment?->teacher?->name ?? '-' }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Centang murid yang hadir pada sesi ini.</p>
                </div>

                <form method="POST" action="{{ route('admin.class-attendance.update', $attendance->id) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-96 overflow-y-auto">
                        @forelse ($allStudents as $student)
                            <label class="flex items-center gap-2 text-sm p-2 rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors cursor-pointer @if(in_array($student->id, $selectedStudentIds)) border-indigo-400 bg-indigo-50 @endif">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                    @checked(in_array($student->id, $selectedStudentIds))
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span>{{ $student->display_name }}</span>
                            </label>
                        @empty
                            <p class="text-gray-400 col-span-3">Tidak ada murid aktif.</p>
                        @endforelse
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('admin.class-attendance.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>