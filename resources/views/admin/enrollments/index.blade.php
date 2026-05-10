<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Enrollments</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.enrollments.inactive') }}" class="px-4 py-2 rounded-md border text-sm">Data tidak aktif</a>
                <a href="{{ route('admin.enrollments.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Enrollment</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Program</th>
                                <th class="py-2">Guru</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Harga Ortu</th>
                                <th class="py-2">Gaji Guru</th>
                                <th class="py-2">Validasi</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($enrollments as $enrollment)
                                <tr>
                                    <td class="py-2 font-medium">
                                        <x-hibernated-label :model="$enrollment->program" :label="$enrollment->program?->name ?? '-'" type="program" />
                                    </td>
                                    <td class="py-2">
                                        <x-hibernated-label :model="$enrollment->teacher" :label="$enrollment->teacher?->name ?? '-'" type="guru" />
                                    </td>
                                    <td class="py-2">
                                        @if ($enrollment->students->count() > 0)
                                            @foreach ($enrollment->students as $student)
                                                <x-hibernated-label :model="$student" :label="$student->name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-2">Rp {{ number_format($enrollment->parent_rate) }}</td>
                                    <td class="py-2">Rp {{ number_format($enrollment->teacher_rate) }}</td>
                                    <td class="py-2">{{ $enrollment->validation_status }}</td>
                                    <td class="py-2">{{ $enrollment->status }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="text-indigo-600">Edit</a>
                                        <form method="POST" action="{{ route('admin.enrollments.destroy', $enrollment) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600">Hibernasi</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
