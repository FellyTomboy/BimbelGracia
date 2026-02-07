<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <p class="text-sm text-gray-500">Role: {{ auth()->user()->role?->value }}</p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold">Ringkasan Cepat</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Cek presensi, laporan, dan status pembayaran sesuai akses role Anda.
                        </p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold">Aksi Utama</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Gunakan menu samping untuk CRUD data, validasi presensi, dan analisis otomatis.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
