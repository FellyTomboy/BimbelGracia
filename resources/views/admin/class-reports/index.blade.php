<x-app-layout>
    <x-slot name="title">WA Kelas</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WA Kelas</h2>
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
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="py-2">{{ $row['name'] }}</td>
                                    <td class="py-2">{{ $row['total'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-2 text-gray-500" colspan="2">Tidak ada data untuk periode ini.</td>
                                </tr>
                            @endforelse
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
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="py-2">{{ $row['teacher'] }}</td>
                                    <td class="py-2">{{ $row['total'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-2 text-gray-500" colspan="2">Tidak ada data untuk periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
