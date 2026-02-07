<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Murid') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">Riwayat Les</h3>
                    <p class="mt-2 text-sm text-gray-600">Lihat daftar les, guru, dan total pertemuan.</p>
                    <div class="mt-4">
                        <a href="{{ route('murid.history.index') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Buka Riwayat</a>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">Tagihan</h3>
                    <p class="mt-2 text-sm text-gray-600">Cek total tagihan dan status pembayaran.</p>
                    <div class="mt-4">
                        <a href="{{ route('murid.billing.index') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Lihat Tagihan</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
