<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

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

it('only calls Gemini manually, discloses the payload, and flags unusual transactions locally', function () {
    config()->set('services.gemini.api_key', 'test-key');
    config()->set('services.gemini.report_model', 'gemini-flash-latest');
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'summary' => 'Pengeluaran memiliki satu lonjakan yang perlu diperiksa.',
                            'highlights' => ['Satu transaksi jauh melampaui pola periode.'],
                            'recommendations' => ['Periksa kembali transaksi dengan nominal tertinggi.'],
                        ]),
                    ]],
                ],
            ]],
        ]),
    ]);

    $user = User::factory()->create(['name' => 'Nama Privat', 'email' => 'privat@example.com']);
    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Nama Dompet Privat', 'type' => 'bank']);
    $category = Category::create(['user_id' => $user->id, 'name' => 'Makanan', 'type' => 'expense']);

    foreach ([10000, 11000, 9000, 10000, 250000] as $index => $amount) {
        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => -$amount,
            'description' => $amount === 250000 ? 'Belanja sangat besar' : 'Belanja rutin '.$index,
            'detail' => 'Catatan privat yang tidak boleh dikirim',
            'transaction_date' => '2026-07-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
        ]);
    }

    $parameters = [
        'preset' => 'custom',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ];

    $this->actingAs($user)
        ->get(route('report.index', $parameters))
        ->assertOk()
        ->assertSee('Analisis dengan AI')
        ->assertSee('Data yang diterima Gemini')
        ->assertSee('Transaksi Tidak Biasa')
        ->assertSee('Belanja sangat besar')
        ->assertSee('median pengeluaran periode ini');

    Http::assertNothingSent();

    $this->post(route('report.analyze'), $parameters)
        ->assertRedirect(route('report.index', $parameters))
        ->assertSessionHas('success');

    Http::assertSent(function (Request $request) {
        $prompt = data_get($request->data(), 'contents.0.parts.0.text', '');

        return str_contains($prompt, 'Belanja sangat besar')
            && str_contains($prompt, '"summary"')
            && ! str_contains($prompt, 'Nama Privat')
            && ! str_contains($prompt, 'privat@example.com')
            && ! str_contains($prompt, 'Nama Dompet Privat')
            && ! str_contains($prompt, 'Catatan privat yang tidak boleh dikirim')
            && ! str_contains($prompt, '"id"');
    });

    $this->get(route('report.index', $parameters))
        ->assertOk()
        ->assertSee('Pengeluaran memiliki satu lonjakan yang perlu diperiksa.')
        ->assertSee('Gemini AI')
        ->assertSee('Analisis ulang')
        ->assertSee('Tidak dikirim')
        ->assertSee('Deteksi transaksi tidak biasa dihitung secara lokal');

    Http::assertSentCount(1);
});
