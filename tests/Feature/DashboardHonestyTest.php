<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

it('does not invent comparisons, saving rates, or health scores for a new user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard.index'))
        ->assertOk()
        ->assertSee('Belum cukup data')
        ->assertSee('Belum ada pembanding')
        ->assertSee('Belum dapat dihitung karena tidak ada pemasukan bulan ini')
        ->assertSee('Belum ada data cashflow dalam 6 bulan terakhir')
        ->assertSee('Akumulasi saldo seluruh dompet, bukan nilai kekayaan bersih')
        ->assertDontSee('100% vs bln lalu');
});

it('shows a health estimate only after enough current-month data exists', function () {
    $user = User::factory()->create();
    $wallet = Wallet::create([
        'user_id' => $user->id,
        'name' => 'Utama',
        'type' => 'bank',
        'balance' => 4000000,
    ]);
    $expenseCategory = $user->categories()->expense()->firstOrFail();
    $incomeCategory = $user->categories()->income()->firstOrFail();

    foreach ([
        [3000000, $incomeCategory->id, 'Pemasukan'],
        [-500000, $expenseCategory->id, 'Belanja'],
        [-250000, $expenseCategory->id, 'Transportasi'],
    ] as [$amount, $categoryId, $description]) {
        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => now(),
        ]);
    }

    $this->actingAs($user)
        ->get(route('dashboard.index'))
        ->assertOk()
        ->assertSee('Estimasi berdasarkan data bulan ini')
        ->assertSee('Keyakinan sedang')
        ->assertSee('75%')
        ->assertSee('100% pengeluaran terkategori')
        ->assertDontSee('Belum cukup data');
});
