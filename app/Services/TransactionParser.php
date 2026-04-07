<?php

namespace App\Services;

use App\Models\Category;

/**
 * Responsible for normalising raw user input into a signed amount
 * and deriving the transaction type from the category.
 */
class TransactionParser
{
    /**
     * Normalise the amount based on the category type.
     * - income  → positive
     * - expense → negative
     */
    public function normaliseAmount(float $rawAmount, ?Category $category, string $type = 'expense'): float
    {
        $abs = abs($rawAmount);
        $resolvedType = $category?->type ?? $type;

        return $resolvedType === 'income' ? $abs : -$abs;
    }

    /**
     * Derive type string from a signed amount.
     */
    public function deriveType(float $amount): string
    {
        return $amount >= 0 ? 'income' : 'expense';
    }
}
