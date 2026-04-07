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
            /** @var ?Category $newCategory */
            $newCategory = isset($data['category_id']) ? Category::find($data['category_id']) : null;

            /** @var Wallet $newWallet */
            $newWallet = Wallet::findOrFail($data['wallet_id']);

            $oldAmount   = (float) $transaction->amount;
            $oldWalletId = $transaction->wallet_id;

            $newAmount = $this->parser->normaliseAmount((float) $data['amount'], $newCategory, $data['type'] ?? 'expense');

            if ($oldWalletId === $newWallet->id) {
                $newWallet->increment('balance', $newAmount - $oldAmount);
            } else {
                $oldWallet = Wallet::findOrFail($oldWalletId);
                $oldWallet->increment('balance', -$oldAmount);
                $newWallet->increment('balance', $newAmount);
            }

            $transaction->update([
                'wallet_id'        => $newWallet->id,
                'category_id'      => $newCategory?->id,
                'amount'           => $newAmount,
                'description'      => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'],
            ]);

            return $transaction->fresh();
        });
    }
}
