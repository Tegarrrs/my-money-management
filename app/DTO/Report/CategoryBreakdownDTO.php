<?php

namespace App\DTO\Report;

class CategoryBreakdownDTO
{
    public function __construct(
        public readonly string $category,
        public readonly float $total,
        public readonly float $percentage,
        public readonly int $transactionCount = 0,
        public readonly array $transactions = [],
    ) {}

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'total' => $this->total,
            'percentage' => $this->percentage,
            'transaction_count' => $this->transactionCount,
            'transactions' => $this->transactions,
        ];
    }
}
