<?php

namespace App\Actions\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TransactionParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTransaction
{
    public function __construct(private readonly TransactionParser $parser) {}

    public function execute(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {

            $type = $data['type'] ?? 'expense';

            // ── TRANSFER ──────────────────────────────────────────────────
            if ($type === 'transfer') {
                $fromWallet = Wallet::findOrFail($data['wallet_id']);
                $toWallet   = Wallet::findOrFail($data['to_wallet_id']);
                $amount     = (float) $data['amount'];
                $groupId    = (string) Str::uuid();
                $desc       = $data['description'] ?? null;
                $detail     = $data['detail'] ?? null;
                $date       = $data['transaction_date'];

                // Debit from source wallet
                $out = $user->transactions()->create([
                    'wallet_id'         => $fromWallet->id,
                    'category_id'       => null,
                    'amount'            => -$amount,
                    'description'       => $desc,
                    'detail'            => $detail,
                    'transaction_date'  => $date,
                    'transfer_group_id' => $groupId,
                ]);

                // Credit to destination wallet
                $user->transactions()->create([
                    'wallet_id'         => $toWallet->id,
                    'category_id'       => null,
                    'amount'            => $amount,
                    'description'       => $desc,
                    'detail'            => $detail,
                    'transaction_date'  => $date,
                    'transfer_group_id' => $groupId,
                ]);

                $fromWallet->decrement('balance', $amount);
                $toWallet->increment('balance', $amount);

                return $out;
            }

            // ── INCOME / EXPENSE ──────────────────────────────────────────
            /** @var ?Category $category */
            $category = isset($data['category_id']) ? Category::find($data['category_id']) : null;

            /** @var Wallet $wallet */
            $wallet = Wallet::findOrFail($data['wallet_id']);

            $amount = $this->parser->normaliseAmount((float) $data['amount'], $category, $type);

            $transaction = $user->transactions()->create([
                'wallet_id'        => $wallet->id,
                'category_id'      => $category?->id,
                'amount'           => $amount,
                'description'      => $data['description'] ?? null,
                'detail'           => $data['detail'] ?? null,
                'transaction_date' => $data['transaction_date'],
            ]);

            $wallet->increment('balance', $amount);

            return $transaction;
        });
    }
}
