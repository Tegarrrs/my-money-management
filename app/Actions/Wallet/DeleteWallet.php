<?php

namespace App\Actions\Wallet;

use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteWallet
{
    public function execute(Wallet $wallet): void
    {
        if ($wallet->transactions()->where('is_balance_adjustment', false)->exists()) {
            throw ValidationException::withMessages([
                'wallet' => 'Dompet tidak dapat dihapus karena masih memiliki transaksi.',
            ]);
        }

        DB::transaction(function () use ($wallet) {
            $wallet->transactions()->where('is_balance_adjustment', true)->delete();
            $wallet->delete();
        });
    }
}
