<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tawaran Les</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">ID</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Mapel</th>
                                <th class="py-2">Jadwal</th>
                                <th class="py-2">Catatan</th>
                                <th class="py-2">Ambil Tawaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($offers as $offer)
                                @php
                                    $contact = $offer->contact_whatsapp ?: $defaultContact;
                                    $teacherName = $teacher?->name ?? auth()->user()?->name;
                                    $message = sprintf(
                                        'Halo admin, saya %s tertarik mengambil tawaran %s untuk murid %s, mapel %s, jadwal %s %s.',
                                        $teacherName,
                                        $offer->code,
                                        $offer->student?->name ?? '-',
                                        $offer->subject,
                                        $offer->schedule_day,
                                        $offer->schedule_time
                                    );
                                @endphp
                                <tr>
                                    <td class="py-2 font-medium">{{ $offer->code }}</td>
                                    <td class="py-2">{{ $offer->student?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $offer->subject }}</td>
                                    <td class="py-2">{{ $offer->schedule_day }} {{ $offer->schedule_time }}</td>
                                    <td class="py-2">{{ $offer->note ?? '-' }}</td>
                                    <td class="py-2">
                                        @if ($contact)
                                            <div class="flex items-center gap-2">
                                                <textarea class="w-full border-gray-300 rounded-md text-sm" rows="2" readonly>{{ $message }}</textarea>
                                                <a href="https://wa.me/{{ $contact }}?text={{ urlencode($message) }}" class="text-emerald-600" target="_blank" rel="noopener">Chat</a>
                                            </div>
                                        @else
                                            <span class="text-gray-500">Kontak admin belum diatur.</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-500">Belum ada tawaran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
