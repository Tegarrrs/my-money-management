<aside class="sidebar" id="sidebar">
    <div class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar">
        <i class="bi bi-layout-sidebar-reverse"></i>
    </div>

    <a href="{{ route('dashboard.index') }}" class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-wallet-fill"></i></div>
        <span class="brand-name">Dom<span>petra</span></span>
    </a>

    <span class="sidebar-section-label">Menu Utama</span>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="{{ route('dashboard.index') }}"
               class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i>
                <span class="nav-label">Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('wallet.index') }}"
               class="nav-link {{ request()->routeIs('wallet*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i>
                <span class="nav-label">Dompet</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('category.index') }}"
               class="nav-link {{ request()->routeIs('category*') ? 'active' : '' }}">
                <i class="bi bi-tag-fill"></i>
                <span class="nav-label">Kategori</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('transaction.index') }}"
               class="nav-link {{ request()->routeIs('transaction*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i>
                <span class="nav-label">Transaksi</span>
            </a>
        </li>
    </ul>

    <span class="sidebar-section-label">Analitik</span>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="{{ route('report.index') }}" class="nav-link {{ request()->routeIs('report*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span class="nav-label">Laporan</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('ocr.upload') }}"
               class="nav-link {{ request()->routeIs('ocr*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-image-fill"></i>
                <span class="nav-label">Impor OCR</span>
            </a>
        </li>
        <li class="nav-item"><a href="#" class="nav-link"><i class="bi bi-bullseye"></i><span class="nav-label">Target Tabungan</span></a></li>
    </ul>

    <span class="sidebar-section-label">Pengaturan</span>
    <ul class="sidebar-nav">
        <li class="nav-item"><a href="#" class="nav-link"><i class="bi bi-gear-fill"></i><span class="nav-label">Pengaturan</span></a></li>
    </ul>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            @php
                $nameParts = explode(' ', auth()->user()->name);
                $initials = isset($nameParts[1])
                    ? substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1)
                    : substr($nameParts[0], 0, 2);
                $initials = strtoupper($initials);
            @endphp
            <div class="avatar">{{ $initials }}</div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-email">{{ auth()->user()->email }}</div>
            </div>
            <i class="bi bi-chevron-expand chevron"></i>
        </div>
    </div>
</aside>

{{-- Mobile overlay backdrop --}}
<div class="sidebar-overlay" id="sidebarOverlay"></div>