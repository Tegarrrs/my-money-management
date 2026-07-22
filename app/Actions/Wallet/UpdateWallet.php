<?php

namespace App\Actions\Wallet;

use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class UpdateWallet
{
    public function execute(Wallet $wallet, array $data): Wallet
    {
        return DB::transaction(function () use ($wallet, $data) {
            $lockedWallet = Wallet::query()->lockForUpdate()->findOrFail($wallet->id);
            $oldName = $lockedWallet->name;

            if (isset($data['initial_balance']) && $data['initial_balance'] !== '') {
                $targetBalance = round((float) $data['initial_balance'], 2);
                $currentBalance = round((float) $lockedWallet->balance, 2);
                $difference = round($targetBalance - $currentBalance, 2);

                if (abs($difference) >= 0.01) {
                    $lockedWallet->transactions()->create([
                        'user_id' => $lockedWallet->user_id,
                        'amount' => $difference,
                        'description' => "Koreksi saldo – {$oldName}",
                        'detail' => 'Dibuat otomatis dari perubahan saldo dompet.',
                        'transaction_date' => now()->toDateString(),
                        'is_balance_adjustment' => true,
                    ]);

                    $lockedWallet->increment('balance', $difference);
                }
            }

            $lockedWallet->update([
                'name' => $data['name'],
                'type' => $data['type'],
                'icon' => $data['icon'] ?? $lockedWallet->icon,
                'color' => $data['color'] ?? $lockedWallet->color,
                'allow_negative_balance' => $data['allow_negative_balance'] ?? false,
            ]);

            return $lockedWallet->fresh();
        });
    }
}
