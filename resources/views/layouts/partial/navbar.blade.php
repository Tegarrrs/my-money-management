<header class="top-navbar">
    {{-- Mobile menu toggle --}}
    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Buka sidebar">
        <i class="bi bi-list"></i>
    </button>

    <h1 class="page-title" id="navbar-title">
        @yield('title', 'Dasbor')
        <small id="navbar-subtitle">@yield('subtitle', 'Selamat datang kembali, ' . explode(' ', auth()->user()->name)[0] . ' 👋')</small>
    </h1>

    <div class="dropdown">
        <div class="navbar-date-range dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-calendar3"></i>
            <span id="dateRangeLabel">{{ now()->translatedFormat('F Y') }}</span>
            <i class="bi bi-chevron-down" style="font-size:10px;color:var(--color-muted)"></i>
        </div>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#" onclick="setDateRange('Bulan Ini');return false;">Bulan Ini</a></li>
            <li><a class="dropdown-item" href="#" onclick="setDateRange('Bulan Lalu');return false;">Bulan Lalu</a></li>
            <li><a class="dropdown-item" href="#" onclick="setDateRange('3 Bulan Terakhir');return false;">3 Bulan Terakhir</a></li>
            <li><a class="dropdown-item" href="#" onclick="setDateRange('Tahun Ini');return false;">Tahun Ini</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" onclick="return false;"><i class="bi bi-calendar-range me-1"></i>Rentang Kustom…</a></li>
        </ul>
    </div>

    <div class="navbar-icon-btn"><i class="bi bi-bell"></i><span class="notif-dot"></span></div>

    <div class="dropdown">
        @php
            $nameParts = explode(' ', auth()->user()->name);
            $initials = isset($nameParts[1])
                ? substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1)
                : substr($nameParts[0], 0, 2);
            $initials = strtoupper($initials);
        @endphp
        <div class="navbar-avatar dropdown-toggle" data-bs-toggle="dropdown">{{ $initials }}</div>
        <ul class="dropdown-menu dropdown-menu-end">
            <li class="px-3 py-2">
                <div style="font-size:13px;font-weight:800;">{{ auth()->user()->name }}</div>
                <div style="font-size:12px;color:var(--color-muted)">{{ auth()->user()->email }}</div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Pengaturan</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                       onclick="event.preventDefault(); this.closest('form').submit();">
                        <i class="bi bi-box-arrow-right me-2"></i>Keluar
                    </a>
                </form>
            </li>
        </ul>
    </div>
</header>