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
                            $menuLengkapActive = request()->routeIs('admin.students.*', 'admin.teachers.*', 'admin.programs.*', 'admin.enrollments.*', 'admin.lesson-offers.*', 'admin.presensi.*', 'admin.class-students.*', 'admin.class-student-sessions.*', 'admin.analysis.ortu', 'admin.analysis.ortu-kelas', 'admin.analysis.guru', 'admin.payments.*', 'admin.discounts.*', 'admin.class-reports.*', 'admin.history.*', 'admin.finance.*', 'admin.export.*');
                            $dropdownBase = 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
                        @endphp
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            {{ __('Admin') }}
                        </x-nav-link>
                        <div class="relative" id="menu-lengkap-wrapper">
                            <button onclick="toggleMenuLengkap()" class="{{ $dropdownBase }} {{ $menuLengkapActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                {{ __('Menu Lengkap') }}
                                <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div id="menu-lengkap-dropdown" class="absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50 py-2 hidden">
                                <div class="relative" data-submenu="data-master">
                                    <span onclick="toggleSubmenu('data-master')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                        Data Master
                                        <svg class="h-4 w-4 sub-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                    <div class="pl-4 hidden" data-submenu-content="data-master">
                                        <a href="{{ route('admin.students.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Murid</a>
                                        <a href="{{ route('admin.teachers.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Guru</a>
                                        <a href="{{ route('admin.programs.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Program</a>
                                        <a href="{{ route('admin.enrollments.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Enrollment</a>
                                        <a href="{{ route('admin.lesson-offers.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tawaran Les</a>
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <a href="{{ route('admin.presensi.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Validasi Presensi</a>
                                    </div>
                                </div>
                                <div class="relative" data-submenu="kelas">
                                    <span onclick="toggleSubmenu('kelas')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                        Kelas
                                        <svg class="h-4 w-4 sub-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                    <div class="pl-4 hidden" data-submenu-content="kelas">
                                        <a href="{{ route('admin.class-students.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Murid Kelas</a>
                                        <a href="{{ route('admin.class-student-sessions.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Jadwal Murid</a>
                                        <a href="{{ route('admin.analysis.ortu-kelas') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">WA Ortu Kelas</a>
                                    </div>
                                </div>
                                <div class="relative" data-submenu="wa">
                                    <span onclick="toggleSubmenu('wa')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                        WA
                                        <svg class="h-4 w-4 sub-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                    <div class="pl-4 hidden" data-submenu-content="wa">
                                        <a href="{{ route('admin.analysis.ortu') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">WA Ortu Privat</a>
                                        <a href="{{ route('admin.analysis.guru') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">WA Guru</a>
                                        <a href="{{ route('admin.discounts.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Diskon/Promo</a>
                                    </div>
                                </div>
                                <div class="relative" data-submenu="pembayaran">
                                    <span onclick="toggleSubmenu('pembayaran')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                        Pembayaran
                                        <svg class="h-4 w-4 sub-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                    <div class="pl-4 hidden" data-submenu-content="pembayaran">
                                        <a href="{{ route('admin.payments.ortu') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pembayaran Ortu</a>
                                        <a href="{{ route('admin.payments.guru') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pembayaran Guru</a>
                                    </div>
                                </div>
                                <div class="relative" data-submenu="laporan">
                                    <span onclick="toggleSubmenu('laporan')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                        Laporan
                                        <svg class="h-4 w-4 sub-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                    <div class="pl-4 hidden" data-submenu-content="laporan">
                                        <a href="{{ route('admin.class-reports.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Laporan Kelas</a>
                                        <a href="{{ route('admin.history.students') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Riwayat</a>
                                        <a href="{{ route('admin.finance.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Keuangan</a>
                                        <a href="{{ route('admin.export.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export & Backup</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                            function toggleMenuLengkap() {
                                document.getElementById('menu-lengkap-dropdown').classList.toggle('hidden');
                            }
                            function toggleSubmenu(id) {
                                const el = document.querySelector('[data-submenu-content="' + id + '"]');
                                el.classList.toggle('hidden');
                            }
                            document.addEventListener('click', function(e) {
                                const wrapper = document.getElementById('menu-lengkap-wrapper');
                                if (!wrapper.contains(e.target)) {
                                    document.getElementById('menu-lengkap-dropdown').classList.add('hidden');
                                }
                            });
                        </script>
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
                <x-responsive-nav-link :href="route('admin.analysis.ortu-kelas')" :active="request()->routeIs('admin.analysis.ortu-kelas')">
                    {{ __('WA Ortu Kelas') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.analysis.ortu')" :active="request()->routeIs('admin.analysis.ortu')">
                    {{ __('WA Ortu Privat') }}
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
                    {{ __('Laporan Kelas') }}
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
