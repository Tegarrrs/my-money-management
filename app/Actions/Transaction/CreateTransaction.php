<?php

namespace App\Actions\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TransactionParser;
use Illuminate\Support\Facades\DB;

class CreateTransaction
{
    public function __construct(private readonly TransactionParser $parser) {}

    public function execute(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            /** @var Category $category */
            $category = Category::findOrFail($data['category_id']);

            /** @var Wallet $wallet */
            $wallet = Wallet::findOrFail($data['wallet_id']);

            // Normalise amount: income positive, expense negative
            $amount = $this->parser->normaliseAmount((float) $data['amount'], $category);

            // Create the transaction
            $transaction = $user->transactions()->create([
                'wallet_id'        => $wallet->id,
                'category_id'      => $category->id,
                'amount'           => $amount,
                'description'      => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'],
            ]);

            // Update wallet balance
            $wallet->increment('balance', $amount);

            return $transaction;
        });
    }
}
