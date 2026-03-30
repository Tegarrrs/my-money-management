<header class="top-navbar">
    <h1 class="page-title" id="navbar-title">
        Dasboard
        <small id="navbar-subtitle">Selamat datang kembali, Budi 👋</small>
    </h1>
    <div class="dropdown">
        <div class="navbar-date-range dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-calendar3"></i>
            <span id="dateRangeLabel">Juni 2025</span>
            <i class="bi bi-chevron-down" style="font-size:10px;color:var(--color-muted)"></i>
        </div>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#" onclick="setDateRange('Bulan Ini');return false;">Bulan
                    Ini</a></li>
            <li><a class="dropdown-item" href="#" onclick="setDateRange('Bulan Lalu');return false;">Bulan
                    Lalu</a></li>
            <li><a class="dropdown-item" href="#" onclick="setDateRange('3 Bulan Terakhir');return false;">3
                    Bulan Terakhir</a></li>
            <li><a class="dropdown-item" href="#" onclick="setDateRange('Tahun Ini');return false;">Tahun
                    Ini</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="#" onclick="return false;"><i
                        class="bi bi-calendar-range me-1"></i>Rentang Kustom…</a></li>
        </ul>
    </div>
    <div class="navbar-icon-btn"><i class="bi bi-bell"></i><span class="notif-dot"></span></div>
    <div class="dropdown">
        <div class="navbar-avatar dropdown-toggle" data-bs-toggle="dropdown">BR</div>
        <ul class="dropdown-menu dropdown-menu-end">
            <li class="px-3 py-2">
                <div style="font-size:13px;font-weight:800;">Budi Raharjo</div>
                <div style="font-size:12px;color:var(--color-muted)">budi@dompetra.id</div>
            </li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Pengaturan</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item text-danger" href="login.html"><i
                        class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
        </ul>
    </div>
</header>