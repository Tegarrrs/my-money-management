<?php

namespace App\Services\Budget;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class BudgetOverviewService
{
    public function forMonth(User $user, Carbon $month): array
    {
        $period = $month->copy()->startOfMonth();
        $start = $period->copy()->startOfDay();
        $end = $period->copy()->endOfMonth()->endOfDay();

        $spentByCategory = Transaction::forUser($user->id)
            ->expense()
            ->whereBetween('transaction_date', [$start, $end])
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(ABS(amount)) as spent')
            ->groupBy('category_id')
            ->pluck('spent', 'category_id');

        $budgets = Budget::with('category')
            ->forUser($user->id)
            ->forPeriod($period->toDateString())
            ->whereHas('category', fn ($query) => $query->expense())
            ->get();

        $items = $budgets
            ->map(function (Budget $budget) use ($spentByCategory) {
                $limit = (float) $budget->amount;
                $spent = (float) ($spentByCategory[$budget->category_id] ?? 0);
                $percentage = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0;

                return [
                    'budget' => $budget,
                    'category' => $budget->category,
                    'limit' => $limit,
                    'spent' => $spent,
                    'remaining' => $limit - $spent,
                    'percentage' => $percentage,
                    'bar_percentage' => min(100, $percentage),
                    'status' => $this->status($percentage, $budget->warning_threshold),
                ];
            })
            ->sortByDesc('percentage')
            ->values();

        $totalBudget = (float) $items->sum('limit');
        $totalSpent = (float) $items->sum('spent');
        $totalPercentage = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0;
        $budgetedCategoryIds = $budgets->pluck('category_id');

        $unbudgetedSpending = abs((float) Transaction::forUser($user->id)
            ->expense()
            ->whereBetween('transaction_date', [$start, $end])
            ->when(
                $budgetedCategoryIds->isNotEmpty(),
                fn ($query) => $query->where(function ($inner) use ($budgetedCategoryIds) {
                    $inner->whereNull('category_id')
                        ->orWhereNotIn('category_id', $budgetedCategoryIds);
                })
            )
            ->sum('amount'));

        $elapsedDays = $period->isCurrentMonth()
            ? max(1, now()->day)
            : ($period->isPast() ? $period->daysInMonth : 0);
        $projectedSpent = $elapsedDays > 0
            ? ($totalSpent / $elapsedDays) * $period->daysInMonth
            : 0;

        return [
            'period' => $period,
            'items' => $items,
            'has_budgets' => $items->isNotEmpty(),
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'total_remaining' => $totalBudget - $totalSpent,
            'percentage' => $totalPercentage,
            'bar_percentage' => min(100, $totalPercentage),
            'status' => $this->status($totalPercentage, 80),
            'over_budget_count' => $items->where('status', 'exceeded')->count(),
            'warning_count' => $items->where('status', 'warning')->count(),
            'unbudgeted_spending' => $unbudgetedSpending,
            'projected_spent' => $projectedSpent,
            'projected_difference' => $totalBudget - $projectedSpent,
        ];
    }

    private function status(float $percentage, int $warningThreshold): string
    {
        return match (true) {
            $percentage >= 100 => 'exceeded',
            $percentage >= $warningThreshold => 'warning',
            default => 'safe',
        };
    }
}
