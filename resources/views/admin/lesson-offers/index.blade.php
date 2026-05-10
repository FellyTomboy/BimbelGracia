<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tawaran Les</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.lesson-offers.inactive') }}" class="px-4 py-2 rounded-md border text-sm">Data tidak aktif</a>
                <a href="{{ route('admin.lesson-offers.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Tawaran</a>
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
                                <th class="py-2">ID</th>
                                <th class="py-2">Tingkat</th>
                                <th class="py-2">Mapel</th>
                                <th class="py-2">Jadwal</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Kontak WA</th>
                                <th class="py-2">Catatan</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($offers as $offer)
                                <tr>
                                    <td class="py-2 font-medium">{{ $offer->code }}</td>
                                    <td class="py-2">{{ $offer->education_level }}</td>
                                    <td class="py-2">{{ $offer->subject }}</td>
                                    <td class="py-2">
                                        @if ($offer->schedules)
                                            @foreach ($offer->schedules as $sch)
                                                <div>{{ $sch['day'] ?? '' }} {{ $sch['time'] ?? '' }}</div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="py-2">{{ $offer->status }}</td>
                                    <td class="py-2">{{ $offer->contact_whatsapp ?? '-' }}</td>
                                    <td class="py-2">{{ $offer->note ?? '-' }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.lesson-offers.edit', $offer) }}" class="text-indigo-600">Edit</a>
                                        <form method="POST" action="{{ route('admin.lesson-offers.destroy', $offer) }}">
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
