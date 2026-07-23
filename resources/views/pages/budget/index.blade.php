@extends('layouts.main')

@section('title', 'Anggaran')
@section('subtitle', 'Rencanakan batas pengeluaran dan pantau realisasinya')

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/budget.css') }}">
@endpush

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-warning alert-dismissible fade show mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="budget-toolbar">
        <div class="budget-month-nav">
            <a href="{{ route('budget.index', ['month' => $previousMonth]) }}" aria-label="Bulan sebelumnya">
                <i class="bi bi-chevron-left"></i>
            </a>
            <div>
                <span>Periode anggaran</span>
                <strong>{{ $month->translatedFormat('F Y') }}</strong>
            </div>
            <a href="{{ route('budget.index', ['month' => $nextMonth]) }}" aria-label="Bulan berikutnya">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        <form method="POST" action="{{ route('budget.copy-previous') }}">
            @csrf
            <input type="hidden" name="period" value="{{ $month->format('Y-m') }}">
            <button class="btn-rp btn-rp-default" type="submit">
                <i class="bi bi-copy"></i> Salin bulan lalu
            </button>
        </form>
    </div>

    <section class="budget-summary-grid">
        <article class="budget-summary-card primary">
            <span>Total anggaran</span>
            <strong>Rp {{ number_format($overview['total_budget'], 0, ',', '.') }}</strong>
            <small>{{ $overview['items']->count() }} kategori dianggarkan</small>
        </article>
        <article class="budget-summary-card spent">
            <span>Sudah terpakai</span>
            <strong>Rp {{ number_format($overview['total_spent'], 0, ',', '.') }}</strong>
            <small>{{ number_format($overview['percentage'], 1, ',', '.') }}% dari anggaran</small>
        </article>
        <article class="budget-summary-card {{ $overview['total_remaining'] < 0 ? 'danger' : 'remaining' }}">
            <span>{{ $overview['total_remaining'] < 0 ? 'Melebihi anggaran' : 'Sisa anggaran' }}</span>
            <strong>Rp {{ number_format(abs($overview['total_remaining']), 0, ',', '.') }}</strong>
            <small>{{ $overview['over_budget_count'] }} kategori melewati batas</small>
        </article>
        <article class="budget-summary-card projection">
            <span>Proyeksi akhir bulan</span>
            <strong>Rp {{ number_format($overview['projected_spent'], 0, ',', '.') }}</strong>
            <small>
                @if(!$overview['has_budgets'])
                    Buat anggaran untuk melihat perbandingan
                @elseif($overview['projected_difference'] >= 0)
                    Diperkirakan tersisa Rp {{ number_format($overview['projected_difference'], 0, ',', '.') }}
                @else
                    Berpotensi lebih Rp {{ number_format(abs($overview['projected_difference']), 0, ',', '.') }}
                @endif
            </small>
        </article>
    </section>

    @if($overview['unbudgeted_spending'] > 0)
        <a class="budget-unbudgeted-alert" href="{{ route('transaction.index') }}">
            <i class="bi bi-exclamation-circle"></i>
            <span>
                <strong>Rp {{ number_format($overview['unbudgeted_spending'], 0, ',', '.') }} belum tercakup anggaran</strong>
                <small>Pengeluaran berasal dari kategori yang belum diberi batas atau transaksi tanpa kategori.</small>
            </span>
            <i class="bi bi-arrow-right"></i>
        </a>
    @endif

    <div class="budget-editor-card">
        <div class="budget-editor-header">
            <div>
                <span class="budget-eyebrow">Anggaran per kategori</span>
                <h2>Atur batas pengeluaran</h2>
                <p>Isi nol atau kosongkan nilai untuk menghapus anggaran kategori tersebut.</p>
            </div>
            <div class="budget-legend">
                <span><i class="safe"></i>Aman</span>
                <span><i class="warning"></i>Perhatian</span>
                <span><i class="exceeded"></i>Melebihi</span>
            </div>
        </div>

        @if($categories->isEmpty())
            <div class="budget-empty">
                <i class="bi bi-tags"></i>
                <h3>Belum ada kategori pengeluaran</h3>
                <p>Buat kategori terlebih dahulu agar anggaran dapat dialokasikan.</p>
                <a href="{{ route('category.index') }}" class="btn-rp btn-rp-primary">Kelola kategori</a>
            </div>
        @else
            <form method="POST" action="{{ route('budget.sync') }}">
                @csrf
                <input type="hidden" name="period" value="{{ $month->format('Y-m') }}">

                <div class="budget-list">
                    @foreach($categories as $category)
                        @php
                            $item = $budgetByCategory->get($category->id);
                            $status = $item['status'] ?? 'safe';
                            $spent = $item['spent'] ?? 0;
                            $limit = $item['limit'] ?? 0;
                            $percentage = $item['percentage'] ?? 0;
                            $barPercentage = $item['bar_percentage'] ?? 0;
                            $threshold = $item ? $item['budget']->warning_threshold : 80;
                        @endphp
                        <div class="budget-row {{ $item ? 'configured' : '' }}">
                            <input type="hidden" name="budgets[{{ $loop->index }}][category_id]" value="{{ $category->id }}">
                            <div class="budget-category">
                                <span class="budget-category-icon" style="--category-color:{{ $category->color ?? '#6b7280' }}">
                                    <i class="bi {{ $category->icon ?? 'bi-tag' }}"></i>
                                </span>
                                <div>
                                    <strong>{{ $category->name }}</strong>
                                    <span>Terpakai Rp {{ number_format($spent, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="budget-reality">
                                <div class="budget-reality-label">
                                    <span>{{ $limit > 0 ? number_format($percentage, 1, ',', '.').'%' : 'Belum diatur' }}</span>
                                    @if($item)
                                        <b class="{{ $status }}">
                                            {{ match($status) { 'exceeded' => 'Melebihi', 'warning' => 'Perhatian', default => 'Aman' } }}
                                        </b>
                                    @endif
                                </div>
                                <div class="budget-progress {{ $status }}">
                                    <span style="width:{{ $barPercentage }}%"></span>
                                </div>
                                <small>
                                    @if($item && $item['remaining'] >= 0)
                                        Sisa Rp {{ number_format($item['remaining'], 0, ',', '.') }}
                                    @elseif($item)
                                        Lebih Rp {{ number_format(abs($item['remaining']), 0, ',', '.') }}
                                    @else
                                        Realisasi akan dihitung otomatis dari transaksi
                                    @endif
                                </small>
                            </div>
                            <div class="budget-inputs">
                                <label>
                                    <span>Batas bulanan</span>
                                    <div class="budget-money-input">
                                        <i>Rp</i>
                                        <input type="number" min="0" step="1000"
                                            name="budgets[{{ $loop->index }}][amount]"
                                            value="{{ old("budgets.{$loop->index}.amount", $limit > 0 ? (int) $limit : '') }}"
                                            placeholder="0">
                                    </div>
                                </label>
                                <label>
                                    <span>Ingatkan</span>
                                    <select name="budgets[{{ $loop->index }}][warning_threshold]">
                                        @foreach([70, 80, 90, 95] as $option)
                                            <option value="{{ $option }}" {{ (int) $threshold === $option ? 'selected' : '' }}>{{ $option }}%</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="budget-save-bar">
                    <span><i class="bi bi-info-circle"></i> Perubahan hanya berlaku untuk {{ $month->translatedFormat('F Y') }}.</span>
                    <button type="submit" class="btn-rp btn-rp-primary">
                        <i class="bi bi-check-lg"></i> Simpan anggaran
                    </button>
                </div>
            </form>
        @endif
    </div>
@endsection
