<?php

use App\Services\Dashboard\FinancialHealthService;

it('withholds a health score when the data is insufficient', function () {
    $result = app(FinancialHealthService::class)->calculate([
        'transaction_count' => 0,
        'income' => 0,
        'expense' => 0,
        'saving_rate' => null,
        'categorized_rate' => null,
        'recorded_months' => 0,
        'has_wallet' => false,
        'has_budget' => false,
        'balance' => 0,
    ]);

    expect($result['available'])->toBeFalse()
        ->and($result['score'])->toBeNull()
        ->and($result['grade'])->toBe('Belum cukup data')
        ->and($result['confidence'])->toBe('low')
        ->and($result['completeness'])->toBe(0);
});

it('normalizes the score only across factors that are actually available', function () {
    $withoutBudget = app(FinancialHealthService::class)->calculate([
        'transaction_count' => 12,
        'income' => 5000000,
        'expense' => 3500000,
        'saving_rate' => 30,
        'categorized_rate' => 100,
        'recorded_months' => 6,
        'has_wallet' => true,
        'has_budget' => false,
        'budget_usage' => 0,
        'balance' => 10000000,
    ]);
    $withExceededBudget = app(FinancialHealthService::class)->calculate([
        'transaction_count' => 12,
        'income' => 5000000,
        'expense' => 3500000,
        'saving_rate' => 30,
        'categorized_rate' => 100,
        'recorded_months' => 6,
        'has_wallet' => true,
        'has_budget' => true,
        'budget_usage' => 120,
        'balance' => 10000000,
    ]);

    expect($withoutBudget['available'])->toBeTrue()
        ->and($withoutBudget['score'])->toBe(100)
        ->and($withoutBudget['confidence'])->toBe('high')
        ->and($withExceededBudget['score'])->toBe(85)
        ->and($withExceededBudget['factors']['budget']['points'])->toBe(0);
});
