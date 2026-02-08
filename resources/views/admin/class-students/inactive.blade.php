<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Murid Kelas Bersama (Hibernasi)</h2>
            <a href="{{ route('admin.class-students.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
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
                                <th class="py-2">Nama</th>
                                <th class="py-2">WA Utama</th>
                                <th class="py-2">WA Cadangan</th>
                                <th class="py-2">Tarif / Pertemuan</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($students as $student)
                                <tr>
                                    <td class="py-2 font-medium">{{ $student->name }}</td>
                                    <td class="py-2">{{ $student->whatsapp_primary ?? '-' }}</td>
                                    <td class="py-2">{{ $student->whatsapp_secondary ?? '-' }}</td>
                                    <td class="py-2">Rp {{ number_format($student->rate_per_meeting) }}</td>
                                    <td class="py-2">hibernasi</td>
                                    <td class="py-2">
                                        <form method="POST" action="{{ route('admin.class-students.restore', $student->id) }}">
                                            @csrf
                                            <button type="submit" class="text-emerald-600">Restore</button>
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
