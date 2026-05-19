<header class="top-navbar">
    {{-- Mobile menu toggle --}}
    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Buka sidebar">
        <i class="bi bi-list"></i>
    </button>

    <h1 class="page-title" id="navbar-title">
        @yield('title', 'Dashboard')
        <small id="navbar-subtitle">@yield('subtitle', 'Selamat datang kembali, ' . explode(' ', auth()->user()->name)[0] . ' 👋')</small>
    </h1>



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