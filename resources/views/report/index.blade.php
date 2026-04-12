@extends('layouts.main')

@section('title', 'Laporan')
@section('subtitle', 'Analisis keuangan periode tertentu')



@section('content')

    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Filter ──────────────────────────────────────────────────── --}}
    <form action="{{ route('report.index') }}" method="GET" class="report-filter mb-3">
        <div class="filter-label">Periode laporan</div>
        <div class="filter-row">
            <div class="filter-group">
                <label for="start_date">Tanggal mulai</label>
                <input type="date" id="start_date" name="start_date" value="{{ $start_date ?? request('start_date') }}">
            </div>
            <div class="filter-group">
                <label for="end_date">Tanggal akhir</label>
                <input type="date" id="end_date" name="end_date" value="{{ $end_date ?? request('end_date') }}">
            </div>
            <div style="display:flex;gap:6px;align-items:flex-end;flex-shrink:0;">
                <button type="submit" class="btn-rp btn-rp-primary">
                    <i class="bi bi-funnel-fill"></i> Tampilkan
                </button>
                <a href="{{ route('report.index') }}" class="btn-rp btn-rp-default">Reset</a>
            </div>
        </div>
    </form>

    {{-- ── Summary Cards ───────────────────────────────────────────── --}}
    {{-- ── Summary Cards Component ── --}}
    @include('report.components.summary', ['summary' => $report['summary']])

    {{-- ── Category Chart + Table Component ── --}}
    @include('report.components.category', ['categoryBreakdown' => $report['category_breakdown']])

    {{-- ── Trend Chart Component ── --}}
    @include('report.components.trend', ['trend' => $report['trend']])

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush