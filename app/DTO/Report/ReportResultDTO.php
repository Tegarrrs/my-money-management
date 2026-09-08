<?php

namespace App\DTO\Report;

use Illuminate\Support\Collection;

class ReportResultDTO
{
    /**
     * @param  CategoryBreakdownDTO[]  $categoryBreakdown
     * @param  TrendDTO[]  $trend
     */
    public function __construct(
        public readonly SummaryDTO $summary,
        public readonly array $categoryBreakdown,
        public readonly array $trend,
        public readonly Collection $transactions = new Collection,
        public readonly array $largestExpenses = [],
        public readonly array $unusualTransactions = [],
        public readonly array $aiInsight = [],
    ) {}

    public function toArray(): array
    {
        return [
            'summary' => $this->summary->toArray(),
            'category_breakdown' => array_map(fn ($item) => $item->toArray(), $this->categoryBreakdown),
            'trend' => array_map(fn ($item) => $item->toArray(), $this->trend),
            'transactions' => $this->transactions,
            'largest_expenses' => $this->largestExpenses,
            'unusual_transactions' => $this->unusualTransactions,
            'ai_insight' => $this->aiInsight,
        ];
    }
}
