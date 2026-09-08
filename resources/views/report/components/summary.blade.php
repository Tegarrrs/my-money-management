@php
    $isPositive = $summary['balance'] >= 0;
    $expenseRatio = $summary['income'] > 0 ? ($summary['expense'] / $summary['income']) * 100 : null;
    $ratioProgress = $expenseRatio === null ? 0 : min($expenseRatio, 100);
    $ratioStatus = match (true) {
        $expenseRatio === null => 'Belum ada pemasukan',
        $expenseRatio <= 70 => 'Terkendali',
        $expenseRatio <= 100 => 'Perlu perhatian',
        default => 'Melebihi pemasukan',
    };
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
        <div class="s-value">{{ $isPositive ? '' : '-' }}Rp {{ number_format(abs($summary['balance']), 0, ',', '.') }}</div>
        <div class="s-sub">{{ $isPositive ? 'surplus pada periode ini' : 'defisit pada periode ini' }}
        </div>
    </div>
    <div class="s-card expense-ratio-card">
        <div class="s-icon"><i class="bi bi-speedometer2"></i></div>
        <div class="s-label">Rasio Pengeluaran</div>
        <div class="ratio-value-row">
            <div class="s-value ratio-value">{{ $expenseRatio === null ? '—' : number_format($expenseRatio, 1, ',', '.') . '%' }}</div>
            <span class="ratio-status {{ $expenseRatio !== null && $expenseRatio > 100 ? 'danger' : '' }}">{{ $ratioStatus }}</span>
        </div>
        <div class="ratio-progress" aria-hidden="true">
            <span style="width: {{ $ratioProgress }}%"></span>
        </div>
        <div class="s-sub">Dari pemasukan · {{ $transactionCount }} transaksi</div>
    </div>
</div>
