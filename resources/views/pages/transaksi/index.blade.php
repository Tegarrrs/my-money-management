@extends('layouts.main')

@section('title', 'Transaksi')
@section('subtitle', 'Kelola semua transaksi keuanganmu')

@section('content')
    <!-- PAGE: TRANSAKSI -->
    <div id="page-transaksi" class="page-view active">

        <div class="filter-bar">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" value="2025-06-01" />
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" value="2025-06-30" />
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label">Kategori</label>
                    <select class="form-select">
                        <option value="">Semua kategori</option>
                        <option>Makanan &amp; Minuman</option>
                        <option>Transportasi</option>
                        <option>Belanja</option>
                        <option>Hiburan</option>
                        <option>Kesehatan</option>
                        <option>Tagihan &amp; Utilitas</option>
                        <option>Gaji</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label">Jenis</label>
                    <select class="form-select">
                        <option value="">Semua jenis</option>
                        <option>Pemasukan</option>
                        <option>Pengeluaran</option>
                    </select>
                </div>
                <div class="col-sm-12 col-md-2 d-flex gap-2">
                    <button class="btn-primary-dp" onclick="showToast('Filter diterapkan','success')"><i
                            class="bi bi-funnel-fill"></i> Filter</button>
                    <button class="btn-outline-dp">Reset</button>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <h2 class="table-card-title">Semua Transaksi</h2>
                <div class="d-flex gap-2">
                    <button class="btn-outline-dp"><i class="bi bi-download"></i> Ekspor</button>
                    <button class="btn-primary-dp" data-bs-toggle="modal" data-bs-target="#addTxModal"><i
                            class="bi bi-plus-lg"></i> Tambah Transaksi</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table dompetra-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Kategori</th>
                            <th>Jenis</th>
                            <th class="text-end">Jumlah</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="all-transactions-body"></tbody>
                </table>
            </div>
            <div class="dp-pagination">
                <span class="page-info">Menampilkan 1–10 dari 24 transaksi</span>
                <div class="page-btn"><i class="bi bi-chevron-left"></i></div>
                <div class="page-btn active">1</div>
                <div class="page-btn">2</div>
                <div class="page-btn">3</div>
                <div class="page-btn"><i class="bi bi-chevron-right"></i></div>
            </div>
        </div>
    </div>
    @include('pages.transaksi.modal')
@endsection