<section class="report-context-card">
    <div class="report-context-period">
        <span>Periode aktif</span>
        <strong>{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</strong>
        <small>{{ $dataQuality['period_days'] }} hari kalender</small>
    </div>

    <div class="report-comparison">
        <span>Dibanding periode sebelumnya</span>
        <small>{{ $comparison['period_label'] }}</small>
        <div>
            @foreach([
                'income' => ['label' => 'Pemasukan', 'inverse' => false],
                'expense' => ['label' => 'Pengeluaran', 'inverse' => true],
                'balance' => ['label' => 'Selisih', 'inverse' => false],
            ] as $key => $meta)
                @php
                    $change = $comparison[$key];
                    $isGood = $change !== null && ($meta['inverse'] ? $change <= 0 : $change >= 0);
                @endphp
                <span class="comparison-pill {{ $change === null ? 'neutral' : ($isGood ? 'good' : 'bad') }}">
                    {{ $meta['label'] }}
                    <b>{{ $change === null ? 'belum dapat dibandingkan' : ($change > 0 ? '+' : '').number_format($change, 1, ',', '.').'%' }}</b>
                </span>
            @endforeach
        </div>
    </div>

    <div class="report-data-quality confidence-{{ $dataQuality['confidence'] }}">
        <span>Kualitas dasar analisis</span>
        <strong>{{ match($dataQuality['confidence']) { 'high' => 'Tinggi', 'medium' => 'Sedang', default => 'Rendah' } }}</strong>
        <small>
            {{ $dataQuality['transaction_count'] }} transaksi
            @if($dataQuality['categorized_percentage'] !== null)
                · {{ $dataQuality['categorized_percentage'] }}% pengeluaran terkategori
            @endif
        </small>
    </div>
</section>
