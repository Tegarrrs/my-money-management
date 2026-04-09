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

            $type      = $data['type'] ?? 'expense';
            $oldAmount = (float) $transaction->amount;

            // ── TRANSFER (update both legs via transfer_group_id) ─────────
            if ($type === 'transfer' && $transaction->transfer_group_id) {
                $groupId    = $transaction->transfer_group_id;
                $newAmount  = (float) $data['amount'];
                $desc       = $data['description'] ?? null;
                $detail     = $data['detail'] ?? null;
                $date       = $data['transaction_date'];

                // Find both legs
                $legs = Transaction::where('transfer_group_id', $groupId)->get();
                $outLeg = $legs->where('amount', '<', 0)->first() ?? $legs->first();
                $inLeg  = $legs->where('amount', '>=', 0)->first() ?? $legs->last();

                $fromWallet = Wallet::findOrFail($data['wallet_id']);
                $toWallet   = Wallet::findOrFail($data['to_wallet_id']);

                // Reverse old balances
                $outLeg->wallet->increment('balance', abs($oldAmount));
                $inLeg->wallet->increment('balance', -abs((float)$inLeg->amount));

                // Apply new balances
                $fromWallet->decrement('balance', $newAmount);
                $toWallet->increment('balance', $newAmount);

                // Update both legs
                $outLeg->update([
                    'wallet_id'        => $fromWallet->id,
                    'amount'           => -$newAmount,
                    'description'      => $desc,
                    'detail'           => $detail,
                    'transaction_date' => $date,
                ]);
                $inLeg->update([
                    'wallet_id'        => $toWallet->id,
                    'amount'           => $newAmount,
                    'description'      => $desc,
                    'detail'           => $detail,
                    'transaction_date' => $date,
                ]);

                return $transaction->fresh();
            }

            // ── INCOME / EXPENSE ──────────────────────────────────────────
            /** @var ?Category $newCategory */
            $newCategory = isset($data['category_id']) ? Category::find($data['category_id']) : null;

            /** @var Wallet $newWallet */
            $newWallet = Wallet::findOrFail($data['wallet_id']);

            $oldWalletId = $transaction->wallet_id;
            $newAmount   = $this->parser->normaliseAmount((float) $data['amount'], $newCategory, $type);

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
                'detail'           => $data['detail'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'transfer_group_id'=> null,
            ]);

            return $transaction->fresh();
        });
    }
}
