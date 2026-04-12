<?php

namespace App\DTO\Report;

class SummaryDTO
{
    public function __construct(
        public readonly float $income,
        public readonly float $expense,
        public readonly float $balance,
    ) {}

    public function toArray(): array
    {
        return [
            'income' => $this->income,
            'expense' => $this->expense,
            'balance' => $this->balance,
        ];
    }
}
