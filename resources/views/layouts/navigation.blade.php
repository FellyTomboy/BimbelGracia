<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @if (auth()->user()?->role?->value === 'admin')
                        @php
                            $adminPresensiActive = request()->routeIs('admin.attendance-windows.*', 'admin.presensi.*');
                            $adminMasterActive = request()->routeIs('admin.students.*', 'admin.teachers.*', 'admin.programs.*', 'admin.enrollments.*', 'admin.lesson-offers.*');
                            $adminKelasActive = request()->routeIs('admin.class-students.*', 'admin.class-student-sessions.*');
                            $adminAnalisisActive = request()->routeIs('admin.analysis.ortu', 'admin.analysis.ortu-kelas', 'admin.analysis.guru', 'admin.payments.*', 'admin.discounts.*');
                            $adminLaporanActive = request()->routeIs('admin.class-reports.*', 'admin.history.*', 'admin.finance.*', 'admin.export.*');
                            $dropdownBase = 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
                        @endphp
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            {{ __('Admin') }}
                        </x-nav-link>
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="{{ $dropdownBase }} {{ $adminPresensiActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ __('Presensi') }}
                                    <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.attendance-windows.index')">
                                    {{ __('Periode Presensi') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.presensi.index')">
                                    {{ __('Validasi Presensi') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="{{ $dropdownBase }} {{ $adminMasterActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ __('Data Master') }}
                                    <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.students.index')">
                                    {{ __('Murid') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.teachers.index')">
                                    {{ __('Guru') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.programs.index')">
                                    {{ __('Program') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.enrollments.index')">
                                    {{ __('Enrollment') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.lesson-offers.index')">
                                    {{ __('Tawaran Les') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="{{ $dropdownBase }} {{ $adminKelasActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ __('Kelas') }}
                                    <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.class-students.index')">
                                    {{ __('Murid Kelas') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.class-student-sessions.index')">
                                    {{ __('Jadwal Murid') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="{{ $dropdownBase }} {{ $adminAnalisisActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ __('WA') }}
                                    <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.analysis.ortu')">
                                    {{ __('WA Ortu Privat') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.analysis.ortu-kelas')">
                                    {{ __('WA Ortu Kelas') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.analysis.guru')">
                                    {{ __('WA Guru') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.discounts.index')">
                                    {{ __('Diskon/Promo') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.payments.ortu')">
                                    {{ __('Pembayaran Ortu') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.payments.guru')">
                                    {{ __('Pembayaran Guru') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                        <x-dropdown align="left" width="60">
                            <x-slot name="trigger">
                                <button class="{{ $dropdownBase }} {{ $adminLaporanActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ __('Laporan') }}
                                    <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.class-reports.index')">
                                    {{ __('WA Kelas') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.history.students')">
                                    {{ __('Riwayat') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.finance.index')">
                                    {{ __('Keuangan') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.export.index')">
                                    {{ __('Export & Backup') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif
                    @if (auth()->user()?->role?->value === 'guru')
                        @php
                            $guruAktif = request()->routeIs('guru.presensi.*', 'guru.history.*');
                            $guruFinance = request()->routeIs('guru.salary-projection.*');
                            $guruOffer = request()->routeIs('guru.tawaran.*');
                            $dropdownBase = $dropdownBase ?? 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
                        @endphp
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="{{ $dropdownBase }} {{ ($guruAktif || $guruFinance || $guruOffer) ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ __('Menu Guru') }}
                                    <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('guru.presensi.index')">
                                    {{ __('Presensi') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('guru.history.index')">
                                    {{ __('Riwayat Les') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('guru.salary-projection.index')">
                                    {{ __('Proyeksi Gaji') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('guru.tawaran.index')">
                                    {{ __('Tawaran Les') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif
                    @if (auth()->user()?->role?->value === 'murid')
                        @php
                            $muridActive = request()->routeIs('murid.history.*', 'murid.billing.*');
                            $dropdownBase = $dropdownBase ?? 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
                        @endphp
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="{{ $dropdownBase }} {{ $muridActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ __('Menu Murid') }}
                                    <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('murid.history.index')">
                                    {{ __('Riwayat Les') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('murid.billing.index')">
                                    {{ __('Tagihan') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if (auth()->user()?->role?->value === 'admin')
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    {{ __('Admin') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.attendance-windows.index')" :active="request()->routeIs('admin.attendance-windows.*')">
                    {{ __('Periode Presensi') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.presensi.index')" :active="request()->routeIs('admin.presensi.*')">
                    {{ __('Validasi Presensi') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')">
                    {{ __('Murid') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.teachers.index')" :active="request()->routeIs('admin.teachers.*')">
                    {{ __('Guru') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.programs.index')" :active="request()->routeIs('admin.programs.*')">
                    {{ __('Program') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.enrollments.index')" :active="request()->routeIs('admin.enrollments.*')">
                    {{ __('Enrollment') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.lesson-offers.index')" :active="request()->routeIs('admin.lesson-offers.*')">
                    {{ __('Tawaran Les') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.bank-accounts.index')" :active="request()->routeIs('admin.bank-accounts.*')">
                    {{ __('Rekening Bimbel') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.class-students.index')" :active="request()->routeIs('admin.class-students.*')">
                    {{ __('Murid Kelas') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.class-student-sessions.index')" :active="request()->routeIs('admin.class-student-sessions.*')">
                    {{ __('Jadwal Murid') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.analysis.ortu')" :active="request()->routeIs('admin.analysis.ortu')">
                    {{ __('WA Ortu Privat') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.analysis.ortu-kelas')" :active="request()->routeIs('admin.analysis.ortu-kelas')">
                    {{ __('WA Ortu Kelas') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.analysis.guru')" :active="request()->routeIs('admin.analysis.guru')">
                    {{ __('WA Guru') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.discounts.index')" :active="request()->routeIs('admin.discounts.*')">
                    {{ __('Diskon/Promo') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.payments.ortu')" :active="request()->routeIs('admin.payments.ortu')">
                    {{ __('Pembayaran Ortu') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.payments.guru')" :active="request()->routeIs('admin.payments.guru')">
                    {{ __('Pembayaran Guru') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.class-reports.index')" :active="request()->routeIs('admin.class-reports.*')">
                    {{ __('WA Kelas') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.history.students')" :active="request()->routeIs('admin.history.*')">
                    {{ __('Riwayat') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.finance.index')" :active="request()->routeIs('admin.finance.*')">
                    {{ __('Keuangan') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.export.index')" :active="request()->routeIs('admin.export.*')">
                    {{ __('Export & Backup') }}
                </x-responsive-nav-link>
            @endif
            @if (auth()->user()?->role?->value === 'guru')
                <x-responsive-nav-link :href="route('guru.presensi.index')" :active="request()->routeIs('guru.presensi.*')">
                    {{ __('Presensi') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('guru.history.index')" :active="request()->routeIs('guru.history.*')">
                    {{ __('Riwayat Les') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('guru.salary-projection.index')" :active="request()->routeIs('guru.salary-projection.*')">
                    {{ __('Proyeksi Gaji') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('guru.tawaran.index')" :active="request()->routeIs('guru.tawaran.*')">
                    {{ __('Tawaran Les') }}
                </x-responsive-nav-link>
            @endif
            @if (auth()->user()?->role?->value === 'murid')
                <x-responsive-nav-link :href="route('murid.history.index')" :active="request()->routeIs('murid.history.*')">
                    {{ __('Riwayat Les') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('murid.billing.index')" :active="request()->routeIs('murid.billing.*')">
                    {{ __('Tagihan') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
