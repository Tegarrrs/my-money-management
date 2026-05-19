<div class="trend-card">
    <div class="rp-card-header">
        <div>
            <div class="rp-card-title">Tren Pemasukan & Pengeluaran</div>
            <div class="rp-card-sub">Pergerakan harian selama periode</div>
        </div>
        <div class="trend-legend">
            <div class="trend-legend-item">
                <span class="legend-dot income"></span> Pemasukan
            </div>
            <div class="trend-legend-item">
                <span class="legend-dot expense"></span> Pengeluaran
            </div>
        </div>
    </div>
    <div class="rp-card-body">
        @if(count($trend) > 0)
            <div id="trendChart"></div>
        @else
            <div class="chart-empty">
                <i class="bi bi-graph-up"></i>
                Belum ada data tren untuk periode ini
            </div>
        @endif
    </div>
</div>

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        /* ── Trend chart ─────────────────────────────────────────── */
        const trendData = @json($trend);

        if (trendData && trendData.length > 0) {
            const fmtDate = val => {
                if (!val) return '';
                const d = new Date(val);
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
            };
            const fmtMoney = val => {
                if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + ' Jt';
                if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + ' Rb';
                return 'Rp ' + val.toLocaleString('id-ID');
            };

            new ApexCharts(document.querySelector('#trendChart'), {
                series: [
                    { name: 'Pemasukan', data: trendData.map(i => i.income) },
                    { name: 'Pengeluaran', data: trendData.map(i => i.expense) },
                ],
                colors: ['#15803d', '#b91c1c'],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: { show: false },
                    animations: { enabled: false },
                    fontFamily: 'inherit',
                    zoom: { enabled: false },
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2.5 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.18,
                        opacityTo: 0.02,
                        stops: [0, 95, 100],
                    },
                },
                grid: {
                    borderColor: '#f3f4f6',
                    strokeDashArray: 4,
                    yaxis: { lines: { show: true } },
                    xaxis: { lines: { show: false } },
                },
                markers: { size: 3, strokeWidth: 0, hover: { size: 5 } },
                xaxis: {
                    categories: trendData.map(i => i.date),
                    labels: {
                        formatter: fmtDate,
                        style: { fontSize: '11px', colors: '#9ca3af' },
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        formatter: fmtMoney,
                        style: { fontSize: '11px', colors: '#9ca3af' },
                    },
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: { formatter: val => 'Rp ' + val.toLocaleString('id-ID') },
                },
                legend: { show: false },
            }).render();
        }
    });
</script>
@endpush
