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
                            $menuLengkapActive = request()->routeIs('admin.students.*', 'admin.teachers.*', 'admin.parents.*', 'admin.programs.*', 'admin.enrollments.*', 'admin.lesson-offers.*', 'admin.presensi.*', 'admin.notifications.*', 'admin.class-student-sessions.*', 'admin.class-sessions.*', 'admin.analysis.ortu', 'admin.analysis.guru', 'admin.payments.*', 'admin.discounts.*', 'admin.class-reports.*', 'admin.history.*', 'admin.finance.*', 'admin.export.*', 'admin.bank-accounts.*');
                            $dropdownBase = 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
                        @endphp
                        <div id="menu-lengkap-wrapper" class="relative flex items-center">
                            <button onclick="toggleMenuLengkap()" class="relative {{ $dropdownBase }} gap-1 {{ $menuLengkapActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                {{ __('Menu Lengkap') }}
                                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div id="menu-lengkap-dropdown" class="fixed w-56 max-h-[calc(100vh-1rem)] overflow-y-auto overscroll-contain bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50 py-2 hidden" style="top:0;left:0;">
                                <div class="relative" data-submenu="data-master">
                                    <span onclick="toggleSubmenu('data-master')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                        Data Master
                                        <svg class="h-4 w-4 sub-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                    <div class="pl-4 hidden" data-submenu-content="data-master">
                                        <a href="{{ route('admin.parents.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Parent</a>
                                        <a href="{{ route('admin.students.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Murid</a>
                                        <a href="{{ route('admin.teachers.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Guru</a>
                                        <a href="{{ route('admin.programs.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Program</a>
                                        <a href="{{ route('admin.enrollments.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Enrollment</a>
                                        <a href="{{ route('admin.lesson-offers.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tawaran Les</a>
                                        <a href="{{ route('admin.documents.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dokumen</a>
                                        <a href="{{ route('admin.new-students.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pendaftar Murid Baru</a>
                                        <a href="{{ route('admin.teacher-registrants.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pendaftar Guru Baru</a>
                                    </div>
                                </div>
                                <div class="relative" data-submenu="kelas">
                                    <span onclick="toggleSubmenu('kelas')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                        Kelas
                                        <svg class="h-4 w-4 sub-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                    <div class="pl-4 hidden" data-submenu-content="kelas">
                                        <a href="{{ route('admin.class-student-sessions.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Presensi & Jadwal Kelas</a>
                                        <a href="{{ route('admin.class-student-sessions.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tambah Sesi</a>
                                    </div>
                                </div>
                                <div class="relative" data-submenu="presensi">
                                    <span onclick="toggleSubmenu('presensi')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                        Presensi
                                        <svg class="h-4 w-4 sub-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                    <div class="pl-4 hidden" data-submenu-content="presensi">
                                        <a href="{{ route('admin.presensi.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Validasi Presensi Privat</a>
                                    </div>
                                </div>
                                <div class="relative" data-submenu="wa">
                                    <span onclick="toggleSubmenu('wa')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                        WA & Promo
                                        <svg class="h-4 w-4 sub-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                    <div class="pl-4 hidden" data-submenu-content="wa">
                                        <a href="{{ route('admin.analysis.ortu') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Template WA Ortu</a>
                                        <a href="{{ route('admin.analysis.guru') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Template WA Guru</a>
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
                                <a href="{{ route('admin.bank-accounts.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">🏦 Rekening Bimbel</a>
                            </div>
                        </div>
                    @endif
                    @if (auth()->user()?->role?->value === 'guru')
                        @php
                            $guruAktif = request()->routeIs('guru.presensi.*', 'guru.history.*');
                            $guruFinance = request()->routeIs('guru.salary-projection.*');
                            $guruOffer = request()->routeIs('guru.tawaran.*');
                            $dropdownBase = $dropdownBase ?? 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
                        @endphp
                        <div id="guru-menu-wrapper" class="relative flex items-center">
                            <button onclick="toggleFixedDropdown('guru-menu-wrapper', 'guru-menu-dropdown', 224)" class="relative {{ $dropdownBase }} gap-1 {{ ($guruAktif || $guruFinance || $guruOffer) ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                {{ __('Menu Guru') }}
                                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div id="guru-menu-dropdown" class="fixed w-56 max-h-[calc(100vh-1rem)] overflow-y-auto overscroll-contain bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50 py-2 hidden" style="top:0;left:0;">
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
                                <x-dropdown-link :href="route('guru.documents.index')">
                                    {{ __('Dokumen') }}
                                </x-dropdown-link>
                            </div>
                        </div>
                    @endif
                    @if (auth()->user()?->role?->value === 'parent')
                        @php
                            $muridActive = request()->routeIs('parent.history.*', 'parent.billing.*');
                            $dropdownBase = $dropdownBase ?? 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
                        @endphp
                        <div id="murid-menu-wrapper" class="relative flex items-center">
                            <button onclick="toggleFixedDropdown('murid-menu-wrapper', 'murid-menu-dropdown', 224)" class="relative {{ $dropdownBase }} gap-1 {{ $muridActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                {{ __('Menu Murid') }}
                                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div id="murid-menu-dropdown" class="fixed w-56 max-h-[calc(100vh-1rem)] overflow-y-auto overscroll-contain bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50 py-2 hidden" style="top:0;left:0;">
                                <x-dropdown-link :href="route('parent.history.index')">
                                    {{ __('Presensi Les') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('parent.billing.index')">
                                    {{ __('Tagihan') }}
                                </x-dropdown-link>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <script>
                const fixedDropdowns = [
                    { wrapperId: 'menu-lengkap-wrapper', dropdownId: 'menu-lengkap-dropdown', width: 224 },
                    { wrapperId: 'guru-menu-wrapper', dropdownId: 'guru-menu-dropdown', width: 224 },
                    { wrapperId: 'murid-menu-wrapper', dropdownId: 'murid-menu-dropdown', width: 224 },
                ];

                function positionFixedDropdown(wrapperId, dropdownId, dropdownWidth) {
                    const wrapper = document.getElementById(wrapperId);
                    const dropdown = document.getElementById(dropdownId);
                    const trigger = wrapper ? wrapper.querySelector('button') : null;

                    if (!trigger || !dropdown) {
                        return;
                    }

                    const rect = trigger.getBoundingClientRect();
                    const viewportPadding = 8;
                    const desiredLeft = rect.left;
                    const maxLeft = window.innerWidth - dropdownWidth - viewportPadding;
                    const left = Math.max(viewportPadding, Math.min(desiredLeft, maxLeft));
                    const top = rect.bottom + 8;
                    const maxHeight = Math.max(160, window.innerHeight - top - viewportPadding);

                    dropdown.style.left = `${left}px`;
                    dropdown.style.top = `${top}px`;
                    dropdown.style.maxHeight = `${maxHeight}px`;
                }

                function toggleFixedDropdown(wrapperId, dropdownId, dropdownWidth) {
                    const dropdown = document.getElementById(dropdownId);

                    if (!dropdown) {
                        return;
                    }

                    if (dropdown.classList.contains('hidden')) {
                        positionFixedDropdown(wrapperId, dropdownId, dropdownWidth);
                        dropdown.classList.remove('hidden');
                    } else {
                        dropdown.classList.add('hidden');
                    }
                }

                function toggleMenuLengkap() {
                    toggleFixedDropdown('menu-lengkap-wrapper', 'menu-lengkap-dropdown', 224);
                }

                function toggleSubmenu(id) {
                    const el = document.querySelector('[data-submenu-content="' + id + '"]');
                    if (el) {
                        el.classList.toggle('hidden');
                    }
                }

                window.addEventListener('resize', function() {
                    fixedDropdowns.forEach(function(config) {
                        const dropdown = document.getElementById(config.dropdownId);
                        if (dropdown && !dropdown.classList.contains('hidden')) {
                            positionFixedDropdown(config.wrapperId, config.dropdownId, config.width);
                        }
                    });
                });

                document.addEventListener('click', function(e) {
                    fixedDropdowns.forEach(function(config) {
                        const wrapper = document.getElementById(config.wrapperId);
                        const dropdown = document.getElementById(config.dropdownId);
                        if (wrapper && dropdown && !wrapper.contains(e.target)) {
                            dropdown.classList.add('hidden');
                        }
                    });
                });
            </script>

            <!-- Settings Dropdown -->
            @php
                $pendingNotificationsCount = 0;
                if (auth()->user()?->role?->value === 'admin') {
                    $pendingNotificationsCount = \App\Models\MonthlyAttendance::query()
                        ->where('parent_review_status', 'pending')
                        ->count();
                }
            @endphp

            <div class="hidden sm:flex sm:items-center gap-2 sm:gap-3">
                @if (auth()->user()?->role?->value === 'admin')
                    <a href="{{ route('admin.notifications.index') }}" class="relative inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 bg-white text-gray-600 hover:text-gray-900 hover:border-gray-300 hover:bg-gray-50 transition-colors" title="Notifikasi Presensi">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if ($pendingNotificationsCount > 0)
                            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-rose-600 text-white text-[10px] font-semibold leading-none ring-2 ring-white">
                                {{ $pendingNotificationsCount > 9 ? '9+' : $pendingNotificationsCount }}
                            </span>
                        @endif
                    </a>
                @endif

                <div class="hidden sm:flex sm:items-center sm:ms-2">
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
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                @if (auth()->user()?->role?->value === 'admin')
                    <a href="{{ route('admin.notifications.index') }}" class="relative inline-flex items-center justify-center w-10 h-10 mr-1 rounded-full border border-gray-200 bg-white text-gray-600 hover:text-gray-900 hover:border-gray-300 hover:bg-gray-50 transition-colors" title="Notifikasi Presensi">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if ($pendingNotificationsCount > 0)
                            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-rose-600 text-white text-[10px] font-semibold leading-none ring-2 ring-white">
                                {{ $pendingNotificationsCount > 9 ? '9+' : $pendingNotificationsCount }}
                            </span>
                        @endif
                    </a>
                @endif
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
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden" x-cloak>
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if (auth()->user()?->role?->value === 'admin')
                {{-- Menu Lengkap (mirrors desktop dropdown: header + collapsible submenus) --}}
                <div x-data="{ menuLengkap: {{ $menuLengkapActive ? 'true' : 'false' }} }">
                    <button type="button" @click="menuLengkap = ! menuLengkap"
                            class="w-full flex items-center justify-between ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium focus:outline-none transition duration-150 ease-in-out {{ $menuLengkapActive ? 'border-indigo-400 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300' }}">
                        <span>{{ __('Menu Lengkap') }}</span>
                        <svg class="h-4 w-4 shrink-0 transition-transform" :class="{ 'rotate-180': menuLengkap }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="menuLengkap" x-collapse class="bg-gray-50">
                        {{-- Data Master --}}
                        <div x-data="{ sub: {{ request()->routeIs('admin.students.*', 'admin.teachers.*', 'admin.parents.*', 'admin.programs.*', 'admin.enrollments.*', 'admin.lesson-offers.*', 'admin.documents.*', 'admin.new-students.*', 'admin.teacher-registrants.*', 'admin.presensi.*') ? 'true' : 'false' }} }">
                            <button type="button" @click="sub = ! sub" class="w-full flex items-center justify-between ps-6 pe-4 py-2 text-sm font-semibold text-gray-500 uppercase tracking-wide">
                                <span>{{ __('Data Master') }}</span>
                                <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="{ 'rotate-180': sub }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="sub" x-collapse>
                                <x-responsive-nav-link :href="route('admin.parents.index')" :active="request()->routeIs('admin.parents.*')" class="ps-8">
                                    {{ __('Parent') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')" class="ps-8">
                                    {{ __('Murid') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.teachers.index')" :active="request()->routeIs('admin.teachers.*')" class="ps-8">
                                    {{ __('Guru') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.programs.index')" :active="request()->routeIs('admin.programs.*')" class="ps-8">
                                    {{ __('Program') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.enrollments.index')" :active="request()->routeIs('admin.enrollments.*')" class="ps-8">
                                    {{ __('Enrollment') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.lesson-offers.index')" :active="request()->routeIs('admin.lesson-offers.*')" class="ps-8">
                                    {{ __('Tawaran Les') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.documents.index')" :active="request()->routeIs('admin.documents.*')" class="ps-8">
                                    {{ __('Dokumen') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.new-students.index')" :active="request()->routeIs('admin.new-students.*')" class="ps-8">
                                    {{ __('Pendaftar Murid Baru') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.teacher-registrants.index')" :active="request()->routeIs('admin.teacher-registrants.*')" class="ps-8">
                                    {{ __('Pendaftar Guru Baru') }}
                                </x-responsive-nav-link>
                            </div>
                        </div>

                        {{-- Presensi --}}
                        <div x-data="{ sub: {{ request()->routeIs('admin.presensi.*') ? 'true' : 'false' }} }">
                            <button type="button" @click="sub = ! sub" class="w-full flex items-center justify-between ps-6 pe-4 py-2 text-sm font-semibold text-gray-500 uppercase tracking-wide">
                                <span>{{ __('Presensi') }}</span>
                                <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="{ 'rotate-180': sub }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="sub" x-collapse>
                                <x-responsive-nav-link :href="route('admin.presensi.index')" :active="request()->routeIs('admin.presensi.*')" class="ps-8">
                                    {{ __('Validasi Presensi Privat') }}
                                </x-responsive-nav-link>
                            </div>
                        </div>

                        {{-- Kelas --}}
                        <div x-data="{ sub: {{ request()->routeIs('admin.class-student-sessions.*') ? 'true' : 'false' }} }">
                            <button type="button" @click="sub = ! sub" class="w-full flex items-center justify-between ps-6 pe-4 py-2 text-sm font-semibold text-gray-500 uppercase tracking-wide">
                                <span>{{ __('Kelas') }}</span>
                                <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="{ 'rotate-180': sub }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="sub" x-collapse>
                                <x-responsive-nav-link :href="route('admin.class-student-sessions.index')" :active="request()->routeIs('admin.class-student-sessions.*')" class="ps-8">
                                    {{ __('Presensi & Jadwal Kelas') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.class-student-sessions.create')" :active="request()->routeIs('admin.class-student-sessions.create')" class="ps-8">
                                    {{ __('Tambah Sesi') }}
                                </x-responsive-nav-link>
                            </div>
                        </div>

                        {{-- WA --}}
                        <div x-data="{ sub: {{ request()->routeIs('admin.analysis.ortu', 'admin.analysis.guru', 'admin.discounts.*') ? 'true' : 'false' }} }">
                            <button type="button" @click="sub = ! sub" class="w-full flex items-center justify-between ps-6 pe-4 py-2 text-sm font-semibold text-gray-500 uppercase tracking-wide">
                                <span>{{ __('WA & Promo') }}</span>
                                <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="{ 'rotate-180': sub }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="sub" x-collapse>
                                <x-responsive-nav-link :href="route('admin.analysis.ortu')" :active="request()->routeIs('admin.analysis.ortu')" class="ps-8">
                                    {{ __('Template WA Ortu') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.analysis.guru')" :active="request()->routeIs('admin.analysis.guru')" class="ps-8">
                                    {{ __('Template WA Guru') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.discounts.index')" :active="request()->routeIs('admin.discounts.*')" class="ps-8">
                                    {{ __('Diskon/Promo') }}
                                </x-responsive-nav-link>
                            </div>
                        </div>

                        {{-- Pembayaran --}}
                        <div x-data="{ sub: {{ request()->routeIs('admin.payments.*') ? 'true' : 'false' }} }">
                            <button type="button" @click="sub = ! sub" class="w-full flex items-center justify-between ps-6 pe-4 py-2 text-sm font-semibold text-gray-500 uppercase tracking-wide">
                                <span>{{ __('Pembayaran') }}</span>
                                <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="{ 'rotate-180': sub }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="sub" x-collapse>
                                <x-responsive-nav-link :href="route('admin.payments.ortu')" :active="request()->routeIs('admin.payments.ortu')" class="ps-8">
                                    {{ __('Pembayaran Ortu') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.payments.guru')" :active="request()->routeIs('admin.payments.guru')" class="ps-8">
                                    {{ __('Pembayaran Guru') }}
                                </x-responsive-nav-link>
                            </div>
                        </div>

                        {{-- Laporan --}}
                        <div x-data="{ sub: {{ request()->routeIs('admin.class-reports.*', 'admin.history.*', 'admin.finance.*', 'admin.export.*') ? 'true' : 'false' }} }">
                            <button type="button" @click="sub = ! sub" class="w-full flex items-center justify-between ps-6 pe-4 py-2 text-sm font-semibold text-gray-500 uppercase tracking-wide">
                                <span>{{ __('Laporan') }}</span>
                                <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="{ 'rotate-180': sub }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="sub" x-collapse>
                                <x-responsive-nav-link :href="route('admin.class-reports.index')" :active="request()->routeIs('admin.class-reports.*')" class="ps-8">
                                    {{ __('Laporan Kelas') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.history.students')" :active="request()->routeIs('admin.history.*')" class="ps-8">
                                    {{ __('Riwayat') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.finance.index')" :active="request()->routeIs('admin.finance.*')" class="ps-8">
                                    {{ __('Keuangan') }}
                                </x-responsive-nav-link>
                                <x-responsive-nav-link :href="route('admin.export.index')" :active="request()->routeIs('admin.export.*')" class="ps-8">
                                    {{ __('Export & Backup') }}
                                </x-responsive-nav-link>
                            </div>
                        </div>
                    </div>
                </div>

                <x-responsive-nav-link :href="route('admin.bank-accounts.index')" :active="request()->routeIs('admin.bank-accounts.*')">
                    {{ __('Rekening Bimbel') }}
                </x-responsive-nav-link>
            @endif
            @if (auth()->user()?->role?->value === 'guru')
                {{-- Menu Guru (mirrors desktop dropdown) --}}
                <div x-data="{ menuGuru: {{ ($guruAktif || $guruFinance || $guruOffer) ? 'true' : 'false' }} }">
                    <button type="button" @click="menuGuru = ! menuGuru"
                            class="w-full flex items-center justify-between ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium focus:outline-none transition duration-150 ease-in-out {{ ($guruAktif || $guruFinance || $guruOffer) ? 'border-indigo-400 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300' }}">
                        <span>{{ __('Menu Guru') }}</span>
                        <svg class="h-4 w-4 shrink-0 transition-transform" :class="{ 'rotate-180': menuGuru }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="menuGuru" x-collapse class="bg-gray-50">
                        <x-responsive-nav-link :href="route('guru.presensi.index')" :active="request()->routeIs('guru.presensi.*')" class="ps-8">
                            {{ __('Presensi') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('guru.history.index')" :active="request()->routeIs('guru.history.*')" class="ps-8">
                            {{ __('Riwayat Les') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('guru.salary-projection.index')" :active="request()->routeIs('guru.salary-projection.*')" class="ps-8">
                            {{ __('Proyeksi Gaji') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('guru.tawaran.index')" :active="request()->routeIs('guru.tawaran.*')" class="ps-8">
                            {{ __('Tawaran Les') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('guru.documents.index')" :active="request()->routeIs('guru.documents.*')" class="ps-8">
                            {{ __('Dokumen') }}
                        </x-responsive-nav-link>
                    </div>
                </div>
            @endif
            @if (auth()->user()?->role?->value === 'parent')
                {{-- Menu Murid (mirrors desktop dropdown) --}}
                <div x-data="{ menuMurid: {{ $muridActive ? 'true' : 'false' }} }">
                    <button type="button" @click="menuMurid = ! menuMurid"
                            class="w-full flex items-center justify-between ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium focus:outline-none transition duration-150 ease-in-out {{ $muridActive ? 'border-indigo-400 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300' }}">
                        <span>{{ __('Menu Murid') }}</span>
                        <svg class="h-4 w-4 shrink-0 transition-transform" :class="{ 'rotate-180': menuMurid }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="menuMurid" x-collapse class="bg-gray-50">
                        <x-responsive-nav-link :href="route('parent.history.index')" :active="request()->routeIs('parent.history.*')" class="ps-8">
                            {{ __('Presensi Les') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('parent.billing.index')" :active="request()->routeIs('parent.billing.*')" class="ps-8">
                            {{ __('Tagihan') }}
                        </x-responsive-nav-link>
                    </div>
                </div>
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