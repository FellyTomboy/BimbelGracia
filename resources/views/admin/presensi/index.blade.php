<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Validasi Presensi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Periode</th>
                                <th class="py-2">Guru</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">ID Les</th>
                                <th class="py-2">Total</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($attendances as $attendance)
                                <tr>
                                    <td class="py-2">{{ sprintf('%02d', $attendance->month) }}/{{ $attendance->year }}</td>
                                    <td class="py-2">{{ $attendance->teacher?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $attendance->student?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $attendance->lesson?->code ?? '-' }}</td>
                                    <td class="py-2">{{ $attendance->total_lessons }}</td>
                                    <td class="py-2">{{ $attendance->status }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('admin.presensi.show', $attendance) }}" class="text-indigo-600">Detail</a>
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
