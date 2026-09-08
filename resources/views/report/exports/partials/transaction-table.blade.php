<table class="transactions">
    <thead>
        <tr>
            <th class="date">Tanggal</th>
            <th class="description">Deskripsi</th>
            <th class="detail">Detail</th>
            <th class="category">Kategori</th>
            <th class="wallet">Dompet</th>
            <th class="type">Jenis</th>
            <th class="amount">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pageTransactions as $transaction)
            @php
                $isTransfer = !is_null($transaction->transfer_group_id);
                $isAdjustment = (bool) $transaction->is_balance_adjustment;
                $isIncome = !$isTransfer && !$isAdjustment && $transaction->amount >= 0;
                $type = match (true) {
                    $isAdjustment => 'Koreksi Saldo',
                    $isTransfer => 'Transfer',
                    $isIncome => 'Pemasukan',
                    default => 'Pengeluaran',
                };
                $typeClass = match (true) {
                    $isAdjustment => 'adjustment',
                    $isTransfer => 'transfer',
                    $isIncome => 'income',
                    default => 'expense',
                };
                $category = match (true) {
                    $isAdjustment => 'Koreksi Saldo',
                    $isTransfer => 'Transfer',
                    default => $transaction->category?->name ?? 'Tanpa kategori',
                };
            @endphp
            <tr>
                <td class="date">{{ $transaction->transaction_date->format('d M Y') }}</td>
                <td class="description">{{ $transaction->description ?: '-' }}</td>
                <td class="detail">{{ $transaction->detail ?: '-' }}</td>
                <td class="category">{{ $category }}</td>
                <td class="wallet">{{ $transaction->wallet?->name ?? '-' }}</td>
                <td class="type type-{{ $typeClass }}">{{ $type }}</td>
                <td class="amount amount-{{ $typeClass }}">
                    {{ $transaction->amount >= 0 ? '+' : '-' }} Rp {{ number_format(abs($transaction->amount), 0, ',', '.') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="empty">Belum ada transaksi pada periode ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>
