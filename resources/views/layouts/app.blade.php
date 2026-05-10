<!DOCTYPE html>
<html lang="en" class="layout-menu-fixed"
    data-assets-path="/assets/"
    data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/dark-mode.css') }}" />

    @stack('styles')
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
</head>
<body>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <!-- Sidebar -->
        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
            <div class="app-brand demo">
                <a href="{{ route('dashboard') }}" class="app-brand-link">
                    <span class="app-brand-text demo menu-text fw-bold ms-2">
                        {{ config('app.name') }}
                    </span>
                </a>
                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                    <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
                </a>
            </div>

            <div class="menu-divider mt-0"></div>
            <div class="menu-inner-shadow"></div>

            <ul class="menu-inner py-1">

                <!-- Dashboard -->
                <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-home-smile"></i>
                        <div class="text-truncate">Dashboard</div>
                    </a>
                </li>

                <!-- Pengurusan -->
                <li class="menu-item {{ request()->routeIs('pegawai*') || request()->routeIs('pegawai-kontrak*') || request()->routeIs('waran*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-briefcase"></i>
                        <div class="text-truncate">Pengurusan</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ request()->routeIs('pegawai*') ? 'active' : '' }}">
                            <a href="{{ route('pegawai.index') }}" class="menu-link">
                                <div class="text-truncate">Pegawai</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('pegawai-kontrak*') ? 'active' : '' }}">
                            <a href="{{ route('pegawai-kontrak.index') }}" class="menu-link">
                                <div class="text-truncate">Pegawai Kontrak</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('waran*') ? 'active' : '' }}">
                            <a href="{{ route('waran.index') }}" class="menu-link">
                                <div class="text-truncate">Waran</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Organisasi -->
                <li class="menu-item {{ request()->routeIs('ptj*') || request()->routeIs('bahagian*') || request()->routeIs('unit*') || request()->routeIs('subunit*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-building"></i>
                        <div class="text-truncate">Organisasi</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ request()->routeIs('ptj*') ? 'active' : '' }}">
                            <a href="{{ route('ptj.index') }}" class="menu-link">
                                <div class="text-truncate">PTJ</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('bahagian*') ? 'active' : '' }}">
                            <a href="{{ route('bahagian.index') }}" class="menu-link">
                                <div class="text-truncate">Bahagian</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('unit*') ? 'active' : '' }}">
                            <a href="{{ route('unit.index') }}" class="menu-link">
                                <div class="text-truncate">Unit</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('subunit*') ? 'active' : '' }}">
                            <a href="{{ route('subunit.index') }}" class="menu-link">
                                <div class="text-truncate">Subunit</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Rujukan -->
                <li class="menu-item {{ request()->routeIs('program*') || request()->routeIs('aktiviti*') || request()->routeIs('butiran*') || request()->routeIs('gred*') || request()->routeIs('jawatan*') || request()->routeIs('opsyen-pencen*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-book"></i>
                        <div class="text-truncate">Rujukan</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ request()->routeIs('program*') ? 'active' : '' }}">
                            <a href="{{ route('program.index') }}" class="menu-link">
                                <div class="text-truncate">Program</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('aktiviti*') ? 'active' : '' }}">
                            <a href="{{ route('aktiviti.index') }}" class="menu-link">
                                <div class="text-truncate">Aktiviti</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('butiran*') ? 'active' : '' }}">
                            <a href="{{ route('butiran.index') }}" class="menu-link">
                                <div class="text-truncate">Butiran</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('gred*') ? 'active' : '' }}">
                            <a href="{{ route('gred.index') }}" class="menu-link">
                                <div class="text-truncate">Gred</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('jawatan*') ? 'active' : '' }}">
                            <a href="{{ route('jawatan.index') }}" class="menu-link">
                                <div class="text-truncate">Jawatan</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('opsyen-pencen*') ? 'active' : '' }}">
                            <a href="{{ route('opsyen-pencen.index') }}" class="menu-link">
                                <div class="text-truncate">Opsyen Pencen</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Lokasi -->
                <li class="menu-item {{ request()->routeIs('parlimen*') || request()->routeIs('dun*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-map"></i>
                        <div class="text-truncate">Lokasi</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ request()->routeIs('parlimen*') ? 'active' : '' }}">
                            <a href="{{ route('parlimen.index') }}" class="menu-link">
                                <div class="text-truncate">Parlimen</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('dun*') ? 'active' : '' }}">
                            <a href="{{ route('dun.index') }}" class="menu-link">
                                <div class="text-truncate">DUN</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Sistem -->
                <li class="menu-item {{ request()->routeIs('pengguna*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-cog"></i>
                        <div class="text-truncate">Sistem</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ request()->routeIs('pengguna*') ? 'active' : '' }}">
                            <a href="{{ route('pengguna.index') }}" class="menu-link">
                                <div class="text-truncate">Pengguna</div>
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
        </aside>
        <!-- /Sidebar -->

        <div class="layout-page">
            <!-- Navbar -->
            <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme">
                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
                    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                        <i class="bx bx-menu bx-md"></i>
                    </a>
                </div>

                <div class="navbar-nav-right d-flex align-items-center ms-auto">

                    <!-- Dark/Light Mode Toggle -->
                    <div class="nav-item me-2">
                        <a class="nav-link btn btn-text-secondary rounded-pill btn-icon" href="javascript:void(0);" id="themeToggle">
                            <i class="bx bx-moon bx-sm"></i>
                        </a>
                    </div>

                    <!-- User dropdown -->
                    <div class="navbar-nav align-items-center">
                        <div class="nav-item navbar-dropdown dropdown-user dropdown">
                            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                                <div class="avatar avatar-online">
                                    <span class="avatar-initial rounded-circle bg-label-primary">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </span>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <span class="fw-medium d-block">{{ auth()->user()->name }}</span>
                                                <small class="text-muted">{{ auth()->user()->email }}</small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li><div class="dropdown-divider"></div></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <a class="dropdown-item" href="#"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                            <i class="bx bx-power-off me-2"></i>
                                            <span>Log Keluar</span>
                                        </a>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </nav>
            <!-- /Navbar -->

            <!-- Main Content -->
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible mb-4" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')

                </div>
                <div class="content-backdrop fade"></div>
            </div>
        </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>
</div>

<!-- Core JS -->
<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>

<!-- Sneat Dark/Light Mode -->
<script>
    const themeToggle = document.getElementById('themeToggle');
    const htmlEl = document.documentElement;
    const icon = themeToggle.querySelector('i');

    // Load saved theme using Sneat's class system
    const savedTheme = localStorage.getItem('sneatTheme') || 'light';
    if (savedTheme === 'dark') {
        htmlEl.classList.remove('light-style');
        htmlEl.classList.add('dark-style');
        icon.className = 'bx bx-sun bx-sm';
    } else {
        htmlEl.classList.remove('dark-style');
        htmlEl.classList.add('light-style');
        icon.className = 'bx bx-moon bx-sm';
    }

    themeToggle.addEventListener('click', function () {
        const isDark = htmlEl.classList.contains('dark-style');
        if (isDark) {
            htmlEl.classList.remove('dark-style');
            htmlEl.classList.add('light-style');
            localStorage.setItem('sneatTheme', 'light');
            icon.className = 'bx bx-moon bx-sm';
        } else {
            htmlEl.classList.remove('light-style');
            htmlEl.classList.add('dark-style');
            localStorage.setItem('sneatTheme', 'dark');
            icon.className = 'bx bx-sun bx-sm';
        }
    });
</script>

@stack('scripts')
</body>
</html>
