<?php

use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionRun;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Recurring\RecurringTransactionProcessor;
use Illuminate\Support\Carbon;

it('creates recurring transactions with ownership-safe wallet and category validation', function () {
    $user = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Utama', 'type' => 'bank']);
    $category = $user->categories()->expense()->firstOrFail();

    $this->actingAs($user)->post(route('recurring.store'), [
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 250000,
        'description' => 'Internet bulanan',
        'frequency' => 'monthly',
        'next_run_at' => '2026-07-25',
    ])->assertRedirect(route('recurring.index'));

    $recurring = RecurringTransaction::firstOrFail();
    expect((float) $recurring->amount)->toBe(-250000.0)
        ->and($recurring->frequency)->toBe('monthly');

    $this->get(route('recurring.index'))
        ->assertOk()
        ->assertSee('Internet bulanan')
        ->assertSee('data-recurring', false);

    $other = User::factory()->create();
    $foreignWallet = Wallet::create(['user_id' => $other->id, 'name' => 'Asing', 'type' => 'cash']);
    $this->post(route('recurring.store'), [
        'wallet_id' => $foreignWallet->id,
        'type' => 'expense',
        'amount' => 1000,
        'description' => 'Tidak valid',
        'frequency' => 'daily',
        'next_run_at' => '2026-07-25',
    ])->assertSessionHasErrors('wallet_id');
});

it('processes due schedules exactly once and advances the next run', function () {
    Carbon::setTestNow('2026-07-23 10:00:00');
    $user = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Tunai', 'type' => 'cash', 'balance' => 1000000]);
    $category = $user->categories()->expense()->firstOrFail();
    $recurring = RecurringTransaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'amount' => -100000,
        'description' => 'Langganan',
        'frequency' => 'monthly',
        'next_run_at' => now(),
        'is_active' => true,
    ]);

    $processor = app(RecurringTransactionProcessor::class);
    expect($processor->processDue($user->id)['completed'])->toBe(1)
        ->and($processor->processDue($user->id)['completed'])->toBe(0)
        ->and(Transaction::where('recurring_transaction_id', $recurring->id)->count())->toBe(1)
        ->and(RecurringTransactionRun::where('recurring_transaction_id', $recurring->id)->count())->toBe(1)
        ->and((float) $wallet->fresh()->balance)->toBe(900000.0)
        ->and($recurring->fresh()->next_run_at->toDateString())->toBe('2026-08-23');
});

it('allows manual execution without duplicating a run in the same minute', function () {
    Carbon::setTestNow('2026-07-23 10:15:20');
    $user = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Bank', 'type' => 'bank']);
    $recurring = RecurringTransaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'amount' => 500000,
        'description' => 'Pendapatan rutin',
        'frequency' => 'monthly',
        'next_run_at' => now()->addWeek(),
        'is_active' => true,
    ]);

    $processor = app(RecurringTransactionProcessor::class);
    expect($processor->runNow($recurring))->toBeTrue()
        ->and($processor->runNow($recurring))->toBeTrue()
        ->and(Transaction::where('recurring_transaction_id', $recurring->id)->count())->toBe(1)
        ->and((float) $wallet->fresh()->balance)->toBe(500000.0);
});

it('prevents another user from changing or running a recurring schedule', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $owner->id, 'name' => 'Owner', 'type' => 'cash']);
    $recurring = RecurringTransaction::create([
        'user_id' => $owner->id,
        'wallet_id' => $wallet->id,
        'amount' => -10000,
        'description' => 'Milik owner',
        'frequency' => 'weekly',
        'next_run_at' => now(),
    ]);

    $this->actingAs($attacker)
        ->patch(route('recurring.toggle', $recurring))
        ->assertForbidden();
    $this->post(route('recurring.run', $recurring))
        ->assertForbidden();
    $this->delete(route('recurring.destroy', $recurring))
        ->assertForbidden();
});
