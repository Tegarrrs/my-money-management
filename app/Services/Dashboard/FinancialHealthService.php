<?php

namespace App\Services\Dashboard;

class FinancialHealthService
{
    public function calculate(array $data): array
    {
        $transactionCount = (int) ($data['transaction_count'] ?? 0);
        $income = (float) ($data['income'] ?? 0);
        $expense = (float) ($data['expense'] ?? 0);
        $savingRate = $data['saving_rate'];
        $categorizedRate = $data['categorized_rate'];
        $recordedMonths = (int) ($data['recorded_months'] ?? 0);
        $hasWallet = (bool) ($data['has_wallet'] ?? false);
        $hasBudget = (bool) ($data['has_budget'] ?? false);
        $budgetUsage = (float) ($data['budget_usage'] ?? 0);
        $balance = (float) ($data['balance'] ?? 0);

        $completeness = 0;
        $completeness += $hasWallet ? 20 : 0;
        $completeness += match (true) {
            $transactionCount >= 10 => 30,
            $transactionCount >= 3 => 20,
            $transactionCount >= 1 => 10,
            default => 0,
        };
        $completeness += $categorizedRate === null
            ? 0
            : (int) round(min(100, max(0, $categorizedRate)) * 0.3);
        $completeness += min(20, $recordedMonths * 5);
        $completeness = min(100, $completeness);

        $confidence = match (true) {
            $completeness >= 80 => 'high',
            $completeness >= 50 => 'medium',
            default => 'low',
        };

        // Tanpa pemasukan atau jumlah transaksi minimum, saving rate dan skor
        // keseluruhan belum cukup representatif untuk dinilai.
        $available = $transactionCount >= 3 && $income > 0 && $savingRate !== null;
        if (! $available) {
            return [
                'available' => false,
                'score' => null,
                'grade' => 'Belum cukup data',
                'confidence' => $confidence,
                'completeness' => $completeness,
                'reason' => $income <= 0
                    ? 'Catat pemasukan dan minimal 3 transaksi bulan ini.'
                    : 'Catat minimal 3 transaksi bulan ini.',
                'factors' => [],
            ];
        }

        $points = 0;
        $maximum = 85;

        $savingPoints = match (true) {
            $savingRate >= 20 => 35,
            $savingRate >= 10 => 25,
            $savingRate >= 0 => 15,
            default => 0,
        };
        $points += $savingPoints;

        $cashflowRatio = $income > 0 ? ($income - $expense) / $income : -1;
        $cashflowPoints = match (true) {
            $cashflowRatio >= .2 => 30,
            $cashflowRatio >= 0 => 20,
            $cashflowRatio >= -.1 => 8,
            default => 0,
        };
        $points += $cashflowPoints;

        $balancePoints = match (true) {
            $balance > 0 => 20,
            $balance === 0.0 => 10,
            default => 0,
        };
        $points += $balancePoints;

        $budgetPoints = null;
        if ($hasBudget) {
            $maximum += 15;
            $budgetPoints = match (true) {
                $budgetUsage <= 80 => 15,
                $budgetUsage <= 100 => 8,
                default => 0,
            };
            $points += $budgetPoints;
        }

        $score = (int) round(($points / $maximum) * 100);

        return [
            'available' => true,
            'score' => min(100, max(0, $score)),
            'grade' => match (true) {
                $score >= 80 => 'Sangat Baik',
                $score >= 60 => 'Baik',
                $score >= 40 => 'Cukup',
                default => 'Perlu Perhatian',
            },
            'confidence' => $confidence,
            'completeness' => $completeness,
            'reason' => null,
            'factors' => [
                'saving' => ['points' => $savingPoints, 'maximum' => 35],
                'cashflow' => ['points' => $cashflowPoints, 'maximum' => 30],
                'balance' => ['points' => $balancePoints, 'maximum' => 20],
                'budget' => $budgetPoints === null ? null : ['points' => $budgetPoints, 'maximum' => 15],
            ],
        ];
    }
}
