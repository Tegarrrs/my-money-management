<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Report\UnusualTransactionDetector;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('finds relative expense spikes without flagging transfers or balance corrections', function () {
    $category = (new Category)->forceFill(['name' => 'Makanan', 'type' => 'expense']);
    $wallet = (new Wallet)->forceFill(['name' => 'Utama']);
    $transactions = collect([10000, 11000, 9000, 10000, 250000])
        ->map(function ($amount, $index) use ($category, $wallet) {
            $transaction = (new Transaction)->forceFill([
                'id' => $index + 1,
                'amount' => -$amount,
                'description' => $amount === 250000 ? 'Belanja besar' : 'Belanja rutin',
                'transaction_date' => Carbon::parse('2026-07-01')->addDays($index),
                'transfer_group_id' => null,
                'is_balance_adjustment' => false,
            ]);
            $transaction->setRelation('category', $category);
            $transaction->setRelation('wallet', $wallet);

            return $transaction;
        });

    $result = app(UnusualTransactionDetector::class)->detect($transactions);

    expect($result)->toHaveCount(1)
        ->and($result[0]['description'])->toBe('Belanja besar')
        ->and($result[0]['reasons'][0])->toContain('median pengeluaran');
});

it('collapses identical duplicate transactions into one review item', function () {
    $category = (new Category)->forceFill(['name' => 'Makanan', 'type' => 'expense']);
    $wallet = (new Wallet)->forceFill(['name' => 'Utama']);
    $transactions = collect([
        ['Kopi', 15000, '2026-07-01'],
        ['Kopi', 15000, '2026-07-01'],
        ['Makan', 20000, '2026-07-02'],
        ['Parkir', 10000, '2026-07-03'],
        ['Minum', 12000, '2026-07-04'],
    ])->map(function ($item, $index) use ($category, $wallet) {
        $transaction = (new Transaction)->forceFill([
            'id' => $index + 1,
            'amount' => -$item[1],
            'description' => $item[0],
            'transaction_date' => Carbon::parse($item[2]),
            'transfer_group_id' => null,
            'is_balance_adjustment' => false,
        ]);
        $transaction->setRelation('category', $category);
        $transaction->setRelation('wallet', $wallet);

        return $transaction;
    });

    $result = app(UnusualTransactionDetector::class)->detect($transactions);

    expect($result)->toHaveCount(1)
        ->and($result[0]['description'])->toBe('Kopi')
        ->and($result[0]['reasons'][0])->toContain('muncul 2 kali');
});
