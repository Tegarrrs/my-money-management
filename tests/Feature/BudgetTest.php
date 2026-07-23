<?php

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

it('stores monthly budgets only for the users own expense categories', function () {
    $user = User::factory()->create();
    $category = $user->categories()->expense()->firstOrFail();

    $this->actingAs($user)
        ->post(route('budget.sync'), [
            'period' => '2026-07',
            'budgets' => [[
                'category_id' => $category->id,
                'amount' => 1500000,
                'warning_threshold' => 80,
            ]],
        ])
        ->assertRedirect(route('budget.index', ['month' => '2026-07']));

    $this->assertDatabaseHas('budgets', [
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => 1500000,
        'warning_threshold' => 80,
    ]);
    expect(Budget::forUser($user->id)->forPeriod('2026-07-01')->exists())->toBeTrue();

    $otherUser = User::factory()->create();
    $foreignCategory = $otherUser->categories()->expense()->firstOrFail();

    $this->actingAs($user)
        ->post(route('budget.sync'), [
            'period' => '2026-07',
            'budgets' => [[
                'category_id' => $foreignCategory->id,
                'amount' => 500000,
            ]],
        ])
        ->assertSessionHasErrors('budgets.0.category_id');
});

it('calculates actual budget realization from expense transactions', function () {
    $user = User::factory()->create();
    $category = $user->categories()->expense()->firstOrFail();
    $wallet = Wallet::create([
        'user_id' => $user->id,
        'name' => 'Tunai',
        'type' => 'cash',
        'balance' => 0,
    ]);

    Budget::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'period' => now()->startOfMonth(),
        'amount' => 1000000,
        'warning_threshold' => 80,
    ]);
    Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'amount' => -800000,
        'description' => 'Realisasi anggaran',
        'transaction_date' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('budget.index'))
        ->assertOk()
        ->assertSee('Rp 800.000')
        ->assertSee('80,0%')
        ->assertSee('Perhatian');

    $this->get(route('dashboard.index'))
        ->assertOk()
        ->assertSee('80% terpakai');
});

it('copies the previous month without overwriting an existing current budget', function () {
    $user = User::factory()->create();
    $categories = $user->categories()->expense()->take(2)->get();
    $previous = now()->startOfMonth()->subMonth();
    $current = now()->startOfMonth();

    Budget::create([
        'user_id' => $user->id,
        'category_id' => $categories[0]->id,
        'period' => $previous,
        'amount' => 600000,
    ]);
    Budget::create([
        'user_id' => $user->id,
        'category_id' => $categories[1]->id,
        'period' => $previous,
        'amount' => 400000,
    ]);
    Budget::create([
        'user_id' => $user->id,
        'category_id' => $categories[0]->id,
        'period' => $current,
        'amount' => 750000,
    ]);

    $this->actingAs($user)
        ->post(route('budget.copy-previous'), ['period' => $current->format('Y-m')])
        ->assertRedirect(route('budget.index', ['month' => $current->format('Y-m')]));

    expect((float) Budget::where('category_id', $categories[0]->id)->forPeriod($current->toDateString())->value('amount'))
        ->toBe(750000.0)
        ->and((float) Budget::where('category_id', $categories[1]->id)->forPeriod($current->toDateString())->value('amount'))
        ->toBe(400000.0);
});

it('removes a category budget when its amount is cleared', function () {
    $user = User::factory()->create();
    $category = $user->categories()->expense()->firstOrFail();
    $budget = Budget::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'period' => '2026-07-01',
        'amount' => 300000,
    ]);

    $this->actingAs($user)
        ->post(route('budget.sync'), [
            'period' => '2026-07',
            'budgets' => [[
                'category_id' => $category->id,
                'amount' => '',
                'warning_threshold' => 80,
            ]],
        ]);

    expect(Budget::find($budget->id))->toBeNull();
});
