<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">Presensi Bulanan</h3>
                    <p class="mt-2 text-sm text-gray-600">Isi presensi hanya jika periode dibuka admin.</p>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">Riwayat & Proyeksi</h3>
                    <p class="mt-2 text-sm text-gray-600">Cek riwayat les dan proyeksi gaji setiap bulan.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
