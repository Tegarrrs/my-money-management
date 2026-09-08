<section class="rp-card unusual-transaction-card">
    <div class="rp-card-header unusual-transaction-header">
        <div>
            <div class="rp-card-title"><i class="bi bi-exclamation-diamond text-warning"></i> Transaksi Tidak Biasa</div>
            <div class="rp-card-sub">Deteksi lokal berdasarkan lonjakan nominal dan kemungkinan transaksi ganda</div>
        </div>
        <span class="local-analysis-badge"><i class="bi bi-device-ssd"></i> Diproses lokal</span>
    </div>

    @if(count($unusualTransactions) > 0)
        <div class="unusual-transaction-list">
            @foreach($unusualTransactions as $transaction)
                <article class="unusual-transaction-item">
                    <div class="unusual-icon"><i class="bi bi-activity"></i></div>
                    <div class="unusual-main">
                        <div class="unusual-title-row">
                            <strong>{{ $transaction['description'] }}</strong>
                            <span>Rp {{ number_format($transaction['amount'], 0, ',', '.') }}</span>
                        </div>
                        <div class="unusual-meta">
                            {{ $transaction['category'] }} · {{ $transaction['formatted_date'] }} · {{ $transaction['wallet'] }}
                        </div>
                        <ul>
                            @foreach($transaction['reasons'] as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="unusual-empty">
            <i class="bi bi-check-circle"></i>
            <div>
                <strong>Tidak ada anomali nominal yang menonjol</strong>
                <span>Aplikasi belum menemukan lonjakan besar atau transaksi ganda pada periode ini.</span>
            </div>
        </div>
    @endif

    <div class="unusual-method-note">
        Ini adalah indikator untuk diperiksa, bukan bukti transaksi salah. Median dihitung hanya dari pengeluaran reguler pada periode terpilih; transfer dan koreksi saldo dikecualikan.
    </div>
</section>
