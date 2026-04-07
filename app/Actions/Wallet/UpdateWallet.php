<?php

namespace App\Actions\Wallet;

use App\Models\Wallet;

class UpdateWallet
{
    public function execute(Wallet $wallet, array $data): Wallet
    {
        // balance is NOT in $data — it's managed only by transaction actions
        $wallet->update([
            'name'                   => $data['name'],
            'type'                   => $data['type'],
            'icon'                   => $data['icon'] ?? $wallet->icon,
            'color'                  => $data['color'] ?? $wallet->color,
            'allow_negative_balance' => $data['allow_negative_balance'] ?? $wallet->allow_negative_balance,
        ]);

        return $wallet->fresh();
    }
}
