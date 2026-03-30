<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.53.0/dist/apexcharts.min.js"></script>
<script>
    'use strict';

    const CATEGORIES = {
        'Makanan & Minuman': { color: '#f59e0b', bg: '#fffbeb' },
        'Transportasi': { color: '#3b82f6', bg: '#eff6ff' },
        'Belanja': { color: '#8b5cf6', bg: '#f5f3ff' },
        'Hiburan': { color: '#ec4899', bg: '#fdf2f8' },
        'Kesehatan': { color: '#10b981', bg: '#ecfdf5' },
        'Tagihan & Utilitas': { color: '#6b7280', bg: '#f9fafb' },
        'Gaji': { color: '#15803d', bg: '#f0fdf4' },
        'Lainnya': { color: '#94a3b8', bg: '#f8fafc' },
    };

    const TRANSACTIONS = [
        { id: 1, date: '2025-06-28', desc: 'Belanja di Hero Supermarket', category: 'Makanan & Minuman', amount: -285000, type: 'pengeluaran' },
        { id: 2, date: '2025-06-27', desc: 'Gaji Bulanan — PT Teknologi Maju', category: 'Gaji', amount: 8500000, type: 'pemasukan' },
        { id: 3, date: '2025-06-26', desc: 'Grab — Kantor ke Rumah', category: 'Transportasi', amount: -42000, type: 'pengeluaran' },
        { id: 4, date: '2025-06-25', desc: 'Langganan Netflix', category: 'Hiburan', amount: -186000, type: 'pengeluaran' },
        { id: 5, date: '2025-06-24', desc: 'Apotek Kimia Farma', category: 'Kesehatan', amount: -95000, type: 'pengeluaran' },
        { id: 6, date: '2025-06-23', desc: 'Tagihan PLN', category: 'Tagihan & Utilitas', amount: -320000, type: 'pengeluaran' },
        { id: 7, date: '2025-06-22', desc: 'Makan Siang — Warung Pak Budi', category: 'Makanan & Minuman', amount: -35000, type: 'pengeluaran' },
        { id: 8, date: '2025-06-21', desc: 'Shopee — Earphone Bluetooth', category: 'Belanja', amount: -249000, type: 'pengeluaran' },
        { id: 9, date: '2025-06-20', desc: 'Tokopedia — Keyboard Mekanikal', category: 'Belanja', amount: -485000, type: 'pengeluaran' },
        { id: 10, date: '2025-06-18', desc: 'Proyek Lepas — Desain UI', category: 'Gaji', amount: 1500000, type: 'pemasukan' },
        { id: 11, date: '2025-06-17', desc: 'Kopi di Kenangan Coffee', category: 'Makanan & Minuman', amount: -48000, type: 'pengeluaran' },
        { id: 12, date: '2025-06-16', desc: 'Kartu KRL Bulanan', category: 'Transportasi', amount: -100000, type: 'pengeluaran' },
        { id: 13, date: '2025-06-14', desc: 'Iuran Gym — FIT Indonesia', category: 'Kesehatan', amount: -450000, type: 'pengeluaran' },
        { id: 14, date: '2025-06-12', desc: 'Makan Malam — BOCA Restaurant', category: 'Makanan & Minuman', amount: -320000, type: 'pengeluaran' },
        { id: 15, date: '2025-06-10', desc: 'Tagihan PDAM', category: 'Tagihan & Utilitas', amount: -85000, type: 'pengeluaran' },
        { id: 16, date: '2025-06-08', desc: 'Buku: Clean Code', category: 'Belanja', amount: -175000, type: 'pengeluaran' },
        { id: 17, date: '2025-06-06', desc: 'Ojek Online — ke Pasar', category: 'Transportasi', amount: -25000, type: 'pengeluaran' },
        { id: 18, date: '2025-06-05', desc: 'Langganan Spotify Premium', category: 'Hiburan', amount: -69000, type: 'pengeluaran' },
        { id: 19, date: '2025-06-04', desc: "Makan Siang — McDonald's", category: 'Makanan & Minuman', amount: -78000, type: 'pengeluaran' },
        { id: 20, date: '2025-06-02', desc: 'Tagihan Internet — MyRepublic', category: 'Tagihan & Utilitas', amount: -495000, type: 'pengeluaran' },
        { id: 21, date: '2025-06-01', desc: 'Honor Konsultasi — PT Solusi Digital', category: 'Gaji', amount: 2000000, type: 'pemasukan' },
        { id: 22, date: '2025-05-30', desc: 'Belanja Bulanan — Hypermart', category: 'Makanan & Minuman', amount: -750000, type: 'pengeluaran' },
        { id: 23, date: '2025-05-28', desc: 'Taksi — Transfer Bandara', category: 'Transportasi', amount: -185000, type: 'pengeluaran' },
        { id: 24, date: '2025-05-25', desc: 'Premi Asuransi Kesehatan', category: 'Kesehatan', amount: -350000, type: 'pengeluaran' },
    ];

    const MONTHLY_DATA = {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
        income: [6500000, 7200000, 6800000, 8100000, 7900000, 8500000],
        expense: [4200000, 5100000, 4800000, 5400000, 5700000, 5230000],
    };
    const WEEKLY_DATA = { labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'], amounts: [1250000, 980000, 1640000, 1360000] };
    const EXPENSE_BY_CATEGORY = [
        { label: 'Makanan & Minuman', amount: 1516000 },
        { label: 'Belanja', amount: 909000 },
        { label: 'Tagihan & Utilitas', amount: 900000 },
        { label: 'Kesehatan', amount: 895000 },
        { label: 'Transportasi', amount: 352000 },
        { label: 'Hiburan', amount: 255000 },
        { label: 'Lainnya', amount: 403000 },
    ];

    /* Format Rupiah */
    function formatRupiah(v) { return 'Rp' + Math.abs(v).toLocaleString('id-ID'); }
    function formatRupiahChart(v) { return 'Rp' + (Math.abs(v) / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt'; }
    function formatTanggal(s) { return new Date(s + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); }
    function catBadge(cat) {
        const c = CATEGORIES[cat] || CATEGORIES['Lainnya'];
        return `<span class="cat-badge" style="background:${c.bg};color:${c.color};"><span class="cat-dot" style="background:${c.color};"></span>${cat}</span>`;
    }

    /* Navigasi */
    function navigateTo(page) {
        document.querySelectorAll('.page-view').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
        const t = document.getElementById('page-' + page); if (t) t.classList.add('active');
        const l = document.querySelector(`.sidebar-nav .nav-link[data-page="${page}"]`); if (l) l.classList.add('active');
        const titles = { dashboard: 'Dasbor', transaksi: 'Transaksi' };
        const subs = { dashboard: 'Selamat datang kembali, Budi 👋', transaksi: 'Kelola semua transaksi keuanganmu' };
        document.getElementById('navbar-title').childNodes[0].textContent = titles[page] || page;
        const sub = document.getElementById('navbar-subtitle'); if (sub) sub.textContent = subs[page] || '';
    }
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.sidebar-nav .nav-link[data-page]').forEach(link => {
            link.addEventListener('click', e => { e.preventDefault(); const p = link.getAttribute('data-page'); if (p) navigateTo(p); });
        });
    });
    function setDateRange(l) { const e = document.getElementById('dateRangeLabel'); if (e) e.textContent = l; }

    /* Render Tables */
    function renderRecentTransactions() {
        const tb = document.getElementById('recent-transactions-body'); if (!tb) return;
        tb.innerHTML = TRANSACTIONS.slice(0, 8).map(tx => {
            const inc = tx.type === 'pemasukan';
            return `<tr><td><span class="tx-date">${formatTanggal(tx.date)}</span></td><td><span class="tx-desc">${tx.desc}</span></td><td>${catBadge(tx.category)}</td><td class="amount-cell ${inc ? 'amount-income' : 'amount-expense'}">${inc ? '+' : '−'} ${formatRupiah(tx.amount)}</td></tr>`;
        }).join('');
    }
    function renderAllTransactions() {
        const tb = document.getElementById('all-transactions-body'); if (!tb) return;
        tb.innerHTML = TRANSACTIONS.slice(0, 10).map(tx => {
            const inc = tx.type === 'pemasukan';
            const typeBadge = inc
                ? `<span class="cat-badge" style="background:#f0fdf4;color:#15803d;"><span class="cat-dot" style="background:#15803d;"></span>Pemasukan</span>`
                : `<span class="cat-badge" style="background:#fff1f2;color:#b91c1c;"><span class="cat-dot" style="background:#b91c1c;"></span>Pengeluaran</span>`;
            return `<tr><td><span class="tx-date">${formatTanggal(tx.date)}</span></td><td><span class="tx-desc">${tx.desc}</span></td><td>${catBadge(tx.category)}</td><td>${typeBadge}</td><td class="amount-cell ${inc ? 'amount-income' : 'amount-expense'}">${inc ? '+' : '−'} ${formatRupiah(tx.amount)}</td><td class="text-end" style="white-space:nowrap;"><button class="btn-outline-dp py-1 px-2 me-1" style="font-size:12px;" title="Edit"><i class="bi bi-pencil"></i></button><button class="btn-outline-dp py-1 px-2" style="font-size:12px;color:var(--color-expense);border-color:var(--color-expense-ring);" title="Hapus"><i class="bi bi-trash3"></i></button></td></tr>`;
        }).join('');
    }
    function renderTopCategories() {
        const el = document.getElementById('top-categories-list'); if (!el) return;
        const sorted = [...EXPENSE_BY_CATEGORY].sort((a, b) => b.amount - a.amount);
        const max = sorted[0].amount;
        el.innerHTML = sorted.map(item => {
            const c = CATEGORIES[item.label] || CATEGORIES['Lainnya'];
            const pct = Math.round((item.amount / max) * 100);
            return `<div style="margin-bottom:13px;"><div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;"><span style="font-size:13px;font-weight:600;color:var(--color-ink);">${item.label}</span><span style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--color-muted);">${formatRupiah(item.amount)}</span></div><div style="height:6px;background:#f0f2f5;border-radius:10px;overflow:hidden;"><div style="height:100%;width:${pct}%;background:${c.color};border-radius:10px;transition:width .6s ease;"></div></div></div>`;
        }).join('');
    }

    /* Charts */
    const CB = { chart: { toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans',system-ui,sans-serif", animations: { enabled: true, speed: 500 } }, grid: { borderColor: '#eef0f3', strokeDashArray: 4, padding: { left: 4, right: 4 } }, tooltip: { theme: 'light', style: { fontSize: '12px' } } };

    function initLineChart() {
        new ApexCharts(document.getElementById('chart-line'), {
            ...CB, chart: { ...CB.chart, type: 'line', height: 240 },
            series: [{ name: 'Pemasukan', data: MONTHLY_DATA.income }, { name: 'Pengeluaran', data: MONTHLY_DATA.expense }],
            colors: ['#15803d', '#b91c1c'], stroke: { curve: 'smooth', width: 2.5 },
            markers: { size: 4, strokeWidth: 2, strokeColors: '#fff', hover: { size: 6 } },
            xaxis: { categories: MONTHLY_DATA.labels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '12px', colors: '#9ca3af', fontWeight: 600 } } },
            yaxis: { labels: { style: { fontSize: '11px', colors: '#9ca3af' }, formatter: v => formatRupiahChart(v) } },
            legend: { show: false },
            tooltip: { ...CB.tooltip, y: { formatter: v => formatRupiah(v) } },
        }).render();
    }
    function initDonutChart() {
        new ApexCharts(document.getElementById('chart-donut'), {
            ...CB, chart: { ...CB.chart, type: 'donut', height: 240 },
            series: EXPENSE_BY_CATEGORY.map(c => c.amount),
            labels: EXPENSE_BY_CATEGORY.map(c => c.label),
            colors: EXPENSE_BY_CATEGORY.map(c => (CATEGORIES[c.label] || CATEGORIES['Lainnya']).color),
            legend: { position: 'bottom', fontSize: '11px', markers: { width: 7, height: 7 }, itemMargin: { horizontal: 5, vertical: 2 } },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '64%', labels: { show: true, total: { show: true, label: 'Total', color: '#9ca3af', fontSize: '11px', fontWeight: 700, formatter: () => 'Rp5,23 jt' }, value: { fontSize: '15px', fontWeight: 800, color: '#0d1117', formatter: v => formatRupiah(parseInt(v)) } } } } },
            stroke: { width: 2, colors: ['#fff'] },
            tooltip: { ...CB.tooltip, y: { formatter: v => formatRupiah(v) } },
        }).render();
    }
    function initBarChart() {
        new ApexCharts(document.getElementById('chart-bar'), {
            ...CB, chart: { ...CB.chart, type: 'bar', height: 200 },
            series: [{ name: 'Pengeluaran', data: WEEKLY_DATA.amounts }],
            colors: ['#1d6a4a'], plotOptions: { bar: { borderRadius: 5, columnWidth: '42%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: WEEKLY_DATA.labels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '12px', colors: '#9ca3af', fontWeight: 600 } } },
            yaxis: { labels: { style: { fontSize: '11px', colors: '#9ca3af' }, formatter: v => formatRupiahChart(v) } },
            tooltip: { ...CB.tooltip, y: { formatter: v => formatRupiah(v) } },
            states: { hover: { filter: { type: 'darken', value: 0.85 } } },
        }).render();
    }

    /* Modal & Toast */
    let selectedType = 'expense';
    function selectType(type) {
        selectedType = type;
        document.getElementById('typeExpense').className = 'type-btn' + (type === 'expense' ? ' expense-active' : '');
        document.getElementById('typeIncome').className = 'type-btn' + (type === 'income' ? ' income-active' : '');
    }
    function saveTransaction() {
        const m = bootstrap.Modal.getInstance(document.getElementById('addTxModal'));
        if (m) m.hide();
        showToast('Transaksi berhasil disimpan!', 'success');
    }
    function showToast(message, type = 'success') {
        const stack = document.getElementById('toastStack'); if (!stack) return;
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
        const t = document.createElement('div');
        t.className = `dp-toast ${type}`;
        t.innerHTML = `<i class="bi ${icon} toast-icon"></i><span class="toast-msg">${message}</span><i class="bi bi-x toast-close" onclick="this.parentElement.remove()"></i>`;
        stack.appendChild(t);
        setTimeout(() => { t.style.cssText += 'opacity:0;transform:translateY(8px);transition:opacity .25s ease,transform .25s ease;'; setTimeout(() => t.remove(), 300); }, 3500);
    }
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.dp-pagination .page-btn').forEach(btn => {
            if (btn.querySelector('i')) return;
            btn.addEventListener('click', () => { document.querySelectorAll('.dp-pagination .page-btn').forEach(b => b.classList.remove('active')); btn.classList.add('active'); });
        });
        renderRecentTransactions();
        renderAllTransactions();
        renderTopCategories();
        initLineChart();
        initDonutChart();
        initBarChart();
    });
</script>
@stack('js')