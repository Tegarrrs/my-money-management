<?php

namespace App\Actions\Wallet;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class CreateWallet
{
    public function execute(User $user, array $data): Wallet
    {
        return DB::transaction(function () use ($user, $data) {
            $initialBalance = round((float) ($data['initial_balance'] ?? 0), 2);

            $wallet = $user->wallets()->create([
                'name' => $data['name'],
                'type' => $data['type'],
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? null,
                'balance' => $initialBalance,
                'allow_negative_balance' => $data['allow_negative_balance'] ?? false,
            ]);

            if ($initialBalance !== 0.0) {
                $user->transactions()->create([
                    'wallet_id' => $wallet->id,
                    'amount' => $initialBalance,
                    'description' => "Saldo awal – {$wallet->name}",
                    'detail' => 'Dibuat otomatis saat dompet ditambahkan.',
                    'transaction_date' => now()->toDateString(),
                    'is_balance_adjustment' => true,
                ]);
            }

            return $wallet;
        });
    }
}
