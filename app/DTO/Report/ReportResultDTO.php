<?php

namespace App\DTO\Report;

use Illuminate\Support\Collection;

class ReportResultDTO
{
    /**
     * @param SummaryDTO $summary
     * @param CategoryBreakdownDTO[] $categoryBreakdown
     * @param TrendDTO[] $trend
     * @param Collection $transactions
     */
    public function __construct(
        public readonly SummaryDTO $summary,
        public readonly array $categoryBreakdown,
        public readonly array $trend,
        public readonly Collection $transactions = new Collection(),
    ) {}

    public function toArray(): array
    {
        return [
            'summary' => $this->summary->toArray(),
            'category_breakdown' => array_map(fn($item) => $item->toArray(), $this->categoryBreakdown),
            'trend' => array_map(fn($item) => $item->toArray(), $this->trend),
            'transactions' => $this->transactions,
        ];
    }
}
