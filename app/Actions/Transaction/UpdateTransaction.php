<?php

namespace App\Actions\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\TransactionParser;
use Illuminate\Support\Facades\DB;

class UpdateTransaction
{
    public function __construct(private readonly TransactionParser $parser) {}

    public function execute(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            /** @var Category $newCategory */
            $newCategory = Category::findOrFail($data['category_id']);

            /** @var Wallet $newWallet */
            $newWallet = Wallet::findOrFail($data['wallet_id']);

            $oldAmount = (float) $transaction->amount;
            $oldWalletId = $transaction->wallet_id;

            // Normalise new amount
            $newAmount = $this->parser->normaliseAmount((float) $data['amount'], $newCategory);

            // --- Rollback old effect on the old wallet ---
            if ($oldWalletId === $newWallet->id) {
                // Same wallet: net delta
                $delta = $newAmount - $oldAmount;
                $newWallet->increment('balance', $delta);
            } else {
                // Wallet changed: fully reverse on old, fully apply on new
                $oldWallet = Wallet::findOrFail($oldWalletId);
                $oldWallet->increment('balance', -$oldAmount);
                $newWallet->increment('balance', $newAmount);
            }

            // Update transaction record
            $transaction->update([
                'wallet_id'        => $newWallet->id,
                'category_id'      => $newCategory->id,
                'amount'           => $newAmount,
                'description'      => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'],
            ]);

            return $transaction->fresh();
        });
    }
}
