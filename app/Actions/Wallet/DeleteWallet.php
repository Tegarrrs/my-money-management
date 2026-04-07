<?php

namespace App\Actions\Wallet;

use App\Models\Wallet;
use Illuminate\Validation\ValidationException;

class DeleteWallet
{
    public function execute(Wallet $wallet): void
    {
        if ($wallet->transactions()->exists()) {
            throw ValidationException::withMessages([
                'wallet' => 'Dompet tidak dapat dihapus karena masih memiliki transaksi.',
            ]);
        }

        $wallet->delete();
    }
}
