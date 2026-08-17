<x-app-layout>
    <x-slot name="title">Tawaran Les</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tawaran Les</h2>
            <p class="text-sm text-gray-500 mt-0.5">Tawaran les yang tersedia untuk guru</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 font-medium">ID</th>
                                <th class="py-3 px-4 font-medium">Tingkat</th>
                                <th class="py-3 px-4 font-medium">Mapel</th>
                                <th class="py-3 px-4 font-medium">Jadwal</th>
                                <th class="py-3 px-4 font-medium">Catatan</th>
                                <th class="py-3 px-4 font-medium">Ambil Tawaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($offers as $offer)
                                @php
                                    $contact = $offer->contact_whatsapp ?: $defaultContact;
                                    $teacherName = $teacher?->name ?? auth()->user()?->name;

                                    $scheduleText = '';
                                    if ($offer->schedules) {
                                        $parts = [];
                                        foreach ($offer->schedules as $sch) {
                                            $parts[] = ($sch['day'] ?? '') . ' ' . ($sch['time'] ?? '');
                                        }
                                        $scheduleText = implode(', ', $parts);
                                    }

                                    $message = sprintf(
                                        'Halo admin, saya %s tertarik mengambil tawaran %s untuk tingkat %s, mapel %s, jadwal %s.',
                                        $teacherName,
                                        $offer->code,
                                        $offer->education_level,
                                        $offer->subject,
                                        $scheduleText
                                    );
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ $offer->code }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $offer->education_level }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $offer->subject }}</td>
                                    <td class="py-3 px-4 text-gray-600">
                                        @if ($offer->schedules)
                                            @foreach ($offer->schedules as $sch)
                                                <div class="text-xs">{{ $sch['day'] ?? '' }} {{ $sch['time'] ?? '' }}</div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">{{ $offer->note ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        @if ($contact)
                                            <div class="flex items-center gap-2">
                                                <textarea class="w-full rounded-xl border-gray-200 text-sm bg-gray-50" rows="2" readonly>{{ $message }}</textarea>
                                                <a href="https://wa.me/{{ App\Helpers\WhatsappHelper::toWaFormat($contact) }}?text={{ urlencode($message) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors whitespace-nowrap">Chat WA</a>
                                            </div>
                                        @else
                                            <span class="text-gray-500 text-sm">Kontak admin belum diatur.</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <x-empty-state icon="🎯" title="Belum ada tawaran" description="Belum ada tawaran les yang tersedia saat ini." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>