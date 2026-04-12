<?php

namespace App\DTO\Report;

class CategoryBreakdownDTO
{
    public function __construct(
        public readonly string $category,
        public readonly float $total,
        public readonly float $percentage,
    ) {}

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'total' => $this->total,
            'percentage' => $this->percentage,
        ];
    }
}
