<?php

namespace App\Services\Report;

use Illuminate\Support\Collection;

class UnusualTransactionDetector
{
    public function detect(Collection $transactions): array
    {
        $expenses = $transactions
            ->filter(fn ($transaction) => (float) $transaction->amount < 0
                && is_null($transaction->transfer_group_id)
                && ! $transaction->is_balance_adjustment)
            ->values();

        if ($expenses->isEmpty()) {
            return [];
        }

        $globalMedian = $this->median($expenses->map(fn ($transaction) => abs((float) $transaction->amount)));
        $categoryMedians = $expenses
            ->groupBy(fn ($transaction) => $transaction->category_id ?? 'uncategorized')
            ->map(fn (Collection $items) => [
                'count' => $items->count(),
                'median' => $this->median($items->map(fn ($transaction) => abs((float) $transaction->amount))),
            ]);
        $duplicateGroups = $expenses->groupBy(fn ($transaction) => implode('|', [
            $transaction->transaction_date->format('Y-m-d'),
            mb_strtolower(trim((string) $transaction->description)),
            number_format(abs((float) $transaction->amount), 2, '.', ''),
        ]));

        return $expenses
            ->map(function ($transaction) use ($expenses, $globalMedian, $categoryMedians, $duplicateGroups) {
                $amount = abs((float) $transaction->amount);
                $categoryKey = $transaction->category_id ?? 'uncategorized';
                $categoryStats = $categoryMedians->get($categoryKey);
                $duplicateKey = implode('|', [
                    $transaction->transaction_date->format('Y-m-d'),
                    mb_strtolower(trim((string) $transaction->description)),
                    number_format($amount, 2, '.', ''),
                ]);
                $duplicateCount = $duplicateGroups->get($duplicateKey, collect())->count();
                $reasons = [];
                $score = 0.0;

                if ($expenses->count() >= 5 && $globalMedian > 0 && $amount >= $globalMedian * 3) {
                    $ratio = round($amount / $globalMedian, 1);
                    $ratioLabel = number_format($ratio, 1, ',', '.');
                    $reasons[] = "Nominal {$ratioLabel}x lebih besar dari median pengeluaran periode ini.";
                    $score = max($score, $ratio);
                }

                if (($categoryStats['count'] ?? 0) >= 4
                    && ($categoryStats['median'] ?? 0) > 0
                    && $amount >= $categoryStats['median'] * 2.5) {
                    $ratio = round($amount / $categoryStats['median'], 1);
                    $ratioLabel = number_format($ratio, 1, ',', '.');
                    $reasons[] = "Nominal {$ratioLabel}x di atas median kategori ".($transaction->category?->name ?? 'Tanpa kategori').'.';
                    $score = max($score, $ratio);
                }

                if ($duplicateCount >= 2) {
                    $reasons[] = "Deskripsi, tanggal, dan nominal yang sama muncul {$duplicateCount} kali.";
                    $score = max($score, 2 + ($duplicateCount / 10));
                }

                if ($reasons === []) {
                    return null;
                }

                return [
                    'id' => $transaction->id,
                    'description' => $transaction->description ?: 'Tanpa deskripsi',
                    'category' => $transaction->category?->name ?? 'Tanpa kategori',
                    'wallet' => $transaction->wallet?->name ?? 'Tanpa dompet',
                    'amount' => $amount,
                    'formatted_date' => $transaction->transaction_date->translatedFormat('d M Y'),
                    'reasons' => array_values(array_unique($reasons)),
                    'score' => $score,
                    'signature' => $duplicateKey,
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->unique('signature')
            ->take(5)
            ->values()
            ->map(function (array $transaction) {
                unset($transaction['signature']);

                return $transaction;
            })
            ->all();
    }

    private function median(Collection $values): float
    {
        $sorted = $values->map(fn ($value) => (float) $value)->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return 0;
        }

        $middle = intdiv($count, 2);

        return $count % 2 === 0
            ? ($sorted[$middle - 1] + $sorted[$middle]) / 2
            : $sorted[$middle];
    }
}
