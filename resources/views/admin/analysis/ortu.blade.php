<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">WA Ortu</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.analysis.ortu') }}" class="p-6 grid md:grid-cols-3 gap-4">
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

            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Les Privat</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        @foreach ($privatSummaries as $summary)
                            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                                <div>
                                    <h4 class="text-lg font-semibold">Kontak Ortu</h4>
                                    <p class="text-sm text-gray-500">WA: {{ $summary['contact'] !== 'unknown' ? $summary['contact'] : '-' }}</p>
                                </div>
                                <div class="text-sm text-gray-700 space-y-2">
                                    @foreach ($summary['students'] as $studentSummary)
                                        <div class="border rounded-md p-3 space-y-2">
                                            <p class="font-semibold">{{ $studentSummary['student']?->name ?? 'Murid' }}</p>
                                            @foreach ($studentSummary['lines'] as $line)
                                                <div class="flex justify-between">
                                                    <span>{{ $line['label'] }}</span>
                                                    <span>{{ $line['count'] }} x {{ number_format($line['rate']) }} = {{ number_format($line['total']) }}</span>
                                                </div>
                                            @endforeach
                                            <div class="flex justify-between text-sm font-semibold border-t pt-2">
                                                <span>Total</span>
                                                <span>{{ number_format($studentSummary['total']) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                    <div class="flex justify-between font-semibold border-t pt-2">
                                        <span>Total Semua Murid</span>
                                        <span>{{ number_format($summary['total']) }}</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs uppercase tracking-wide text-gray-500">Template WA</label>
                                    <textarea class="mt-2 w-full border-gray-300 rounded-md text-sm" rows="6" readonly>{{ $summary['message'] }}</textarea>
                                    @if ($summary['contact'] !== 'unknown')
                                        <a href="https://wa.me/{{ $summary['contact'] }}?text={{ urlencode($summary['message']) }}" class="inline-flex mt-3 items-center px-4 py-2 rounded-md bg-emerald-600 text-white text-sm" target="_blank" rel="noopener">Kirim WA</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-4">Les Kelas Bersama</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        @foreach ($classSummaries as $summary)
                            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                                <div>
                                    <h4 class="text-lg font-semibold">Kontak Ortu</h4>
                                    <p class="text-sm text-gray-500">WA: {{ $summary['contact'] !== 'unknown' ? $summary['contact'] : '-' }}</p>
                                </div>
                                <div class="text-sm text-gray-700 space-y-2">
                                    @foreach ($summary['students'] as $studentSummary)
                                        <div class="border rounded-md p-3 space-y-2">
                                            <p class="font-semibold">{{ $studentSummary['student']?->name ?? 'Murid' }}</p>
                                            <div class="flex justify-between">
                                                <span>Pertemuan</span>
                                                <span>{{ $studentSummary['count'] }} x {{ number_format($studentSummary['rate']) }} = {{ number_format($studentSummary['total']) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                    <div class="flex justify-between font-semibold border-t pt-2">
                                        <span>Total Semua Murid</span>
                                        <span>{{ number_format($summary['total']) }}</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs uppercase tracking-wide text-gray-500">Template WA</label>
                                    <textarea class="mt-2 w-full border-gray-300 rounded-md text-sm" rows="6" readonly>{{ $summary['message'] }}</textarea>
                                    @if ($summary['contact'] !== 'unknown')
                                        <a href="https://wa.me/{{ $summary['contact'] }}?text={{ urlencode($summary['message']) }}" class="inline-flex mt-3 items-center px-4 py-2 rounded-md bg-emerald-600 text-white text-sm" target="_blank" rel="noopener">Kirim WA</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
