@extends('layouts.main')

@section('title', 'Dasbor')
@section('subtitle', 'Selamat datang kembali, ' . explode(' ', auth()->user()->name)[0] . ' 👋')

@push('css')
<link rel="stylesheet" href="{{ asset('assets/dashboard.css') }}">
@endpush

@section('content')

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($onboardingProgress < 3)
        <section class="onboarding-checklist-card">
            <div class="onboarding-checklist-copy">
                <span class="onboarding-checklist-kicker">Mulai dengan benar</span>
                <h2>Lengkapi pengaturan awal</h2>
                <p>{{ $onboardingProgress }} dari 3 langkah selesai. Data yang lengkap membuat laporan lebih akurat.</p>
                <div class="onboarding-mini-progress"><span style="width: {{ ($onboardingProgress / 3) * 100 }}%"></span></div>
            </div>
            <div class="onboarding-checklist-items">
                <a href="{{ route('wallet.index') }}" class="{{ $onboardingChecklist['wallet'] ? 'done' : '' }}">
                    <i class="bi bi-{{ $onboardingChecklist['wallet'] ? 'check-circle-fill' : 'circle' }}"></i> Buat dompet
                </a>
                <a href="{{ route('category.index') }}" class="{{ $onboardingChecklist['categories'] ? 'done' : '' }}">
                    <i class="bi bi-{{ $onboardingChecklist['categories'] ? 'check-circle-fill' : 'circle' }}"></i> Siapkan kategori
                </a>
                <a href="{{ route('transaction.index') }}" class="{{ $onboardingChecklist['transaction'] ? 'done' : '' }}">
                    <i class="bi bi-{{ $onboardingChecklist['transaction'] ? 'check-circle-fill' : 'circle' }}"></i> Catat transaksi
                </a>
            </div>
            <a href="{{ route('onboarding.show') }}" class="btn-rp btn-rp-primary">Lanjutkan setup <i class="bi bi-arrow-right"></i></a>
        </section>
    @endif

    @if($uncategorizedCount > 0)
        <a class="uncategorized-reminder" href="{{ route('transaction.index', ['category_id' => 'uncategorized']) }}">
            <span class="uncategorized-reminder-icon"><i class="bi bi-tags"></i></span>
            <span><strong>{{ $uncategorizedCount }} transaksi belum memiliki kategori</strong><small>Lengkapi kategorinya agar laporan dan insight AI lebih akurat.</small></span>
            <i class="bi bi-arrow-right"></i>
        </a>
    @endif

    @php
        $monthName          = \Carbon\Carbon::now()->translatedFormat('F Y');
        $maxCatAmount       = $topCategories->max('total_amount') ?: 1;
        $totalCatExpense    = $topCategories->sum('total_amount') ?: 1;
        $maxWalletBalance   = $wallets->max(fn($w) => abs((float)$w->balance)) ?: 1;

        // Health grade
        $healthGrade = match(true) {
            $healthScore >= 80 => ['label' => 'Sangat Baik',      'color' => '#15803d'],
            $healthScore >= 60 => ['label' => 'Baik',             'color' => '#ca8a04'],
            $healthScore >= 40 => ['label' => 'Cukup',            'color' => '#ea580c'],
            default            => ['label' => 'Perlu Perhatian',  'color' => '#b91c1c'],
        };

        // SVG ring math  (r = 33, circumference ≈ 207.3)
        $ringR   = 33;
        $ringC   = 2 * M_PI * $ringR;
        $ringDash = ($healthScore / 100) * $ringC;
    @endphp

    {{-- ═══════════════════════════════════════════════
         1. KPI CARDS
    ════════════════════════════════════════════════ --}}
    <div class="kpi-grid">

        {{-- Current Balance --}}
        @php $balDir = $totalBalance >= 0 ? 'up' : 'down'; @endphp
        <div class="kpi-card kpi-balance">
            <div class="kpi-header">
                <div class="kpi-icon"><i class="bi bi-wallet2"></i></div>
                <span class="kpi-trend {{ $balDir }}">
                    <i class="bi bi-{{ $balDir === 'up' ? 'arrow-up' : 'arrow-down' }}-short"></i>
                    {{ $wallets->count() }} dompet
                </span>
            </div>
            <div class="kpi-label">Total Saldo</div>
            <div class="kpi-value">Rp {{ number_format($totalBalance, 0, ',', '.') }}</div>
            <div class="kpi-meta">Aset bersih dari seluruh dompet aktif</div>
        </div>

        {{-- Monthly Income --}}
        @php
            $incDir = $incomeChange >= 0 ? 'up' : 'down';
            $incAbs = abs($incomeChange);
        @endphp
        <div class="kpi-card kpi-income">
            <div class="kpi-header">
                <div class="kpi-icon"><i class="bi bi-arrow-down-left-circle-fill"></i></div>
                <span class="kpi-trend {{ $incDir }}">
                    <i class="bi bi-arrow-{{ $incDir }}-short"></i>
                    {{ $incAbs }}%
                </span>
            </div>
            <div class="kpi-label">Pemasukan Bulan Ini</div>
            <div class="kpi-value">Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</div>
            <div class="kpi-meta">
                Bln lalu: Rp {{ number_format($lastMonthIncome, 0, ',', '.') }}
                &nbsp;·&nbsp; {{ $monthName }}
            </div>
        </div>

        {{-- Monthly Expense --}}
        @php
            $expDir = $expenseChange > 0 ? 'down' : 'up'; // More expense = bad = red
            $expAbs = abs($expenseChange);
        @endphp
        <div class="kpi-card kpi-expense">
            <div class="kpi-header">
                <div class="kpi-icon"><i class="bi bi-arrow-up-right-circle-fill"></i></div>
                <span class="kpi-trend {{ $expDir }}">
                    <i class="bi bi-arrow-{{ $expenseChange > 0 ? 'up' : 'down' }}-short"></i>
                    {{ $expAbs }}%
                </span>
            </div>
            <div class="kpi-label">Pengeluaran Bulan Ini</div>
            <div class="kpi-value">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</div>
            <div class="kpi-meta">
                Bln lalu: Rp {{ number_format($lastMonthExpense, 0, ',', '.') }}
                &nbsp;·&nbsp; {{ $budgetUsage }}% dari pemasukan
            </div>
        </div>

        {{-- Saving Rate --}}
        @php
            $savDir = $savingRateChange >= 0 ? 'up' : 'down';
            $savAbs = abs($savingRateChange);
        @endphp
        <div class="kpi-card kpi-saving">
            <div class="kpi-header">
                <div class="kpi-icon"><i class="bi bi-piggy-bank-fill"></i></div>
                <span class="kpi-trend {{ $savDir }}">
                    <i class="bi bi-arrow-{{ $savDir }}-short"></i>
                    {{ $savAbs }}%
                </span>
            </div>
            <div class="kpi-label">Tingkat Tabungan</div>
            <div class="kpi-value">{{ $savingRate }}%</div>
            <div class="kpi-meta">
                Target ideal ≥ 20%
                &nbsp;·&nbsp; Bln lalu {{ $lastMonthSavingRate }}%
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         2. FINANCIAL INSIGHTS
    ════════════════════════════════════════════════ --}}
    <div class="insight-grid">

        {{-- Expense trend --}}
        @php $expUp = $expenseChange > 0; @endphp
        <div class="insight-chip">
            <div class="insight-chip-icon"
                 style="background:{{ $expUp ? '#fff1f2' : '#f0fdf4' }};color:{{ $expUp ? '#b91c1c' : '#15803d' }}">
                <i class="bi bi-{{ $expUp ? 'graph-up-arrow' : 'graph-down-arrow' }}"></i>
            </div>
            <div class="insight-chip-body">
                <div class="insight-chip-label">Tren Pengeluaran</div>
                <div class="insight-chip-value" style="color:{{ $expUp ? '#b91c1c' : '#15803d' }}">
                    {{ $expUp ? '+' : '' }}{{ $expenseChange }}% vs bln lalu
                </div>
                <div class="insight-chip-sub">
                    {{ $expUp ? 'Pengeluaran naik — perhatikan!' : 'Pengeluaran turun — bagus!' }}
                </div>
            </div>
        </div>

        {{-- Largest spending category --}}
        @php
            $catColor = $largestCategory?->category?->color ?? '#ca8a04';
            $catIcon  = $largestCategory?->category?->icon  ?? 'bi-tag-fill';
        @endphp
        <div class="insight-chip">
            <div class="insight-chip-icon"
                 style="background:{{ $catColor }}22;color:{{ $catColor }}">
                <i class="bi {{ $catIcon }}"></i>
            </div>
            <div class="insight-chip-body">
                <div class="insight-chip-label">Pengeluaran Terbesar</div>
                <div class="insight-chip-value">{{ $largestCategory?->category?->name ?? '—' }}</div>
                <div class="insight-chip-sub">
                    @if($largestCategory)
                        Rp {{ number_format($largestCategory->total_amount, 0, ',', '.') }} bln ini
                    @else
                        Belum ada data bulan ini
                    @endif
                </div>
            </div>
        </div>

        {{-- Wallet health --}}
        <div class="insight-chip">
            <div class="insight-chip-icon"
                 style="background:{{ $negativeWallet ? '#fff1f2' : '#f0fdf4' }};color:{{ $negativeWallet ? '#b91c1c' : '#15803d' }}">
                <i class="bi bi-{{ $negativeWallet ? 'exclamation-triangle-fill' : 'shield-check-fill' }}"></i>
            </div>
            <div class="insight-chip-body">
                <div class="insight-chip-label">Status Dompet</div>
                <div class="insight-chip-value"
                     style="color:{{ $negativeWallet ? '#b91c1c' : '#15803d' }}">
                    {{ $negativeWallet ? $negativeWallet->name : 'Semua dompet aman' }}
                </div>
                <div class="insight-chip-sub">
                    {{ $negativeWallet ? 'Saldo negatif terdeteksi' : 'Tidak ada dompet minus' }}
                </div>
            </div>
        </div>

        {{-- Budget usage --}}
        @php
            $budCrit = $budgetUsage >= 90;
            $budWarn = $budgetUsage >= 70;
            $budColor = $budCrit ? '#b91c1c' : ($budWarn ? '#ca8a04' : '#15803d');
        @endphp
        <div class="insight-chip">
            <div class="insight-chip-icon"
                 style="background:{{ $budCrit ? '#fff1f2' : ($budWarn ? '#fef3c7' : '#f0fdf4') }};color:{{ $budColor }}">
                <i class="bi bi-pie-chart-fill"></i>
            </div>
            <div class="insight-chip-body">
                <div class="insight-chip-label">Penggunaan Anggaran</div>
                <div class="insight-chip-value" style="color:{{ $budColor }}">
                    {{ $budgetUsage }}% dari pemasukan
                </div>
                <div class="insight-chip-sub">
                    @if($budCrit) Anggaran hampir habis!
                    @elseif($budWarn) Perhatikan pengeluaranmu
                    @else Anggaran masih aman
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         3. CHARTS — Cashflow Trend + Expense Distribution
    ════════════════════════════════════════════════ --}}
    <div class="chart-grid">

        {{-- Cashflow Trend --}}
        <div class="chart-panel">
            <div class="chart-panel-header">
                <div>
                    <h3 class="chart-panel-title">Tren Cashflow</h3>
                    <div class="chart-panel-sub">6 bulan terakhir</div>
                </div>
                <div class="chart-panel-legend">
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:#15803d;"></div>
                        Pemasukan
                    </div>
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot" style="background:#b91c1c;"></div>
                        Pengeluaran
                    </div>
                </div>
            </div>
            <div class="chart-panel-body">
                <div id="chart-cashflow" style="height:192px;"></div>
            </div>
        </div>

        {{-- Expense Distribution --}}
        <div class="chart-panel">
            <div class="chart-panel-header">
                <div>
                    <h3 class="chart-panel-title">Distribusi Pengeluaran</h3>
                    <div class="chart-panel-sub">{{ $monthName }}</div>
                </div>
            </div>
            <div class="chart-panel-body">
                @if(count($donutValues) > 0)
                    <div id="chart-donut" style="height:192px;"></div>
                @else
                    <div class="chart-empty" style="height:192px;">
                        <i class="bi bi-pie-chart"></i>
                        <span>Belum ada pengeluaran bulan ini</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         4. FINANCIAL HEALTH + QUICK ACTIONS
    ════════════════════════════════════════════════ --}}
    <div class="mid-grid">

        {{-- Financial Health --}}
        <div class="health-panel">
            <div class="health-panel-header">
                <h3>Kesehatan Finansial</h3>
                <span style="font-size:11px;color:#9ca3af;">Skor bulan ini</span>
            </div>
            <div class="health-panel-body">

                {{-- Score ring --}}
                <div class="health-score-wrap">
                    <div class="health-score-ring">
                        <svg viewBox="0 0 76 76">
                            <circle class="ring-bg"   cx="38" cy="38" r="{{ $ringR }}" />
                            <circle class="ring-fill"
                                cx="38" cy="38" r="{{ $ringR }}"
                                stroke="{{ $healthGrade['color'] }}"
                                stroke-dasharray="{{ round($ringDash, 2) }} {{ round($ringC, 2) }}"
                            />
                        </svg>
                        <div class="health-score-label">{{ $healthScore }}<span>/100</span></div>
                    </div>
                    <div>
                        <div class="health-grade" style="color:{{ $healthGrade['color'] }}">
                            {{ $healthGrade['label'] }}
                        </div>
                        <div class="health-grade-desc">Kondisi keuangan Anda bulan ini</div>
                    </div>
                </div>

                {{-- Metric bars --}}
                <div class="health-metrics">
                    <div class="health-metric-row">
                        <div class="health-metric-label">Tingkat Tabungan</div>
                        <div class="health-metric-bar-wrap">
                            <div class="health-metric-bar"
                                 style="width:{{ min(100, max(0, $savingRate)) }}%;
                                        background:{{ $savingRate >= 20 ? '#15803d' : ($savingRate >= 10 ? '#ca8a04' : '#b91c1c') }}">
                            </div>
                        </div>
                        <div class="health-metric-val">{{ $savingRate }}%</div>
                    </div>
                    <div class="health-metric-row">
                        <div class="health-metric-label">Penggunaan Anggaran</div>
                        <div class="health-metric-bar-wrap">
                            <div class="health-metric-bar"
                                 style="width:{{ $budgetUsage }}%;
                                        background:{{ $budgetUsage >= 90 ? '#b91c1c' : ($budgetUsage >= 70 ? '#ca8a04' : '#15803d') }}">
                            </div>
                        </div>
                        <div class="health-metric-val">{{ $budgetUsage }}%</div>
                    </div>
                </div>

                {{-- Stat grid --}}
                <div class="health-stat-grid">
                    <div class="health-stat-item">
                        <div class="health-stat-label">Cashflow</div>
                        <div class="health-stat-value {{ $cashflowPositive ? 'positive' : 'negative' }}">
                            {{ $cashflowPositive ? '✓ Positif' : '✗ Negatif' }}
                        </div>
                    </div>
                    <div class="health-stat-item">
                        <div class="health-stat-label">Transaksi</div>
                        <div class="health-stat-value neutral">{{ $monthlyTxCount }}×</div>
                    </div>
                    <div class="health-stat-item">
                        <div class="health-stat-label">Dompet</div>
                        <div class="health-stat-value neutral">{{ $wallets->count() }} aktif</div>
                    </div>
                    <div class="health-stat-item">
                        <div class="health-stat-label">Saldo</div>
                        <div class="health-stat-value {{ $totalBalance >= 0 ? 'positive' : 'negative' }}">
                            {{ $totalBalance >= 0 ? '✓ Positif' : '✗ Negatif' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="quick-actions-panel">
            <div class="quick-actions-header">
                <h3>Aksi Cepat</h3>
            </div>
            <div class="quick-actions-body">
                <div class="quick-action-grid">
                    <a href="{{ route('transaction.index') }}" class="quick-action-btn" id="qa-add-transaction">
                        <div class="quick-action-icon" style="background:#ecfdf5;color:#1d6a4a;">
                            <i class="bi bi-plus-circle-fill"></i>
                        </div>
                        <span class="quick-action-label">Tambah Transaksi</span>
                    </a>
                    <a href="{{ route('wallet.index') }}" class="quick-action-btn" id="qa-add-wallet">
                        <div class="quick-action-icon" style="background:#ecfeff;color:#0e7490;">
                            <i class="bi bi-wallet-fill"></i>
                        </div>
                        <span class="quick-action-label">Kelola Dompet</span>
                    </a>
                    <a href="{{ route('transaction.index') }}" class="quick-action-btn" id="qa-transfer">
                        <div class="quick-action-icon" style="background:#eff6ff;color:#2563eb;">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <span class="quick-action-label">Transfer Saldo</span>
                    </a>
                    <a href="{{ route('ocr.upload') }}" class="quick-action-btn" id="qa-ocr">
                        <div class="quick-action-icon" style="background:#fdf4ff;color:#9333ea;">
                            <i class="bi bi-file-earmark-image-fill"></i>
                        </div>
                        <span class="quick-action-label">Impor OCR</span>
                    </a>
                    <a href="{{ route('report.index') }}" class="quick-action-btn" id="qa-report">
                        <div class="quick-action-icon" style="background:#fff7ed;color:#ea580c;">
                            <i class="bi bi-bar-chart-line-fill"></i>
                        </div>
                        <span class="quick-action-label">Lihat Laporan</span>
                    </a>
                    <a href="{{ route('category.index') }}" class="quick-action-btn" id="qa-category">
                        <div class="quick-action-icon" style="background:#fef3c7;color:#ca8a04;">
                            <i class="bi bi-tag-fill"></i>
                        </div>
                        <span class="quick-action-label">Kategori</span>
                    </a>
                    <a href="{{ route('transaction.index') }}" class="quick-action-btn" id="qa-history">
                        <div class="quick-action-icon" style="background:#f5f3ff;color:#7c3aed;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <span class="quick-action-label">Riwayat Transaksi</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="quick-action-btn" id="qa-profile">
                        <div class="quick-action-icon" style="background:#f9fafb;color:#6b7280;">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <span class="quick-action-label">Profil Saya</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         5. WALLET CARDS + TOP SPENDING CATEGORIES
    ════════════════════════════════════════════════ --}}
    <div class="wallet-cat-grid">

        {{-- Wallet Cards --}}
        <div class="wallet-grid-section">
            <div class="wallet-grid-header">
                <h3>Dompet Saya</h3>
                <a href="{{ route('wallet.index') }}" class="wallet-grid-link">
                    Kelola <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="wallet-grid-body">
                @if($wallets->isEmpty())
                    <div class="panel-empty">
                        <i class="bi bi-wallet2"></i>
                        <span>Belum ada dompet.</span>
                        <a href="{{ route('wallet.index') }}">Tambah dompet</a>
                    </div>
                @else
                    @foreach($wallets as $wallet)
                        @php
                            $isNeg    = (float)$wallet->balance < 0;
                            $wPct     = round(abs((float)$wallet->balance) / $maxWalletBalance * 100);
                            $wColor   = $wallet->color ?? '#1d6a4a';
                            $wBg      = $wallet->color ? $wallet->color . '22' : '#ecfdf5';
                        @endphp
                        <div class="wallet-card-item">
                            <div class="wallet-card-icon" style="background:{{ $wBg }};color:{{ $wColor }}">
                                <i class="bi {{ $wallet->icon ?? 'bi-wallet2' }}"></i>
                            </div>
                            <div class="wallet-card-info">
                                <div class="wallet-card-name">{{ $wallet->name }}</div>
                                <div class="wallet-card-type">{{ ucfirst($wallet->type ?? 'Dompet') }}</div>
                                <div class="wallet-pct-bar">
                                    <div class="wallet-pct-fill"
                                         style="width:{{ $wPct }}%;
                                                background:{{ $isNeg ? '#b91c1c' : $wColor }}">
                                    </div>
                                </div>
                            </div>
                            <div class="wallet-card-right">
                                <div class="wallet-card-balance {{ $isNeg ? 'negative' : '' }}">
                                    {{ $wallet->formatted_balance }}
                                </div>
                                <div class="wallet-card-pct">{{ $wPct }}% maks</div>
                            </div>
                            @if($isNeg)
                                <span class="wallet-neg-badge">Minus</span>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Top Spending Categories --}}
        <div class="category-section">
            <div class="category-header">
                <h3>Pengeluaran per Kategori</h3>
                <span style="font-size:11px;color:#9ca3af;">{{ $monthName }}</span>
            </div>
            <div class="category-body">
                @if($topCategories->isEmpty())
                    <div class="panel-empty" style="padding:24px 12px;">
                        <i class="bi bi-tag"></i>
                        <span>Belum ada pengeluaran bulan ini</span>
                    </div>
                @else
                    @foreach($topCategories as $catRow)
                        @php
                            $catPct      = round($catRow->total_amount / $maxCatAmount * 100);
                            $catSharePct = round($catRow->total_amount / $totalCatExpense * 100);
                            $cColor      = $catRow->category?->color ?? '#b91c1c';
                            $cIcon       = $catRow->category?->icon  ?? 'bi-tag-fill';
                        @endphp
                        <div class="category-item">
                            <div class="category-icon-wrap" style="background:{{ $cColor }}22;color:{{ $cColor }}">
                                <i class="bi {{ $cIcon }}"></i>
                            </div>
                            <div class="category-info">
                                <div class="category-name">{{ $catRow->category?->name ?? 'Lainnya' }}</div>
                                <div class="category-bar-wrap">
                                    <div class="category-bar-fill"
                                         style="width:{{ $catPct }}%;background:{{ $cColor }}">
                                    </div>
                                </div>
                            </div>
                            <div class="category-right">
                                <div class="category-amount">
                                    Rp {{ number_format($catRow->total_amount, 0, ',', '.') }}
                                </div>
                                <div class="category-pct">{{ $catSharePct }}% total</div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         6. RECENT TRANSACTIONS
    ════════════════════════════════════════════════ --}}
    <div class="tx-feed-panel">
        <div class="tx-feed-header">
            <h3>Transaksi Terbaru</h3>
            <a href="{{ route('transaction.index') }}" class="tx-feed-link">
                Lihat semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        @forelse($recentTransactions as $tx)
            @php
                $isTransfer = !is_null($tx->transfer_group_id);
                $isIncome   = !$isTransfer && $tx->amount >= 0;
                $isAdjustment = (bool) $tx->is_balance_adjustment;

                if ($isAdjustment) {
                    $txBg        = '#f5f3ff'; $txColor = '#7c3aed';
                    $txIcon      = 'bi-sliders';
                    $txBadge     = ''; $txBadgeLabel = 'Koreksi Saldo';
                    $txAmtClass  = 'transfer'; $txPrefix = $tx->amount >= 0 ? '+' : '−';
                } elseif ($isTransfer) {
                    $txBg        = '#eff6ff'; $txColor = '#2563eb';
                    $txIcon      = 'bi-arrow-left-right';
                    $txBadge     = 'tx-badge-transfer'; $txBadgeLabel = 'Transfer';
                    $txAmtClass  = 'transfer'; $txPrefix = '';
                } elseif ($isIncome) {
                    $txBg        = '#f0fdf4'; $txColor = '#15803d';
                    $txIcon      = $tx->category?->icon ?? 'bi-arrow-down-left-circle';
                    $txBadge     = 'tx-badge-income'; $txBadgeLabel = 'Pemasukan';
                    $txAmtClass  = 'income'; $txPrefix = '+';
                } else {
                    $txBg        = '#fff1f2'; $txColor = '#b91c1c';
                    $txIcon      = $tx->category?->icon ?? 'bi-arrow-up-right-circle';
                    $txBadge     = 'tx-badge-expense'; $txBadgeLabel = 'Pengeluaran';
                    $txAmtClass  = 'expense'; $txPrefix = '−';
                }
            @endphp
            <div class="tx-item">
                <div class="tx-item-icon" style="background:{{ $txBg }};color:{{ $txColor }}">
                    <i class="bi {{ $txIcon }}"></i>
                </div>
                <div class="tx-item-body">
                    <div class="tx-item-desc">
                        {{ $tx->description ?? ($isAdjustment ? 'Koreksi Saldo' : ($isTransfer ? 'Transfer' : ($isIncome ? 'Pemasukan' : 'Pengeluaran'))) }}
                    </div>
                    <div class="tx-item-meta">
                        <span class="tx-item-date">{{ $tx->transaction_date->format('d M Y') }}</span>
                        <span class="tx-item-badge {{ $txBadge }}" @if($isAdjustment) style="background:#f5f3ff;color:#7c3aed;" @endif>{{ $txBadgeLabel }}</span>
                        @if(!$isTransfer && !$isAdjustment && $tx->category)
                            <span class="tx-item-cat">{{ $tx->category->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="tx-item-right">
                    <div class="tx-item-amount {{ $txAmtClass }}">
                        {{ $txPrefix }} {{ $tx->formatted_amount }}
                    </div>
                    @if($tx->wallet)
                        <div class="tx-item-wallet">{{ $tx->wallet->name }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="panel-empty">
                <i class="bi bi-inbox"></i>
                <span>Belum ada transaksi terbaru.</span>
                <a href="{{ route('transaction.index') }}">Tambah sekarang</a>
            </div>
        @endforelse
    </div>

@endsection

@push('js')
<script>
(function () {
    'use strict';

    /* ── Chart base config ── */
    const BASE = {
        chart: {
            toolbar: { show: false },
            fontFamily: "'Plus Jakarta Sans', system-ui, sans-serif",
            animations: { enabled: true, speed: 700 }
        },
        grid: {
            borderColor: '#f3f4f6',
            strokeDashArray: 4,
            padding: { left: 2, right: 2, top: 0, bottom: 0 }
        },
        tooltip: { theme: 'light', style: { fontSize: '12px' } }
    };

    function fmt(v) {
        return 'Rp ' + Math.abs(v).toLocaleString('id-ID');
    }
    function fmtK(v) {
        const a = Math.abs(v);
        if (a >= 1_000_000) return 'Rp ' + (a / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
        if (a >= 1_000)    return 'Rp ' + (a / 1_000).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb';
        return 'Rp ' + a.toLocaleString('id-ID');
    }

    /* ── Cashflow Trend Chart ── */
    const cashflowEl = document.getElementById('chart-cashflow');
    if (cashflowEl) {
        const labels  = @json($cashflowLabels);
        const income  = @json($cashflowIncome);
        const expense = @json($cashflowExpense);

        new ApexCharts(cashflowEl, {
            ...BASE,
            chart: { ...BASE.chart, type: 'area', height: 192 },
            series: [
                { name: 'Pemasukan',   data: income },
                { name: 'Pengeluaran', data: expense }
            ],
            colors: ['#15803d', '#b91c1c'],
            fill: {
                type: 'gradient',
                gradient: {
                    type: 'vertical',
                    shadeIntensity: 0.1,
                    opacityFrom: 0.25,
                    opacityTo: 0.02,
                    stops: [0, 100]
                }
            },
            stroke: { curve: 'smooth', width: [2.5, 2.5] },
            markers: { size: 3.5, strokeWidth: 2, strokeColors: '#fff', hover: { size: 5 } },
            xaxis: {
                categories: labels,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { fontSize: '11px', colors: '#9ca3af', fontWeight: 600 } }
            },
            yaxis: {
                labels: {
                    style: { fontSize: '10px', colors: '#9ca3af' },
                    formatter: v => fmtK(v)
                }
            },
            legend: { show: false },
            tooltip: { ...BASE.tooltip, y: { formatter: v => fmt(v) } },
            dataLabels: { enabled: false }
        }).render();
    }

    /* ── Expense Donut Chart ── */
    const donutEl = document.getElementById('chart-donut');
    if (donutEl) {
        const labels = @json($donutLabels);
        const values = @json($donutValues);

        if (values.length > 0) {
            new ApexCharts(donutEl, {
                chart: {
                    type: 'donut',
                    height: 192,
                    fontFamily: "'Plus Jakarta Sans', system-ui, sans-serif",
                    toolbar: { show: false },
                    animations: { enabled: true, speed: 700 }
                },
                series: values,
                labels: labels,
                colors: ['#b91c1c', '#0e7490', '#7c3aed', '#ca8a04', '#ea580c', '#db2777'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '62%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '11px',
                                    fontWeight: 700,
                                    color: '#9ca3af',
                                    formatter: w => {
                                        const t = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return fmtK(t);
                                    }
                                },
                                value: {
                                    fontSize: '14px',
                                    fontWeight: 800,
                                    color: '#111827',
                                    formatter: v => fmtK(+v)
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    fontSize: '11px',
                    fontWeight: 600,
                    markers: { size: 6, shape: 'circle' },
                    itemMargin: { horizontal: 6 }
                },
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: v => fmt(v) } }
            }).render();
        }
    }
})();
</script>
@endpush
