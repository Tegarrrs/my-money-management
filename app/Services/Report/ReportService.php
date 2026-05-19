<?php

namespace App\Services\Report;

use App\DTO\Report\CategoryBreakdownDTO;
use App\DTO\Report\ReportResultDTO;
use App\DTO\Report\SummaryDTO;
use App\DTO\Report\TrendDTO;

class ReportService
{
    public function __construct(
        private readonly ReportQuery $reportQuery
    ) {}

    public function generateReport(string $start, string $end, ?int $userId = null): ReportResultDTO
    {
        $summaryData = $this->reportQuery->getSummary($start, $end, $userId);
        
        $summaryDTO = new SummaryDTO(
            income: $summaryData->income,
            expense: $summaryData->expense,
            balance: $summaryData->balance
        );

        $breakdownData = $this->reportQuery->getCategoryBreakdown($start, $end, $userId);
        $totalExpense = $summaryDTO->expense;
        
        $categoryBreakdownDTOs = [];
        foreach ($breakdownData as $item) {
            $total = (float) $item->total;
            $percentage = $totalExpense > 0 ? round(($total / $totalExpense) * 100, 2) : 0;
            $categoryName = $item->category ? $item->category->name : 'Tanpa Kategori';
            
            $categoryBreakdownDTOs[] = new CategoryBreakdownDTO(
                category: $categoryName,
                total: $total,
                percentage: $percentage
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

        return new ReportResultDTO(
            summary: $summaryDTO,
            categoryBreakdown: $categoryBreakdownDTOs,
            trend: $trendDTOs
        );
    }
}
