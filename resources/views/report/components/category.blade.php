<div class="report-row">
    {{-- Donut chart --}}
    <div class="rp-card">
        <div class="rp-card-header">
            <div>
                <div class="rp-card-title">Kategori Pengeluaran</div>
                <div class="rp-card-sub">Distribusi per kategori</div>
            </div>
        </div>
        <div class="rp-card-body">
            @if(count($categoryBreakdown) > 0)
                <div id="categoryChart"></div>
            @else
                <div class="chart-empty">
                    <i class="bi bi-pie-chart"></i>
                    Belum ada data pengeluaran
                </div>
            @endif
        </div>
    </div>

    {{-- Category table --}}
    <div class="rp-card">
        <div class="rp-card-header">
            <div>
                <div class="rp-card-title">Rincian Kategori</div>
                <div class="rp-card-sub">{{ count($categoryBreakdown) }} kategori ditemukan</div>
            </div>
        </div>
        <div class="rp-card-body-flush">
            @php
                $catColors = ['#b91c1c', '#d97706', '#15803d', '#2563eb', '#7c3aed', '#db2777', '#0891b2', '#65a30d'];
            @endphp
            <table class="cat-table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th class="th-right">Porsi</th>
                        <th class="th-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categoryBreakdown as $i => $cat)
                        <tr>
                            <td>
                                <div class="cat-name-wrap">
                                    <span class="cat-color-dot"
                                        style="background:{{ $catColors[$i % count($catColors)] }}"></span>
                                    <span class="cat-name">{{ $cat['category'] }}</span>
                                    <button class="category-detail-toggle js-category-detail" type="button"
                                        data-category-index="{{ $i }}" data-bs-toggle="modal"
                                        data-bs-target="#categoryTransactionsModal">
                                        Lihat {{ $cat['transaction_count'] }} transaksi
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div class="pct-bar-wrap">
                                    <div class="pct-bar-bg">
                                        <div class="pct-bar-fill"
                                            style="width:{{ $cat['percentage'] }}%;background:{{ $catColors[$i % count($catColors)] }}">
                                        </div>
                                    </div>
                                    <span class="pct-label">{{ number_format($cat['percentage'], 1, ',', '.') }}%</span>
                                </div>
                            </td>
                            <td class="amt-expense">
                                Rp {{ number_format($cat['total'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="chart-empty">
                                    <i class="bi bi-inbox"></i>
                                    Belum ada data pengeluaran
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Satu modal dipakai ulang agar rincian panjang tidak menambah tinggi halaman. --}}
<div class="modal fade" id="categoryTransactionsModal" tabindex="-1"
    aria-labelledby="categoryTransactionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable category-modal-dialog">
        <div class="modal-content category-modal-content">
            <div class="modal-header category-modal-header">
                <div>
                    <div class="category-modal-eyebrow">Rincian kategori</div>
                    <h5 class="modal-title" id="categoryTransactionsModalLabel">Kategori</h5>
                    <div class="category-modal-summary" id="categoryModalSummary"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body category-modal-body" id="categoryModalTransactions"></div>
            <div class="modal-footer category-modal-footer">
                <span id="categoryModalFooterText"></span>
                <button type="button" class="btn-rp btn-rp-default" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        /* ── Palette ─────────────────────────────────────────────── */
        const COLORS = ['#b91c1c', '#d97706', '#15803d', '#2563eb', '#7c3aed', '#db2777', '#0891b2', '#65a30d'];

        /* ── Donut chart ─────────────────────────────────────────── */
        const categoryData = @json($categoryBreakdown);

        const moneyFormatter = new Intl.NumberFormat('id-ID', {
            style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
        });

        document.querySelectorAll('.js-category-detail').forEach(button => {
            button.addEventListener('click', function () {
                const category = categoryData[Number(this.dataset.categoryIndex)];
                if (!category) return;

                document.getElementById('categoryTransactionsModalLabel').textContent = category.category;
                document.getElementById('categoryModalSummary').textContent =
                    `${category.transaction_count} transaksi · ${category.percentage.toLocaleString('id-ID')}% dari total pengeluaran`;
                document.getElementById('categoryModalFooterText').textContent =
                    `Total ${moneyFormatter.format(category.total)}`;

                const list = document.getElementById('categoryModalTransactions');
                list.replaceChildren();

                category.transactions.forEach(transaction => {
                    const item = document.createElement('div');
                    item.className = 'category-modal-item';

                    const info = document.createElement('div');
                    info.className = 'category-modal-item-info';

                    const name = document.createElement('div');
                    name.className = 'category-modal-item-name';
                    name.textContent = transaction.description;

                    const meta = document.createElement('div');
                    meta.className = 'category-modal-item-meta';
                    meta.textContent = [transaction.formatted_date, transaction.wallet, transaction.detail]
                        .filter(Boolean).join(' · ');

                    const amount = document.createElement('div');
                    amount.className = 'category-modal-item-amount';
                    amount.textContent = moneyFormatter.format(transaction.amount);

                    info.append(name, meta);
                    item.append(info, amount);
                    list.append(item);
                });
            });
        });

        if (categoryData && categoryData.length > 0) {
            new ApexCharts(document.querySelector('#categoryChart'), {
                series: categoryData.map(i => i.total),
                labels: categoryData.map(i => i.category),
                colors: COLORS,
                chart: {
                    type: 'donut',
                    height: 300,
                    animations: { enabled: false },
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                dataLabels: {
                    enabled: true,
                    formatter: val => val.toFixed(1) + '%',
                    style: { fontSize: '12px', fontWeight: '600', colors: ['#fff'] },
                    dropShadow: { enabled: false },
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '12px',
                                    color: '#9ca3af',
                                    formatter: w => {
                                        const t = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        if (t >= 1000000) return 'Rp ' + (t / 1000000).toFixed(1) + ' Jt';
                                        if (t >= 1000) return 'Rp ' + (t / 1000).toFixed(0) + ' Rb';
                                        return 'Rp ' + t.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    fontSize: '12px',
                    fontWeight: 500,
                    markers: { width: 8, height: 8, radius: 99 },
                    itemMargin: { horizontal: 8, vertical: 4 },
                },
                tooltip: {
                    y: { formatter: val => 'Rp ' + val.toLocaleString('id-ID') },
                },
                stroke: { width: 2, colors: ['#fff'] },
            }).render();
        }
    });
</script>
@endpush
