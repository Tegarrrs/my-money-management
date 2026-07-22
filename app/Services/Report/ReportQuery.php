<?php

namespace App\Services\Report;

use App\Models\Transaction;
use Illuminate\Support\Collection;

class ReportQuery
{
    public function getSummary(string $start, string $end, ?int $userId = null): object
    {
        $userId = $userId ?? auth()->id();

        $sql = '
            SUM(CASE WHEN amount > 0 AND transfer_group_id IS NULL AND is_balance_adjustment = 0 THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN amount < 0 AND transfer_group_id IS NULL AND is_balance_adjustment = 0 THEN amount ELSE 0 END) as raw_expense
        ';

        $result = Transaction::where('user_id', $userId)
            ->whereBetween('transaction_date', [$start, $end])
            ->selectRaw($sql)
            ->first();

        $income = (float) ($result->income ?? 0);
        $expenseRaw = (float) ($result->raw_expense ?? 0);

        $expense = abs($expenseRaw);

        return (object) [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    public function getCategoryBreakdown(string $start, string $end, ?int $userId = null): Collection
    {
        $userId = $userId ?? auth()->id();

        return Transaction::with('category')
            ->where('user_id', $userId)
            ->where('amount', '<', 0)
            ->where('is_balance_adjustment', false)
            ->whereNull('transfer_group_id')
            ->whereBetween('transaction_date', [$start, $end])
            ->selectRaw('category_id, SUM(ABS(amount)) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();
    }

    public function getTrend(string $start, string $end, ?int $userId = null): Collection
    {
        $userId = $userId ?? auth()->id();

        $sql = '
            DATE(transaction_date) as date,
            SUM(CASE WHEN amount > 0 AND transfer_group_id IS NULL AND is_balance_adjustment = 0 THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN amount < 0 AND transfer_group_id IS NULL AND is_balance_adjustment = 0 THEN ABS(amount) ELSE 0 END) as expense
        ';

        return Transaction::where('user_id', $userId)
            ->whereNull('transfer_group_id')
            ->where('is_balance_adjustment', false)
            ->whereBetween('transaction_date', [$start, $end])
            ->selectRaw($sql)
            ->groupByRaw('DATE(transaction_date)')
            ->orderByRaw('DATE(transaction_date)')
            ->get();
    }

    public function getDetailedTransactions(string $start, string $end, ?int $userId = null): Collection
    {
        $userId = $userId ?? auth()->id();

        $transactions = Transaction::with(['category', 'wallet'])
            ->where('user_id', $userId)
            ->whereBetween('transaction_date', [$start, $end])
            ->where(function ($q) {
                $q->whereNull('transfer_group_id')
                    ->orWhere('amount', '<', 0); // only debit leg of transfers
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $transferGroups = $transactions->pluck('transfer_group_id')->filter()->unique();
        $destinationWallets = $transferGroups->isEmpty()
            ? collect()
            : Transaction::with('wallet')
                ->where('user_id', $userId)
                ->whereIn('transfer_group_id', $transferGroups)
                ->where('amount', '>', 0)
                ->get()
                ->keyBy('transfer_group_id');

        $transactions->each(function ($transaction) use ($destinationWallets) {
            $destination = $destinationWallets->get($transaction->transfer_group_id);
            $transaction->setAttribute('destination_wallet_name', $destination?->wallet?->name);
        });

        return $transactions;
    }
}
