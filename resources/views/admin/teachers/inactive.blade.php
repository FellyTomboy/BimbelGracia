<x-app-layout>
    <x-slot name="title">Guru (Hibernasi)</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Guru (Hibernasi)</h2>
            <a href="{{ route('admin.teachers.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
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
                                <th class="py-2">Nama Lengkap</th>
                                <th class="py-2">Nama Panggilan</th>
                                <th class="py-2">Email</th>
                                <th class="py-2">WhatsApp</th>
                                <th class="py-2">Tarif Kelas</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($teachers as $teacher)
                                <tr>
                                    <td class="py-2">
                                        <span class="font-medium text-gray-900 @empty($teacher->full_name) text-amber-600 font-semibold @endempty">{{ $teacher->full_name ?: '—' }}</span>
                                        @empty($teacher->full_name)<span class="text-xs text-amber-500 ml-1">(kosong)</span>@endempty
                                    </td>
                                    <td class="py-2">
                                        <span class="text-gray-700 @empty($teacher->nickname) text-gray-400 @endempty">{{ $teacher->nickname ?: '—' }}</span>
                                        @empty($teacher->nickname)<span class="text-xs text-gray-400 ml-1">(kosong)</span>@endempty
                                    </td>
                                    <td class="py-2">{{ $teacher->user?->email ?? '-' }}</td>
                                    <td class="py-2">{{ $teacher->whatsapp_number ?? '-' }}</td>
                                    <td class="py-2">Rp {{ number_format($teacher->class_rate ?? 0) }}</td>
                                    <td class="py-2">hibernasi</td>
                                    <td class="py-2">
                                        <form method="POST" action="{{ route('admin.teachers.restore', $teacher->id) }}">
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
