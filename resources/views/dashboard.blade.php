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

    @php
        $isPositive = ($totalIncome + $totalExpense) >= 0;
    @endphp

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="s-card wallet">
            <div class="s-icon"><i class="bi bi-wallet2"></i></div>
            <div class="s-label">Total Saldo</div>
            <div class="s-value">Rp {{ number_format($totalBalance, 0, ',', '.') }}</div>
            <div class="s-sub">dari {{ $wallets->count() }} dompet aktif</div>
        </div>
        <div class="s-card income">
            <div class="s-icon"><i class="bi bi-arrow-down-left"></i></div>
            <div class="s-label">Total Pemasukan</div>
            <div class="s-value">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
            <div class="s-sub">semua waktu</div>
        </div>
        <div class="s-card expense">
            <div class="s-icon"><i class="bi bi-arrow-up-right"></i></div>
            <div class="s-label">Total Pengeluaran</div>
            <div class="s-value">Rp {{ number_format(abs($totalExpense), 0, ',', '.') }}</div>
            <div class="s-sub">semua waktu</div>
        </div>
        <div class="s-card {{ $isPositive ? 'balance' : 'balance-neg' }}">
            <div class="s-icon"><i class="bi bi-piggy-bank-fill"></i></div>
            <div class="s-label">Tabungan Bersih</div>
            <div class="s-value">Rp {{ number_format(abs($totalIncome + $totalExpense), 0, ',', '.') }}</div>
            <div class="s-sub">pemasukan − pengeluaran</div>
        </div>
    </div>

    <!-- Layout Dompet & Chart -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="rp-card h-100 mb-0">
                <div class="rp-card-header">
                    <div>
                        <div class="rp-card-title">Pemasukan vs Pengeluaran</div>
                        <div class="rp-card-sub">Tren semua waktu</div>
                    </div>
                </div>
                <div class="rp-card-body p-3">
                    <div id="chart-line" style="height:260px;"></div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="rp-card h-100 mb-0">
                <div class="rp-card-header">
                    <div>
                        <div class="rp-card-title">Daftar Dompet</div>
                        <div class="rp-card-sub">Saldo terkini per dompet</div>
                    </div>
                </div>
                <div class="rp-card-body-flush p-2">
                    @if($wallets->isEmpty())
                        <div class="empty-state">
                            <i class="bi bi-wallet2"></i>
                            Belum ada dompet.
                            <a href="{{ route('wallet.index') }}" style="color:#4f46e5; text-decoration:none;">Tambah dompet</a>
                        </div>
                    @else
                        @foreach($wallets as $wallet)
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border-bottom:1px solid #f3f4f6;">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:34px;height:34px;border-radius:10px;background:{{ $wallet->color ?? '#e8f5e9' }};display:flex;align-items:center;justify-content:center;">
                                        <i class="bi {{ $wallet->icon ?? 'bi-wallet2' }}" style="font-size:16px;color:{{ $wallet->color ? 'white' : '#15803d' }}"></i>
                                    </div>
                                    <div>
                                        <div style="font-size:13px;font-weight:600;color:#111827;">{{ $wallet->name }}</div>
                                        <div style="font-size:11px;color:#9ca3af;">{{ ucfirst($wallet->type) }}</div>
                                    </div>
                                </div>
                                <span style="font-size:13px;font-weight:700;color:#111827;">{{ $wallet->formatted_balance }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="section-header">
        <h2 class="section-title">Transaksi Terbaru</h2>
        <a href="{{ route('transaction.index') }}" class="section-link">Lihat semua <i class="bi bi-arrow-right"></i></a>
    </div>
    
    <div class="rp-card">
        <div class="rp-card-body-flush">
            <div class="table-responsive">
                <table class="tx-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Kategori</th>
                            <th class="th-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                            @php $isIncome = $tx->amount >= 0; @endphp
                            <tr>
                                <td class="cell-date">
                                    <div class="date-main">{{ $tx->transaction_date->format('d M Y') }}</div>
                                    <div class="date-day">{{ $tx->transaction_date->translatedFormat('l') }}</div>
                                </td>
                                <td>
                                    <span class="desc-main" title="{{ $tx->description }}">{{ $tx->description ?? '-' }}</span>
                                </td>
                                <td>
                                    @if($tx->category)
                                        <span class="cat-pill">
                                            <span class="cat-dot" style="background:{{ $tx->category->type === 'income' ? '#15803d' : '#b91c1c' }}"></span>
                                            {{ $tx->category->name }}
                                        </span>
                                    @else
                                        <span style="color:#e5e7eb;font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td class="{{ $isIncome ? 'amt-income' : 'amt-expense' }}">
                                    {{ $isIncome ? '+' : '−' }} {{ $tx->formatted_amount }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        Belum ada transaksi terbaru.<br>
                                        <a href="{{ route('transaction.index') }}" style="color:#4f46e5; text-decoration:none;">Tambah sekarang</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection