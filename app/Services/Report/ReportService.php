<?php

namespace App\Services\Report;

use App\DTO\Report\CategoryBreakdownDTO;
use App\DTO\Report\ReportResultDTO;
use App\DTO\Report\SummaryDTO;
use App\DTO\Report\TrendDTO;

class ReportService
{
    public function __construct(
        private readonly ReportQuery $reportQuery,
        private readonly GeminiReportAnalyzer $reportAnalyzer,
        private readonly UnusualTransactionDetector $unusualTransactionDetector,
    ) {}

    public function generateReport(
        string $start,
        string $end,
        ?int $userId = null,
        bool $requestAiAnalysis = false,
    ): ReportResultDTO {
        $summaryData = $this->reportQuery->getSummary($start, $end, $userId);

        $summaryDTO = new SummaryDTO(
            income: $summaryData->income,
            expense: $summaryData->expense,
            balance: $summaryData->balance
        );

        $detailedTransactions = $this->reportQuery->getDetailedTransactions($start, $end, $userId);
        $expenseTransactions = $detailedTransactions
            ->filter(fn ($transaction) => $transaction->amount < 0
                && is_null($transaction->transfer_group_id)
                && ! $transaction->is_balance_adjustment);

        $breakdownData = $this->reportQuery->getCategoryBreakdown($start, $end, $userId);
        $totalExpense = $summaryDTO->expense;

        $categoryBreakdownDTOs = [];
        foreach ($breakdownData as $item) {
            $total = (float) $item->total;
            $percentage = $totalExpense > 0 ? round(($total / $totalExpense) * 100, 2) : 0;
            $categoryName = $item->category ? $item->category->name : 'Tanpa Kategori';
            $categoryTransactions = $expenseTransactions
                ->filter(fn ($transaction) => $transaction->category_id === $item->category_id)
                ->sortByDesc(fn ($transaction) => abs((float) $transaction->amount))
                ->values();

            $categoryBreakdownDTOs[] = new CategoryBreakdownDTO(
                category: $categoryName,
                total: $total,
                percentage: $percentage,
                transactionCount: $categoryTransactions->count(),
                transactions: $categoryTransactions->map(fn ($transaction) => [
                    'id' => $transaction->id,
                    'description' => $transaction->description ?: 'Tanpa deskripsi',
                    'detail' => $transaction->detail,
                    'amount' => abs((float) $transaction->amount),
                    'date' => $transaction->transaction_date->format('Y-m-d'),
                    'formatted_date' => $transaction->transaction_date->translatedFormat('d M Y'),
                    'wallet' => $transaction->wallet?->name ?? 'Tanpa dompet',
                ])->all(),
            );
        }

        $trendData = $this->reportQuery->getTrend($start, $end, $userId);
        $trendDTOs = [];
        foreach ($trendData as $item) {
            $trendDTOs[] = new TrendDTO(
                date: $item->date,
                income: (float) $item->income,
                expense: (float) $item->expense
            );
        }

        $largestExpenses = $expenseTransactions
            ->sortByDesc(fn ($transaction) => abs((float) $transaction->amount))
            ->take(5)
            ->values()
            ->map(fn ($transaction) => [
                'id' => $transaction->id,
                'description' => $transaction->description ?: 'Tanpa deskripsi',
                'detail' => $transaction->detail,
                'category' => $transaction->category?->name ?? 'Tanpa Kategori',
                'wallet' => $transaction->wallet?->name ?? 'Tanpa dompet',
                'amount' => abs((float) $transaction->amount),
                'date' => $transaction->transaction_date->format('Y-m-d'),
                'formatted_date' => $transaction->transaction_date->translatedFormat('d M Y'),
            ])->all();

        $analysisData = [
            'period' => ['start' => $start, 'end' => $end],
            'summary' => $summaryDTO->toArray(),
            'transaction_count' => $detailedTransactions
                ->where('is_balance_adjustment', false)
                ->count(),
            'expense_transaction_count' => $expenseTransactions->count(),
            'categories' => array_map(fn ($category) => [
                'category' => $category->category,
                'total' => $category->total,
                'percentage' => $category->percentage,
                'transaction_count' => $category->transactionCount,
            ], $categoryBreakdownDTOs),
            'largest_expenses' => array_map(fn ($expense) => [
                'date' => $expense['date'],
                'description' => $expense['description'],
                'category' => $expense['category'],
                'amount' => $expense['amount'],
            ], array_slice($largestExpenses, 0, 3)),
        ];

        $aiInsight = $requestAiAnalysis
            ? $this->reportAnalyzer->analyze($analysisData, (int) $userId, force: true)
            : $this->reportAnalyzer->cachedOrFallback($analysisData, (int) $userId);
        $unusualTransactions = $this->unusualTransactionDetector->detect($detailedTransactions);

        return new ReportResultDTO(
            summary: $summaryDTO,
            categoryBreakdown: $categoryBreakdownDTOs,
            trend: $trendDTOs,
            transactions: $detailedTransactions,
            largestExpenses: $largestExpenses,
            unusualTransactions: $unusualTransactions,
            aiInsight: $aiInsight,
        );
    }
}
