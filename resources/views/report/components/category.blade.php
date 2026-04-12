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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        /* ── Palette ─────────────────────────────────────────────── */
        const COLORS = ['#b91c1c', '#d97706', '#15803d', '#2563eb', '#7c3aed', '#db2777', '#0891b2', '#65a30d'];

        /* ── Donut chart ─────────────────────────────────────────── */
        const categoryData = @json($categoryBreakdown);

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
