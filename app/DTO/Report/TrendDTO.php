<?php

namespace App\DTO\Report;

class TrendDTO
{
    public function __construct(
        public readonly string $date,
        public readonly float $income,
        public readonly float $expense,
    ) {}

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'income' => $this->income,
            'expense' => $this->expense,
        ];
    }
}
