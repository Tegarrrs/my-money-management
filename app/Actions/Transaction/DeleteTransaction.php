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
            if ($transaction->transfer_group_id) {
                // Find all legs of this transfer
                $legs = Transaction::where('user_id', $transaction->user_id)
                    ->where('transfer_group_id', $transaction->transfer_group_id)
                    ->get();
                foreach ($legs as $leg) {
                    $wallet = Wallet::find($leg->wallet_id);
                    if ($wallet) {
                        $wallet->increment('balance', -(float) $leg->amount);
                    }
                    $leg->delete();
                }
            } elseif ($transaction->split_group_id) {
                // Find all parts of this split
                $parts = Transaction::where('user_id', $transaction->user_id)
                    ->where('split_group_id', $transaction->split_group_id)
                    ->get();
                foreach ($parts as $part) {
                    $wallet = Wallet::find($part->wallet_id);
                    if ($wallet) {
                        $wallet->increment('balance', -(float) $part->amount);
                    }
                    $part->delete();
                }
            } else {
                /** @var Wallet $wallet */
                $wallet = Wallet::findOrFail($transaction->wallet_id);
                $wallet->increment('balance', -(float) $transaction->amount);
                $transaction->delete();
            }
        });
    }
}
