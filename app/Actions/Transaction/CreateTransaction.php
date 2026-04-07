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
            /** @var ?Category $category */
            $category = isset($data['category_id']) ? Category::find($data['category_id']) : null;

            /** @var Wallet $wallet */
            $wallet = Wallet::findOrFail($data['wallet_id']);

            // Normalise: category type takes priority, else explicit type field
            $amount = $this->parser->normaliseAmount((float) $data['amount'], $category, $data['type'] ?? 'expense');

            $transaction = $user->transactions()->create([
                'wallet_id'        => $wallet->id,
                'category_id'      => $category?->id,
                'amount'           => $amount,
                'description'      => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'],
            ]);

            $wallet->increment('balance', $amount);

            return $transaction;
        });
    }
}
