@extends('layouts.main')

@section('title', 'Dasbor')
@section('subtitle', 'Selamat datang kembali, ' . explode(' ', auth()->user()->name)[0] . ' 👋')

@section('content')
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="summary-card card-balance">
                <div class="card-icon ic-balance"><i class="bi bi-wallet2"></i></div>
                <div class="card-label">Total Saldo</div>
                <div class="card-amount">Rp {{ number_format($totalBalance, 0, ',', '.') }}</div>
                <div class="card-meta">dari {{ $wallets->count() }} dompet aktif</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="summary-card card-income">
                <div class="card-icon ic-income"><i class="bi bi-arrow-down-left-circle-fill"></i></div>
                <div class="card-label">Total Pemasukan</div>
                <div class="card-amount" style="color:var(--color-income)">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
                <div class="card-meta">Semua waktu</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="summary-card card-expense">
                <div class="card-icon ic-expense"><i class="bi bi-arrow-up-right-circle-fill"></i></div>
                <div class="card-label">Total Pengeluaran</div>
                <div class="card-amount" style="color:var(--color-expense)">Rp {{ number_format(abs($totalExpense), 0, ',', '.') }}</div>
                <div class="card-meta">Semua waktu</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="summary-card card-savings">
                <div class="card-icon ic-savings"><i class="bi bi-piggy-bank-fill"></i></div>
                <div class="card-label">Tabungan Bersih</div>
                <div class="card-amount" style="color:var(--color-savings)">
                    Rp {{ number_format($totalIncome + $totalExpense, 0, ',', '.') }}
                </div>
                <div class="card-meta">Pemasukan − Pengeluaran</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="chart-card" style="min-height:320px;">
                <div class="chart-card-header">
                    <div>
                        <div class="chart-title">Pemasukan vs Pengeluaran</div>
                        <div class="chart-subtitle">Tren bulanan</div>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item"><span class="legend-dot" style="background:var(--color-income)"></span>Pemasukan</span>
                        <span class="legend-item"><span class="legend-dot" style="background:var(--color-expense)"></span>Pengeluaran</span>
                    </div>
                </div>
                <div id="chart-line" style="height:240px;"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card" style="min-height:320px;">
                <div class="chart-card-header">
                    <div>
                        <div class="chart-title">Dompet Saya</div>
                        <div class="chart-subtitle">Saldo per dompet</div>
                    </div>
                </div>
                @if($wallets->isEmpty())
                    <div class="text-center py-4" style="color:var(--color-muted);font-size:13px;">
                        <i class="bi bi-wallet2 d-block mb-2" style="font-size:2rem;"></i>
                        Belum ada dompet.<br>
                        <a href="{{ route('wallet.index') }}" style="color:var(--color-primary)">Tambah dompet</a>
                    </div>
                @else
                    <div style="padding-top:8px;">
                        @foreach($wallets as $wallet)
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--color-border-soft);">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:{{ $wallet->color ?? '#e8f5e9' }};display:flex;align-items:center;justify-content:center;">
                                        <i class="bi {{ $wallet->icon ?? 'bi-wallet2' }}" style="font-size:14px;color:{{ $wallet->color ? 'white' : '#15803d' }}"></i>
                                    </div>
                                    <div>
                                        <div style="font-size:13px;font-weight:600;">{{ $wallet->name }}</div>
                                        <div style="font-size:11px;color:var(--color-muted)">{{ ucfirst($wallet->type) }}</div>
                                    </div>
                                </div>
                                <span style="font-size:13px;font-weight:700;font-family:'JetBrains Mono',monospace;">{{ $wallet->formatted_balance }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="section-header">
        <h2 class="section-title">Transaksi Terbaru</h2>
        <a href="{{ route('transaction.index') }}" class="section-link">Lihat semua <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="table-card">
        <div class="table-responsive">
            <table class="table dompetra-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Kategori</th>
                        <th class="text-end">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $tx)
                        @php $isIncome = $tx->amount >= 0; @endphp
                        <tr>
                            <td><span class="tx-date">{{ $tx->transaction_date->format('d M Y') }}</span></td>
                            <td><span class="tx-desc">{{ $tx->description ?? '-' }}</span></td>
                            <td>
                                @if($tx->category)
                                    <span class="cat-badge">
                                        <span class="cat-dot"></span>{{ $tx->category->name }}
                                    </span>
                                @else
                                    <span class="cat-badge"><span class="cat-dot"></span>Tanpa Kategori</span>
                                @endif
                            </td>
                            <td class="amount-cell {{ $isIncome ? 'amount-income' : 'amount-expense' }}">
                                {{ $isIncome ? '+' : '−' }} {{ $tx->formatted_amount }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4" style="color:var(--color-muted);font-size:13px;">
                                <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;"></i>
                                Belum ada transaksi. <a href="{{ route('transaction.index') }}" style="color:var(--color-primary)">Tambah sekarang</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection