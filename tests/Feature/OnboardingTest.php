<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

it('creates a useful set of default categories for every new user', function () {
    $user = User::factory()->create();

    expect($user->categories()->count())->toBe(10)
        ->and($user->categories()->income()->count())->toBeGreaterThan(0)
        ->and($user->categories()->expense()->count())->toBeGreaterThan(0)
        ->and($user->categories()->whereNull('icon')->count())->toBe(0);
});

it('guides a new user through wallet categories and the first transaction', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('onboarding.show'))
        ->assertOk()
        ->assertSee('Buat dompet pertamamu');

    $this->post(route('onboarding.wallet'), [
        'name' => 'Dompet Utama',
        'type' => 'bank',
        'initial_balance' => 1000000,
    ])->assertRedirect(route('onboarding.show'));

    $wallet = $user->wallets()->firstOrFail();
    expect($wallet->transactions()->where('is_balance_adjustment', true)->count())->toBe(1);

    $this->post(route('onboarding.categories'))
        ->assertRedirect(route('onboarding.show'));

    $category = $user->categories()->expense()->firstOrFail();
    $this->post(route('onboarding.transaction'), [
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 50000,
        'description' => 'Belanja pertama',
        'transaction_date' => '2026-07-23',
    ])->assertRedirect(route('dashboard.index'));

    $user->refresh();
    expect($user->onboarding_completed_at)->not->toBeNull()
        ->and($user->onboarding_step)->toBe(4)
        ->and(Transaction::where('user_id', $user->id)->where('is_balance_adjustment', false)->count())->toBe(1)
        ->and((float) $wallet->fresh()->balance)->toBe(950000.0);
});

it('keeps a setup checklist visible when onboarding is skipped', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.skip'))
        ->assertRedirect(route('dashboard.index'));

    $this->get(route('dashboard.index'))
        ->assertOk()
        ->assertSee('Lengkapi pengaturan awal')
        ->assertSee('1 dari 3 langkah selesai');
});

it('reminds the user about uncategorized transactions and provides a working filter', function () {
    $user = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Tunai', 'type' => 'cash']);
    Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'amount' => -25000,
        'description' => 'Belum dikategorikan',
        'transaction_date' => '2026-07-23',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.index'))
        ->assertOk()
        ->assertSee('1 transaksi belum memiliki kategori');

    $this->get(route('transaction.index', ['category_id' => 'uncategorized']))
        ->assertOk()
        ->assertSee('Belum dikategorikan')
        ->assertSee('Tanpa kategori');
});
