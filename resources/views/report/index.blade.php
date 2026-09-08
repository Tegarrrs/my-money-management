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
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Filter ──────────────────────────────────────────────────── --}}
    @php
        $downloadParameters = $selectedPreset === 'custom'
            ? ['preset' => 'custom', 'start_date' => $start_date, 'end_date' => $end_date]
            : ['preset' => $selectedPreset];
    @endphp
    <div class="report-preset-panel mb-3">
        <div class="report-preset-heading">
            <div>
                <span>Preset periode</span>
                <strong>Pilih rentang waktu dengan cepat</strong>
            </div>
            <div class="report-preset-actions">
                <div class="dropdown">
                    <button type="button" class="report-download-button dropdown-toggle" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="bi bi-download"></i>
                        <span>Unduh laporan</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end report-download-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route('report.download.pdf', $downloadParameters) }}">
                                <span class="download-format-icon pdf"><i class="bi bi-file-earmark-pdf"></i></span>
                                <span>
                                    <strong>Dokumen PDF</strong>
                                    <small>Siap cetak dan dibagikan</small>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('report.download.excel', $downloadParameters) }}">
                                <span class="download-format-icon excel"><i class="bi bi-file-earmark-excel"></i></span>
                                <span>
                                    <strong>Workbook Excel</strong>
                                    <small>Dapat diedit dan untuk backup</small>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="report-preset-list">
            @foreach([
                'this_month' => 'Bulan Ini',
                'last_month' => 'Bulan Lalu',
                'last_3_months' => '3 Bulan',
                'last_6_months' => '6 Bulan',
                'year_to_date' => 'Tahun Ini',
            ] as $preset => $label)
                <a href="{{ route('report.index', ['preset' => $preset]) }}"
                    class="{{ $selectedPreset === $preset ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
            <button type="button" class="{{ $selectedPreset === 'custom' ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#customReportPeriod">
                <i class="bi bi-calendar3"></i> Kustom
            </button>
        </div>
    </div>

    <form action="{{ route('report.index') }}" method="GET"
        class="report-filter mb-3 collapse {{ $selectedPreset === 'custom' ? 'show' : '' }}" id="customReportPeriod">
        <input type="hidden" name="preset" value="custom">
        <div class="filter-label">Rentang tanggal kustom</div>
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
                <a href="{{ route('report.index', ['preset' => 'this_month']) }}" class="btn-rp btn-rp-default">Reset</a>
            </div>
        </div>
    </form>

    {{-- ── Summary Cards ───────────────────────────────────────────── --}}
    {{-- ── Summary Cards Component ── --}}
    @include('report.components.summary', [
        'summary' => $report['summary'],
        'transactionCount' => count($report['transactions']),
    ])

    @include('report.components.comparison', [
        'comparison' => $comparison,
        'dataQuality' => $dataQuality,
        'startDate' => $start_date,
        'endDate' => $end_date,
    ])

    @if($budgetSummary)
        @include('report.components.budget', ['budgetSummary' => $budgetSummary])
    @endif

    {{-- ── AI Summary + Largest Expenses ── --}}
    @include('report.components.insights', [
        'insight' => $report['ai_insight'],
        'largestExpenses' => $report['largest_expenses'],
        'analysisParameters' => $downloadParameters,
        'transactionCount' => count($report['transactions']),
        'aiConfigured' => filled(config('services.gemini.api_key')),
    ])

    @include('report.components.unusual-transactions', [
        'unusualTransactions' => $report['unusual_transactions'],
    ])

    {{-- ── Category Chart + Table Component ── --}}
    @include('report.components.category', ['categoryBreakdown' => $report['category_breakdown']])

    {{-- ── Trend Chart Component ── --}}
    @include('report.components.trend', ['trend' => $report['trend']])

    {{-- ── Detailed Transactions Component ── --}}
    @include('report.components.details', ['transactions' => $report['transactions']])

@endsection
