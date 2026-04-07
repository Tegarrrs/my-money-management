<?php

namespace App\Actions\Wallet;

use App\Models\User;
use App\Models\Wallet;

class CreateWallet
{
    public function execute(User $user, array $data): Wallet
    {
        return $user->wallets()->create([
            'name'                   => $data['name'],
            'type'                   => $data['type'],
            'icon'                   => $data['icon'] ?? null,
            'color'                  => $data['color'] ?? null,
            'balance'                => (float) ($data['initial_balance'] ?? 0),
            'allow_negative_balance' => $data['allow_negative_balance'] ?? false,
        ]);
    }
}
