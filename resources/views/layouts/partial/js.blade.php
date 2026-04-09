<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.53.0/dist/apexcharts.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.7/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    'use strict';

    /* ========================================================
     *  UTILS
     * ======================================================== */
    function formatRupiah(v) { return 'Rp' + Math.abs(v).toLocaleString('id-ID'); }
    function formatRupiahChart(v) { return 'Rp' + (Math.abs(v) / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt'; }
    function setDateRange(l) { const e = document.getElementById('dateRangeLabel'); if (e) e.textContent = l; }

    /* ========================================================
     *  CHARTS (placeholders — connect to API when ready)
     * ======================================================== */
    const CB = {
        chart: { toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans',system-ui,sans-serif", animations: { enabled: true, speed: 500 } },
        grid: { borderColor: '#eef0f3', strokeDashArray: 4, padding: { left: 4, right: 4 } },
        tooltip: { theme: 'light', style: { fontSize: '12px' } }
    };

    function initLineChart(data) {
        const el = document.getElementById('chart-line');
        if (!el || !data) return;
        new ApexCharts(el, {
            ...CB,
            chart: { ...CB.chart, type: 'line', height: 240 },
            series: [
                { name: 'Pemasukan',    data: data.income },
                { name: 'Pengeluaran',  data: data.expense }
            ],
            colors: ['#15803d', '#b91c1c'],
            stroke: { curve: 'smooth', width: 2.5 },
            markers: { size: 4, strokeWidth: 2, strokeColors: '#fff', hover: { size: 6 } },
            xaxis: { categories: data.labels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '12px', colors: '#9ca3af', fontWeight: 600 } } },
            yaxis: { labels: { style: { fontSize: '11px', colors: '#9ca3af' }, formatter: v => formatRupiahChart(v) } },
            legend: { show: false },
            tooltip: { ...CB.tooltip, y: { formatter: v => formatRupiah(v) } },
        }).render();
    }

    /* ========================================================
     *  SIDEBAR — Collapse (Desktop) + Mobile Offcanvas
     * ======================================================== */
    document.addEventListener('DOMContentLoaded', function () {

        const sidebar        = document.getElementById('sidebar');
        const overlay        = document.getElementById('sidebarOverlay');
        const collapseBtn    = document.getElementById('sidebarCollapseBtn');
        const mobileMenuBtn  = document.getElementById('mobileMenuBtn');
        const COLLAPSED_KEY  = 'sidebar_collapsed';

        // Restore desktop collapsed state
        if (localStorage.getItem(COLLAPSED_KEY) === '1') {
            document.body.classList.add('sidebar-collapsed');
        }

        // Desktop collapse toggle
        if (collapseBtn) {
            collapseBtn.addEventListener('click', function () {
                document.body.classList.toggle('sidebar-collapsed');
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem(COLLAPSED_KEY, isCollapsed ? '1' : '0');
            });
        }

        // Mobile open
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function () {
                sidebar && sidebar.classList.add('sidebar-open');
                overlay && overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        }

        // Close via overlay
        if (overlay) {
            overlay.addEventListener('click', closeMobileSidebar);
        }

        function closeMobileSidebar() {
            sidebar && sidebar.classList.remove('sidebar-open');
            overlay && overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMobileSidebar();
        });

        /* --------------------------------------------------------
         *  Auto-dismiss flash alerts after 4s
         * ------------------------------------------------------ */
        document.querySelectorAll('.alert.alert-success, .alert.alert-danger').forEach(function (alert) {
            setTimeout(function () {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            }, 4000);
        });

        /* --------------------------------------------------------
         *  Pagination buttons (legacy UI style)
         * ------------------------------------------------------ */
        document.querySelectorAll('.dp-pagination .page-btn').forEach(function (btn) {
            if (btn.querySelector('i')) return;
            btn.addEventListener('click', function () {
                document.querySelectorAll('.dp-pagination .page-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

    });

    /* ========================================================
     *  Toast helper (kept for JS usage)
     * ======================================================== */
    function showToast(message, type = 'success') {
        const stack = document.getElementById('toastStack'); if (!stack) return;
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
        const t = document.createElement('div');
        t.className = `dp-toast ${type}`;
        t.innerHTML = `<i class="bi ${icon} toast-icon"></i><span class="toast-msg">${message}</span><i class="bi bi-x toast-close" onclick="this.parentElement.remove()"></i>`;
        stack.appendChild(t);
        setTimeout(() => {
            t.style.cssText += 'opacity:0;transform:translateY(8px);transition:opacity .25s ease,transform .25s ease;';
            setTimeout(() => t.remove(), 300);
        }, 3500);
    }
</script>
@stack('js')