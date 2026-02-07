<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Laporan Kelas Bersama</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.class-reports.index') }}" class="p-6 grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bulan</label>
                        <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="mt-1 w-full border-gray-300 rounded-md" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tahun</label>
                        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="mt-1 w-full border-gray-300 rounded-md" required />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Terapkan</button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <h3 class="text-lg font-semibold mb-4">Total Kehadiran Murid</h3>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Murid</th>
                                <th class="py-2">Total Hadir</th>
                                <th class="py-2">Template WA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($students as $student)
                                @php
                                    $total = (int) ($studentTotals[$student->id] ?? 0);
                                    $message = sprintf('Total kehadiran kelas bersama bulan %02d/%d adalah %d pertemuan.', $month, $year, $total);
                                @endphp
                                <tr>
                                    <td class="py-2">{{ $student->name }}</td>
                                    <td class="py-2">{{ $total }}</td>
                                    <td class="py-2">
                                        <div class="flex items-center gap-2">
                                            <textarea class="w-full border-gray-300 rounded-md text-sm" rows="2" readonly>{{ $message }}</textarea>
                                            @if ($student->whatsapp)
                                                <a href="https://wa.me/{{ $student->whatsapp }}?text={{ urlencode($message) }}" class="text-emerald-600" target="_blank" rel="noopener">Kirim</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <h3 class="text-lg font-semibold mb-4">Total Mengajar Guru</h3>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Guru</th>
                                <th class="py-2">Total Mengajar</th>
                                <th class="py-2">Template WA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($teachers as $teacher)
                                @php
                                    $total = (int) ($teacherTotals[$teacher->id] ?? 0);
                                    $message = sprintf('Total mengajar kelas bersama bulan %02d/%d adalah %d pertemuan.', $month, $year, $total);
                                @endphp
                                <tr>
                                    <td class="py-2">{{ $teacher->name }}</td>
                                    <td class="py-2">{{ $total }}</td>
                                    <td class="py-2">
                                        <div class="flex items-center gap-2">
                                            <textarea class="w-full border-gray-300 rounded-md text-sm" rows="2" readonly>{{ $message }}</textarea>
                                            @if ($teacher->whatsapp)
                                                <a href="https://wa.me/{{ $teacher->whatsapp }}?text={{ urlencode($message) }}" class="text-emerald-600" target="_blank" rel="noopener">Kirim</a>
                                            @endif
                                        </div>
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
