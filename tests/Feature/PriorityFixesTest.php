<?php

use App\Actions\Wallet\CreateWallet;
use App\Actions\Wallet\UpdateWallet;
use App\Models\Category;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Storage;

it('only exposes split transaction details to their owner', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $owner->id, 'name' => 'Bank', 'type' => 'bank']);
    $groupId = (string) str()->uuid();

    $transaction = Transaction::create([
        'user_id' => $owner->id,
        'wallet_id' => $wallet->id,
        'amount' => -40000,
        'description' => 'Makan',
        'transaction_date' => '2026-07-01',
        'split_group_id' => $groupId,
    ]);
    Transaction::create([
        'user_id' => $owner->id,
        'wallet_id' => $wallet->id,
        'amount' => -10000,
        'description' => 'Parkir',
        'transaction_date' => '2026-07-01',
        'split_group_id' => $groupId,
    ]);

    $this->actingAs($owner)
        ->getJson(route('transaction.splits', $transaction))
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonMissingPath('0.user_id');

    $this->actingAs($otherUser)
        ->getJson(route('transaction.splits', $transaction))
        ->assertForbidden();
});

it('rejects another users category during text and OCR imports', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Bank', 'type' => 'bank']);
    $foreignCategory = Category::create([
        'user_id' => $otherUser->id,
        'name' => 'Kategori Privat',
        'type' => 'expense',
    ]);
    $items = json_encode([[
        'date' => '2026-07-01',
        'description' => 'Belanja',
        'name' => 'Belanja',
        'amount' => 50000,
    ]]);

    $this->actingAs($user)
        ->post(route('import.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $foreignCategory->id,
            'type' => 'expense',
            'items' => $items,
        ])
        ->assertSessionHasErrors('category_id');

    $this->actingAs($user)
        ->post(route('ocr.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $foreignCategory->id,
            'type' => 'expense',
            'date' => '2026-07-01',
            'items' => $items,
        ])
        ->assertSessionHasErrors('category_id');

    expect(Transaction::where('user_id', $user->id)->count())->toBe(0);
});

it('serves private receipts only to their owner', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    Storage::disk('local')->put('receipts/test.jpg', 'private-image');
    $receipt = Receipt::create([
        'user_id' => $owner->id,
        'image_path' => 'receipts/test.jpg',
        'status' => 'completed',
    ]);

    $this->actingAs($owner)
        ->get(route('receipts.show', $receipt))
        ->assertOk()
        ->assertHeader('Cache-Control');

    $this->actingAs($otherUser)
        ->get(route('receipts.show', $receipt))
        ->assertForbidden();
});

it('records initial balances and manual corrections as adjustment transactions', function () {
    $user = User::factory()->create();

    $wallet = app(CreateWallet::class)->execute($user, [
        'name' => 'BCA',
        'type' => 'bank',
        'initial_balance' => 1000000,
    ]);

    expect((float) $wallet->balance)->toBe(1000000.0)
        ->and($wallet->transactions()->where('is_balance_adjustment', true)->count())->toBe(1);

    app(UpdateWallet::class)->execute($wallet, [
        'name' => 'BCA Utama',
        'type' => 'bank',
        'initial_balance' => 1250000,
        'allow_negative_balance' => false,
    ]);

    $wallet->refresh();
    $latestAdjustment = $wallet->transactions()
        ->where('is_balance_adjustment', true)
        ->latest('id')
        ->firstOrFail();

    expect((float) $wallet->balance)->toBe(1250000.0)
        ->and((float) $latestAdjustment->amount)->toBe(250000.0)
        ->and($latestAdjustment->description)->toContain('Koreksi saldo');
});
