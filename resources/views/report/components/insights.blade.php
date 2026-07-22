<div class="report-insight-grid">
    <section class="ai-insight-card">
        <div class="ai-insight-header">
            <div class="ai-insight-icon"><i class="bi bi-stars"></i></div>
            <div>
                <div class="rp-card-title">Resume Keuangan AI</div>
                <div class="rp-card-sub">
                    Analisis periode terpilih
                    <span class="ai-source-badge">
                        {{ ($insight['source'] ?? 'local') === 'gemini' ? 'Gemini AI' : 'Analisis otomatis' }}
                    </span>
                </div>
            </div>
        </div>

        <p class="ai-summary-text">{{ $insight['summary'] }}</p>

        <div class="ai-list-grid">
            <div>
                <div class="ai-list-title"><i class="bi bi-graph-up-arrow"></i> Temuan utama</div>
                <ul class="ai-insight-list">
                    @forelse($insight['highlights'] as $highlight)
                        <li>{{ $highlight }}</li>
                    @empty
                        <li>Belum ada temuan untuk periode ini.</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <div class="ai-list-title"><i class="bi bi-lightbulb"></i> Saran</div>
                <ul class="ai-insight-list recommendations">
                    @forelse($insight['recommendations'] as $recommendation)
                        <li>{{ $recommendation }}</li>
                    @empty
                        <li>Tambahkan transaksi untuk mendapatkan saran.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>

    <section class="rp-card largest-expense-card">
        <div class="rp-card-header">
            <div>
                <div class="rp-card-title"><i class="bi bi-arrow-up-right-circle text-danger"></i> Pengeluaran Terbesar</div>
                <div class="rp-card-sub">5 transaksi dengan nominal tertinggi</div>
            </div>
        </div>
        <div class="largest-expense-list">
            @forelse($largestExpenses as $index => $expense)
                <div class="largest-expense-item">
                    <span class="expense-rank">{{ $index + 1 }}</span>
                    <div class="expense-main">
                        <div class="expense-description">{{ $expense['description'] }}</div>
                        <div class="expense-meta">{{ $expense['category'] }} · {{ $expense['formatted_date'] }} · {{ $expense['wallet'] }}</div>
                    </div>
                    <div class="expense-amount">Rp {{ number_format($expense['amount'], 0, ',', '.') }}</div>
                </div>
            @empty
                <div class="chart-empty py-5">
                    <i class="bi bi-receipt"></i>
                    Belum ada pengeluaran
                </div>
            @endforelse
        </div>
    </section>
</div>
