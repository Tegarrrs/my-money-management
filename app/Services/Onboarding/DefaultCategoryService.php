<?php

namespace App\Services\Onboarding;

use App\Models\User;

class DefaultCategoryService
{
    public const CATEGORIES = [
        ['name' => 'Gaji', 'type' => 'income', 'icon' => 'bi-cash-stack', 'color' => '#15803d'],
        ['name' => 'Bonus', 'type' => 'income', 'icon' => 'bi-gift', 'color' => '#16a34a'],
        ['name' => 'Pemasukan Lainnya', 'type' => 'income', 'icon' => 'bi-plus-circle', 'color' => '#0e7490'],
        ['name' => 'Makanan & Minuman', 'type' => 'expense', 'icon' => 'bi-cup-hot', 'color' => '#f97316'],
        ['name' => 'Transportasi', 'type' => 'expense', 'icon' => 'bi-car-front', 'color' => '#3b82f6'],
        ['name' => 'Belanja', 'type' => 'expense', 'icon' => 'bi-bag', 'color' => '#ec4899'],
        ['name' => 'Tagihan', 'type' => 'expense', 'icon' => 'bi-receipt', 'color' => '#7c3aed'],
        ['name' => 'Kesehatan', 'type' => 'expense', 'icon' => 'bi-heart-pulse', 'color' => '#dc2626'],
        ['name' => 'Hiburan', 'type' => 'expense', 'icon' => 'bi-controller', 'color' => '#0891b2'],
        ['name' => 'Lain-lain', 'type' => 'expense', 'icon' => 'bi-three-dots', 'color' => '#6b7280'],
    ];

    public function ensureFor(User $user): void
    {
        foreach (self::CATEGORIES as $category) {
            $record = $user->categories()->firstOrNew([
                'name' => $category['name'],
                'type' => $category['type'],
            ]);

            // Lengkapi metadata bawaan tanpa menimpa tampilan yang sudah
            // dikustomisasi oleh pengguna lama.
            $record->icon ??= $category['icon'];
            $record->color ??= $category['color'];
            $record->save();
        }
    }
}
