<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Admin') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">Kontrol Utama</h3>
                    <p class="mt-2 text-sm text-gray-600">CRUD data murid, guru, les, validasi presensi, dan analisis WA.</p>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">Kelas Bersama</h3>
                    <p class="mt-2 text-sm text-gray-600">Kelola kelas bersama, jadwal, dan laporan kehadiran bulanan.</p>
                    <div class="mt-4 flex gap-3">
                        <a href="{{ route('admin.class-students.index') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Murid Kelas</a>
                        <a href="{{ route('admin.class-student-sessions.index') }}" class="px-4 py-2 rounded-md border text-sm">Jadwal Murid</a>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">Keuangan</h3>
                    <p class="mt-2 text-sm text-gray-600">Pantau pendapatan kotor, gaji guru, dan pendapatan bersih per bulan.</p>
                    <a href="{{ route('admin.finance.index') }}" class="inline-flex mt-4 items-center px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Buka Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
