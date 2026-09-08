<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Dompetra</title>
    <style>
        @page { margin: 32px 34px 38px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.4;
        }
        .header {
            margin-bottom: 15px;
            padding: 18px 20px;
            border-radius: 8px;
            color: #fff;
            background: #163d2d;
        }
        .brand { font-size: 9px; font-weight: bold; letter-spacing: 1.4px; color: #a7f3d0; }
        h1 { margin: 3px 0 4px; font-size: 21px; }
        .subtitle { color: #d1fae5; font-size: 9px; }
        .header-meta { float: right; margin-top: -38px; text-align: right; color: #d1fae5; }
        .summary { width: 100%; margin-bottom: 15px; border-spacing: 8px 0; }
        .summary td { width: 33.33%; padding: 11px 13px; border-radius: 7px; }
        .summary-label { margin-bottom: 5px; font-size: 7px; font-weight: bold; letter-spacing: .7px; }
        .summary-value { font-size: 16px; font-weight: bold; }
        .income { color: #166534; background: #dcfce7; }
        .expense { color: #991b1b; background: #fee2e2; }
        .balance { color: #1e40af; background: #dbeafe; }
        .section-heading {
            margin-bottom: 0;
            padding: 9px 11px;
            color: #fff;
            background: #1d6a4a;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: .5px;
        }
        .section-heading span { float: right; color: #d1fae5; font-weight: normal; }
        table.transactions {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.transactions thead { display: table-header-group; }
        table.transactions th {
            padding: 7px 7px;
            border-bottom: 2px solid #1d6a4a;
            color: #475569;
            background: #e2e8f0;
            text-align: left;
            font-size: 7px;
            text-transform: uppercase;
        }
        table.transactions td {
            padding: 7px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        table.transactions tbody tr:nth-child(even) { background: #f8fafc; }
        .date { width: 10%; white-space: nowrap; }
        .description { width: 22%; }
        .detail { width: 20%; color: #64748b; }
        .category { width: 14%; }
        .wallet { width: 12%; }
        .type { width: 10%; }
        .amount { width: 12%; text-align: right; white-space: nowrap; font-weight: bold; }
        .type { font-size: 7px; font-weight: bold; }
        .type-income { color: #166534; }
        .type-expense { color: #991b1b; }
        .type-transfer { color: #1d4ed8; }
        .type-adjustment { color: #6d28d9; }
        .amount-income { color: #166534; }
        .amount-expense { color: #991b1b; }
        .amount-transfer { color: #1d4ed8; }
        .amount-adjustment { color: #6d28d9; }
        .empty { padding: 35px 10px !important; color: #94a3b8; text-align: center; }
        .page-break { page-break-before: always; }
        .continuation-header {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .continuation-header td {
            padding: 10px 12px;
            color: #fff;
            background: #163d2d;
        }
        .continuation-brand { font-size: 11px; font-weight: bold; }
        .continuation-period { margin-top: 2px; color: #a7f3d0; font-size: 7px; }
        .continuation-page { text-align: right; color: #d1fae5 !important; font-size: 8px; }
        .footer {
            position: fixed;
            right: 0;
            bottom: -24px;
            left: 0;
            padding-top: 7px;
            border-top: 1px solid #e5e7eb;
            color: #94a3b8;
            font-size: 7px;
        }
        .footer-right { float: right; padding-right: 100px; }
    </style>
</head>
<body>
    <div class="footer">
        Dompetra - Laporan dibuat {{ $generatedAt->translatedFormat('d M Y, H:i') }}
        <span class="footer-right">Dokumen ini dibuat otomatis dari data periode terpilih.</span>
    </div>

    <div class="header">
        <div class="brand">DOMPETRA</div>
        <h1>Laporan Keuangan</h1>
        <div class="subtitle">
            Periode {{ $startDate->translatedFormat('d M Y') }} sampai {{ $endDate->translatedFormat('d M Y') }}
        </div>
        <div class="header-meta">
            {{ $user->name }}<br>
            {{ count($transactions) }} transaksi
        </div>
    </div>

    <table class="summary">
        <tr>
            <td class="income">
                <div class="summary-label">TOTAL PEMASUKAN</div>
                <div class="summary-value">Rp {{ number_format($summary->income, 0, ',', '.') }}</div>
            </td>
            <td class="expense">
                <div class="summary-label">TOTAL PENGELUARAN</div>
                <div class="summary-value">Rp {{ number_format($summary->expense, 0, ',', '.') }}</div>
            </td>
            <td class="balance">
                <div class="summary-label">SELISIH PERIODE</div>
                <div class="summary-value">Rp {{ number_format($summary->balance, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <div class="section-heading">
        DETAIL TRANSAKSI
        <span>Urut dari transaksi terbaru</span>
    </div>
    @include('report.exports.partials.transaction-table', ['pageTransactions' => $firstPageTransactions])

    @foreach($remainingTransactionPages as $pageIndex => $pageTransactions)
        <div class="page-break"></div>
        <table class="continuation-header">
            <tr>
                <td>
                    <div class="continuation-brand">DOMPETRA - Detail Transaksi</div>
                    <div class="continuation-period">
                        {{ $startDate->translatedFormat('d M Y') }} sampai {{ $endDate->translatedFormat('d M Y') }}
                    </div>
                </td>
                <td class="continuation-page">Lanjutan {{ $pageIndex + 1 }}</td>
            </tr>
        </table>
        @include('report.exports.partials.transaction-table', ['pageTransactions' => $pageTransactions])
    @endforeach

</body>
</html>
