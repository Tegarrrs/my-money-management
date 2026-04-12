@php
    $isPositive = $summary['balance'] >= 0;
@endphp
<div class="summary-grid">
    <div class="s-card income">
        <div class="s-icon"><i class="bi bi-arrow-down-left"></i></div>
        <div class="s-label">Total Pemasukan</div>
        <div class="s-value">Rp {{ number_format($summary['income'], 0, ',', '.') }}</div>
        <div class="s-sub">periode yang dipilih</div>
    </div>
    <div class="s-card expense">
        <div class="s-icon"><i class="bi bi-arrow-up-right"></i></div>
        <div class="s-label">Total Pengeluaran</div>
        <div class="s-value">Rp {{ number_format($summary['expense'], 0, ',', '.') }}</div>
        <div class="s-sub">periode yang dipilih</div>
    </div>
    <div class="s-card {{ $isPositive ? 'balance' : 'balance-neg' }}">
        <div class="s-icon"><i class="bi bi-{{ $isPositive ? 'graph-up' : 'graph-down' }}"></i></div>
        <div class="s-label">Selisih (Balance)</div>
        <div class="s-value">Rp {{ number_format(abs($summary['balance']), 0, ',', '.') }}</div>
        <div class="s-sub">{{ $isPositive ? 'surplus — kondisi sehat' : 'defisit — pengeluaran melebihi pemasukan' }}
        </div>
    </div>
</div>
