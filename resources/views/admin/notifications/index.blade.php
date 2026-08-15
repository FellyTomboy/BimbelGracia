<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifikasi Presensi</h2>
            <p class="text-sm text-gray-500 mt-0.5">Penolakan presensi dari orangtua yang menunggu konfirmasi admin.</p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="font-semibold text-gray-900">Antrian Konfirmasi</h3>
                    <p class="text-sm text-gray-500 mt-1">Tentukan apakah penolakan orangtua dikonfirmasi atau tidak.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 font-medium">Tanggal Les</th>
                                <th class="py-3 px-4 font-medium">Program</th>
                                <th class="py-3 px-4 font-medium">Guru</th>
                                <th class="py-3 px-4 font-medium">Murid</th>
                                <th class="py-3 px-4 font-medium">Alasan Penolakan</th>
                                <th class="py-3 px-4 font-medium">Diajukan</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($attendances as $attendance)
                                <tr class="hover:bg-gray-50/50 transition-colors align-top">
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ $attendance->lesson_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" />
                                    </td>
                                    <td class="py-3 px-4">
                                        <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->name ?? '-'" type="guru" />
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($attendance->students->count() > 0)
                                            @foreach ($attendance->students as $student)
                                                <x-hibernated-label :model="$student" :label="$student->display_name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 max-w-xs">
                                        <p class="text-sm">{{ $attendance->parent_rejection_reason ?? '-' }}</p>
                                    </td>
                                    <td class="py-3 px-4 text-gray-500 whitespace-nowrap">
                                        {{ $attendance->parent_reviewed_at?->format('d M Y H:i') ?? '-' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('admin.notifications.uphold-rejection', $attendance) }}" onsubmit="return confirm('Konfirmasi penolakan presensi ini?')">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-medium hover:bg-rose-700">Konfirmasi Ditolak</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.notifications.dismiss', $attendance) }}" onsubmit="return confirm('Tolak konfirmasi penolakan ini? Status presensi tidak akan diubah.')">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-xs font-medium hover:bg-gray-200">Tidak Dikonfirmasi</button>
                                            </form>
                                            <a href="{{ route('admin.presensi.show', $attendance) }}" class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-medium hover:bg-indigo-100">Detail</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-10">
                                        <x-empty-state icon="🔔" title="Tidak ada notifikasi" description="Belum ada penolakan presensi yang menunggu konfirmasi." />
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