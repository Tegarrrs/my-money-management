<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Services\Budget\BudgetOverviewService;
use App\Services\Report\ReportExcelExporter;
use App\Services\Report\ReportQuery;
use App\Services\Report\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly BudgetOverviewService $budgetOverviewService,
        private readonly ReportQuery $reportQuery,
        private readonly ReportExcelExporter $excelExporter,
    ) {}

    public function index(ReportFilterRequest $request): View
    {
        $startDate = $request->getStartDate()->toDateString();
        $endDate = $request->getEndDate()->toDateString();
        $userId = $request->user()->id;

        $report = $this->reportService->generateReport($startDate, $endDate, $userId);
        $start = $request->getStartDate();
        $end = $request->getEndDate();
        $periodDays = (int) $start->copy()->startOfDay()
            ->diffInDays($end->copy()->startOfDay()) + 1;
        $previousEnd = $start->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($periodDays - 1)->startOfDay();
        $previousSummary = $this->reportQuery->getSummary(
            $previousStart->toDateString(),
            $previousEnd->toDateString(),
            $userId,
        );
        $currentSummary = $report->summary->toArray();
        $comparison = [
            'income' => $this->percentageChange($currentSummary['income'], $previousSummary->income),
            'expense' => $this->percentageChange($currentSummary['expense'], $previousSummary->expense),
            'balance' => $this->percentageChange($currentSummary['balance'], $previousSummary->balance),
            'previous' => [
                'income' => $previousSummary->income,
                'expense' => $previousSummary->expense,
                'balance' => $previousSummary->balance,
            ],
            'period_label' => $previousStart->translatedFormat('d M Y').' – '.$previousEnd->translatedFormat('d M Y'),
        ];
        $regularTransactions = $report->transactions
            ->filter(fn ($transaction) => ! $transaction->is_balance_adjustment && is_null($transaction->transfer_group_id));
        $expenseTransactions = $regularTransactions->where('amount', '<', 0);
        $categorizedExpenseCount = $expenseTransactions->whereNotNull('category_id')->count();
        $dataQuality = [
            'transaction_count' => $regularTransactions->count(),
            'categorized_percentage' => $expenseTransactions->isEmpty()
                ? null
                : (int) round(($categorizedExpenseCount / $expenseTransactions->count()) * 100),
            'period_days' => $periodDays,
            'confidence' => match (true) {
                $regularTransactions->count() >= 20 => 'high',
                $regularTransactions->count() >= 5 => 'medium',
                default => 'low',
            },
        ];
        $budgetSummary = $start->isSameMonth($end)
            ? $this->budgetOverviewService->forMonth($request->user(), $start)
            : null;

        return view('report.index', [
            'report' => $report->toArray(),
            'start_date' => $request->getStartDate()->format('Y-m-d'),
            'end_date' => $request->getEndDate()->format('Y-m-d'),
            'budgetSummary' => $budgetSummary,
            'selectedPreset' => $request->selectedPreset(),
            'comparison' => $comparison,
            'dataQuality' => $dataQuality,
        ]);
    }

    public function downloadExcel(ReportFilterRequest $request): StreamedResponse
    {
        $startDate = $request->getStartDate()->toDateString();
        $endDate = $request->getEndDate()->toDateString();
        $userId = $request->user()->id;
        $summary = $this->reportQuery->getSummary($startDate, $endDate, $userId);
        $transactions = $this->reportQuery->getDetailedTransactions($startDate, $endDate, $userId);
        $spreadsheet = $this->excelExporter->make(
            $request->user(),
            $startDate,
            $endDate,
            $summary,
            $transactions,
        );
        $filename = "laporan_dompetra_{$startDate}_sd_{$endDate}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function analyze(ReportFilterRequest $request): RedirectResponse
    {
        $report = $this->reportService->generateReport(
            $request->getStartDate()->toDateString(),
            $request->getEndDate()->toDateString(),
            $request->user()->id,
            requestAiAnalysis: true,
        );
        $status = $report->aiInsight['status'] ?? 'request_failed';
        $redirect = redirect()->route('report.index', $this->reportParameters($request));

        return match ($status) {
            'ready' => $redirect->with('success', 'Analisis Gemini berhasil diperbarui untuk periode terpilih.'),
            'not_configured' => $redirect->with('error', 'Gemini AI belum dikonfigurasi pada aplikasi.'),
            'no_data' => $redirect->with('error', 'Belum ada transaksi yang dapat dianalisis pada periode ini.'),
            default => $redirect->with('error', 'Gemini tidak dapat dihubungi. Analisis lokal tetap tersedia.'),
        };
    }

    public function downloadPdf(ReportFilterRequest $request): Response
    {
        $startDate = $request->getStartDate()->toDateString();
        $endDate = $request->getEndDate()->toDateString();
        $userId = $request->user()->id;
        $summary = $this->reportQuery->getSummary($startDate, $endDate, $userId);
        $transactions = $this->reportQuery->getDetailedTransactions($startDate, $endDate, $userId);
        $firstPageTransactions = $transactions->take(11);
        $remainingTransactionPages = $transactions->slice(11)->chunk(15);
        $filename = "laporan_dompetra_{$startDate}_sd_{$endDate}.pdf";

        $pdf = Pdf::loadView('report.exports.pdf', [
            'user' => $request->user(),
            'startDate' => $request->getStartDate(),
            'endDate' => $request->getEndDate(),
            'summary' => $summary,
            'transactions' => $transactions,
            'firstPageTransactions' => $firstPageTransactions,
            'remainingTransactionPages' => $remainingTransactionPages,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'landscape');
        $pdf->render();
        $domPdf = $pdf->getDomPDF();
        $font = $domPdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $domPdf->getCanvas()->page_text(
            735,
            568,
            'Halaman {PAGE_NUM} dari {PAGE_COUNT}',
            $font,
            7,
            [0.58, 0.64, 0.72],
        );

        return $pdf->download($filename);
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.01) {
            return null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    private function reportParameters(ReportFilterRequest $request): array
    {
        if ($request->selectedPreset() === 'custom') {
            return [
                'preset' => 'custom',
                'start_date' => $request->getStartDate()->toDateString(),
                'end_date' => $request->getEndDate()->toDateString(),
            ];
        }

        return ['preset' => $request->selectedPreset()];
    }
}
