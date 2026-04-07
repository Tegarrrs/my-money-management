<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class DeleteTransaction
{
    public function execute(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            /** @var Wallet $wallet */
            $wallet = Wallet::findOrFail($transaction->wallet_id);

            // Reverse the amount effect on the wallet
            $wallet->increment('balance', -(float) $transaction->amount);

            $transaction->delete();
        });
    }
}
