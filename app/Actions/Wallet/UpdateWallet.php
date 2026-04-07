<?php

namespace App\Actions\Wallet;

use App\Models\Wallet;

class UpdateWallet
{
    public function execute(Wallet $wallet, array $data): Wallet
    {
        $update = [
            'name'                   => $data['name'],
            'type'                   => $data['type'],
            'icon'                   => $data['icon'] ?? $wallet->icon,
            'color'                  => $data['color'] ?? $wallet->color,
            'allow_negative_balance' => $data['allow_negative_balance'] ?? $wallet->allow_negative_balance,
        ];

        // Allow direct balance correction when user explicitly provides it
        if (isset($data['initial_balance']) && $data['initial_balance'] !== '') {
            $update['balance'] = (float) $data['initial_balance'];
        }

        $wallet->update($update);

        return $wallet->fresh();
    }
}
