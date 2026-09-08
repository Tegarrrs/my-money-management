<div class="report-insight-grid">
    <section class="ai-insight-card">
        <div class="ai-insight-header">
            <div class="ai-insight-heading">
                <div class="ai-insight-icon"><i class="bi bi-stars"></i></div>
                <div>
                    <div class="rp-card-title">Resume Keuangan AI</div>
                    <div class="rp-card-sub">
                        Analisis periode terpilih
                        <span class="ai-source-badge">
                            {{ ($insight['source'] ?? 'local') === 'gemini' ? 'Gemini AI' : 'Analisis lokal' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="ai-insight-actions">
                <button type="button" class="ai-data-button" data-bs-toggle="modal" data-bs-target="#aiDataDisclosureModal">
                    <i class="bi bi-shield-check"></i> Data yang dikirim
                </button>
                <form action="{{ route('report.analyze') }}" method="POST">
                    @csrf
                    @foreach($analysisParameters as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <button type="submit" class="ai-analyze-button" {{ $transactionCount === 0 ? 'disabled' : '' }}>
                        <i class="bi bi-stars"></i>
                        {{ ($insight['source'] ?? 'local') === 'gemini' ? 'Analisis ulang' : 'Analisis dengan AI' }}
                    </button>
                </form>
            </div>
        </div>

        <p class="ai-summary-text">{{ $insight['summary'] }}</p>
        @if(($insight['source'] ?? 'local') === 'gemini' && !empty($insight['analyzed_at']))
            <div class="ai-analysis-time">
                <i class="bi bi-clock"></i>
                Terakhir dianalisis {{ \Illuminate\Support\Carbon::parse($insight['analyzed_at'])->translatedFormat('d M Y, H:i') }}
            </div>
        @elseif(!$aiConfigured)
            <div class="ai-analysis-time"><i class="bi bi-info-circle"></i> Gemini belum dikonfigurasi; ringkasan lokal tetap tersedia.</div>
        @else
            <div class="ai-analysis-time"><i class="bi bi-hand-index-thumb"></i> Gemini hanya dipanggil ketika Anda menekan tombol analisis.</div>
        @endif

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

<div class="modal fade" id="aiDataDisclosureModal" tabindex="-1" aria-labelledby="aiDataDisclosureTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ai-disclosure-dialog">
        <div class="modal-content ai-disclosure-content">
            <div class="modal-header ai-disclosure-header">
                <div>
                    <div class="ai-disclosure-eyebrow"><i class="bi bi-shield-lock"></i> Transparansi AI</div>
                    <h5 class="modal-title" id="aiDataDisclosureTitle">Data yang diterima Gemini</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body ai-disclosure-body">
                <div class="ai-disclosure-note">
                    Gemini hanya menerima ringkasan periode aktif setelah Anda menekan
                    <strong>Analisis dengan AI</strong>. Membuka atau mengganti periode tidak mengirim data baru.
                </div>
                <div class="ai-disclosure-grid">
                    <section class="ai-disclosure-panel sent">
                        <h6><i class="bi bi-check-circle-fill"></i> Dikirim ke Gemini</h6>
                        <ul>
                            <li>Tanggal awal dan akhir periode.</li>
                            <li>Total pemasukan, pengeluaran, dan selisih.</li>
                            <li>Jumlah transaksi dan jumlah transaksi pengeluaran.</li>
                            <li>Ringkasan kategori: nama, total, persentase, dan jumlah transaksi.</li>
                            <li>Tiga pengeluaran terbesar: tanggal, deskripsi, kategori, dan nominal.</li>
                        </ul>
                    </section>
                    <section class="ai-disclosure-panel protected">
                        <h6><i class="bi bi-x-circle-fill"></i> Tidak dikirim</h6>
                        <ul>
                            <li>Nama, email, kata sandi, atau identitas akun.</li>
                            <li>Saldo dan nama dompet.</li>
                            <li>ID transaksi serta catatan detail transaksi.</li>
                            <li>Foto atau file struk.</li>
                            <li>Transaksi di luar periode yang sedang dipilih.</li>
                        </ul>
                    </section>
                </div>
                <div class="ai-local-note">
                    <i class="bi bi-cpu"></i>
                    Deteksi transaksi tidak biasa dihitung secara lokal di aplikasi dan tidak membutuhkan Gemini.
                </div>
            </div>
        </div>
    </div>
</div>
