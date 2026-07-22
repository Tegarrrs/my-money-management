<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

it('shows category members and the largest expense only for the signed in user', function () {
    config()->set('services.gemini.api_key', null);

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Bank', 'type' => 'bank']);
    $category = Category::create(['user_id' => $user->id, 'name' => 'Makanan', 'type' => 'expense']);
    $otherWallet = Wallet::create(['user_id' => $otherUser->id, 'name' => 'Dompet Lain', 'type' => 'cash']);
    $otherCategory = Category::create(['user_id' => $otherUser->id, 'name' => 'Rahasia', 'type' => 'expense']);

    Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'amount' => -125000,
        'description' => 'Belanja bulanan',
        'transaction_date' => '2026-07-10',
    ]);
    Transaction::create([
        'user_id' => $otherUser->id,
        'wallet_id' => $otherWallet->id,
        'category_id' => $otherCategory->id,
        'amount' => -9999999,
        'description' => 'Transaksi pengguna lain',
        'transaction_date' => '2026-07-10',
    ]);

    $this->actingAs($user)
        ->get(route('report.index', ['start_date' => '2026-07-01', 'end_date' => '2026-07-31']))
        ->assertOk()
        ->assertSee('Resume Keuangan AI')
        ->assertSee('Pengeluaran Terbesar')
        ->assertSee('Makanan')
        ->assertSee('1 transaksi')
        ->assertSee('Belanja bulanan')
        ->assertDontSee('Transaksi pengguna lain');
});
