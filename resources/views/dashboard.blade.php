@extends('layouts.main')

@section('content')
    <!-- PAGE: DASBOR -->
    <div id="page-dashboard" class="page-view active">

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="summary-card card-balance">
                    <div class="card-icon ic-balance"><i class="bi bi-wallet2"></i></div>
                    <div class="card-label">Total Saldo</div>
                    <div class="card-amount">Rp24.580.000</div>
                    <div class="card-meta"><span class="card-change up"><i class="bi bi-arrow-up-right"></i>
                            8,2%</span> dari bulan lalu</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="summary-card card-income">
                    <div class="card-icon ic-income"><i class="bi bi-arrow-down-left-circle-fill"></i></div>
                    <div class="card-label">Total Pemasukan</div>
                    <div class="card-amount" style="color:var(--color-income)">Rp8.500.000</div>
                    <div class="card-meta"><span class="card-change up"><i class="bi bi-arrow-up-right"></i>
                            3,1%</span> dari bulan lalu</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="summary-card card-expense">
                    <div class="card-icon ic-expense"><i class="bi bi-arrow-up-right-circle-fill"></i></div>
                    <div class="card-label">Total Pengeluaran</div>
                    <div class="card-amount" style="color:var(--color-expense)">Rp5.230.000</div>
                    <div class="card-meta"><span class="card-change down"><i class="bi bi-arrow-down-right"></i> 5,4%</span>
                        dari bulan lalu</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="summary-card card-savings">
                    <div class="card-icon ic-savings"><i class="bi bi-piggy-bank-fill"></i></div>
                    <div class="card-label">Tabungan Bersih</div>
                    <div class="card-amount" style="color:var(--color-savings)">Rp3.270.000</div>
                    <div class="card-meta"><span class="card-change up"><i class="bi bi-arrow-up-right"></i>
                            12,0%</span> dari bulan lalu</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="chart-card" style="min-height:320px;">
                    <div class="chart-card-header">
                        <div>
                            <div class="chart-title">Pemasukan vs Pengeluaran</div>
                            <div class="chart-subtitle">Tren bulanan — Jan s.d. Jun 2025</div>
                        </div>
                        <div class="chart-legend">
                            <span class="legend-item"><span class="legend-dot"
                                    style="background:var(--color-income)"></span>Pemasukan</span>
                            <span class="legend-item"><span class="legend-dot"
                                    style="background:var(--color-expense)"></span>Pengeluaran</span>
                        </div>
                    </div>
                    <div id="chart-line" style="height:240px;"></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card" style="min-height:320px;">
                    <div class="chart-card-header">
                        <div>
                            <div class="chart-title">Pengeluaran per Kategori</div>
                            <div class="chart-subtitle">Juni 2025</div>
                        </div>
                    </div>
                    <div id="chart-donut" style="height:240px;"></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="chart-card" style="min-height:280px;">
                    <div class="chart-card-header">
                        <div>
                            <div class="chart-title">Pengeluaran Mingguan</div>
                            <div class="chart-subtitle">Bulan berjalan per minggu</div>
                        </div>
                    </div>
                    <div id="chart-bar" style="height:200px;"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card" style="min-height:280px;">
                    <div class="chart-card-header">
                        <div>
                            <div class="chart-title">Kategori Teratas</div>
                            <div class="chart-subtitle">Diurutkan berdasarkan jumlah · Juni 2025</div>
                        </div>
                    </div>
                    <div id="top-categories-list" style="padding-top:4px;"></div>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2 class="section-title">Transaksi Terbaru</h2>
            <a href="#" class="section-link" onclick="navigateTo('transaksi');return false;">Lihat semua <i
                    class="bi bi-arrow-right"></i></a>
        </div>
        <div class="table-card">
            <div class="table-responsive">
                <table class="table dompetra-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Kategori</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody id="recent-transactions-body"></tbody>
                </table>
            </div>
        </div>

    </div>
@endsection