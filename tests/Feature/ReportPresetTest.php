<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('applies report presets and offers polished download formats', function () {
    Carbon::setTestNow('2026-07-23 10:00:00');
    $user = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Utama', 'type' => 'bank']);

    Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'amount' => 2000000,
        'description' => 'Pemasukan Juli',
        'transaction_date' => '2026-07-10',
    ]);
    Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'amount' => 1000000,
        'description' => 'Pemasukan pembanding',
        'transaction_date' => '2026-06-10',
    ]);

    $this->actingAs($user)
        ->get(route('report.index', ['preset' => 'this_month']))
        ->assertOk()
        ->assertSee('Unduh laporan')
        ->assertSee('Dokumen PDF')
        ->assertSee('Workbook Excel')
        ->assertDontSee('Preset ini untuk apa?')
        ->assertSee('01 Jul 2026 – 23 Jul 2026')
        ->assertSee('+100,0%')
        ->assertSee('Pemasukan Juli')
        ->assertDontSee('Pemasukan pembanding');

    $this->get(route('report.index', ['preset' => 'last_month']))
        ->assertOk()
        ->assertSee('01 Jun 2026 – 30 Jun 2026')
        ->assertSee('Pemasukan pembanding');
});

it('treats manually supplied dates as a custom period', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('report.index', [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-15',
        ]))
        ->assertOk()
        ->assertSee('Rentang tanggal kustom')
        ->assertSee('01 Apr 2026 – 15 Apr 2026')
        ->assertSee('15 hari kalender')
        ->assertSee('Rendah');
});

it('downloads a styled and editable Excel report for the signed in user', function () {
    Carbon::setTestNow('2026-07-23 10:00:00');
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Rekening Utama', 'type' => 'bank']);
    $otherWallet = Wallet::create(['user_id' => $otherUser->id, 'name' => 'Rahasia', 'type' => 'bank']);

    Transaction::create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'amount' => -125000,
        'description' => '=Transaksi pengguna',
        'transaction_date' => '2026-07-10',
    ]);
    Transaction::create([
        'user_id' => $otherUser->id,
        'wallet_id' => $otherWallet->id,
        'amount' => -900000,
        'description' => 'Transaksi pengguna lain',
        'transaction_date' => '2026-07-11',
    ]);

    $response = $this->actingAs($user)
        ->get(route('report.download.excel', ['preset' => 'this_month']))
        ->assertOk()
        ->assertDownload('laporan_dompetra_2026-07-01_sd_2026-07-23.xlsx');

    $temporaryFile = tempnam(sys_get_temp_dir(), 'dompetra-report-').'.xlsx';
    file_put_contents($temporaryFile, $response->streamedContent());

    try {
        $workbook = IOFactory::load($temporaryFile);
        $transactionSheet = $workbook->getSheetByName('Transaksi');

        expect($workbook->getSheetNames())->toBe(['Ringkasan', 'Transaksi'])
            ->and($workbook->getSheetByName('Ringkasan')->getCell('B16')->getValue())->toContain('SUMIFS')
            ->and($transactionSheet->getCell('B6')->getValue())->toBe('=Transaksi pengguna')
            ->and($transactionSheet->getCell('B7')->getValue())->not->toBe('Transaksi pengguna lain')
            ->and($transactionSheet->getAutoFilter()->getRange())->toBe('A5:G6');
    } finally {
        @unlink($temporaryFile);
    }
});

it('downloads a print-ready PDF report for the selected period', function () {
    Carbon::setTestNow('2026-07-23 10:00:00');
    $user = User::factory()->create();
    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Rekening Utama', 'type' => 'bank']);

    foreach (range(1, 31) as $index) {
        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => $index % 8 === 0 ? 450000 : -25000,
            'description' => $index % 10 === 0 ? 'Transfer pengujian' : 'Transaksi laporan '.$index,
            'detail' => $index % 4 === 0 ? 'Catatan panjang untuk menguji pembungkusan tabel PDF' : null,
            'transaction_date' => '2026-07-'.str_pad((string) min($index, 23), 2, '0', STR_PAD_LEFT),
            'transfer_group_id' => $index % 10 === 0 ? 'transfer-'.$index : null,
            'is_balance_adjustment' => $index % 17 === 0,
        ]);
    }

    $response = $this->actingAs($user)
        ->get(route('report.download.pdf', ['preset' => 'this_month']))
        ->assertOk()
        ->assertDownload('laporan_dompetra_2026-07-01_sd_2026-07-23.pdf');

    $content = $response->getContent();
    $pageCount = preg_match_all('/\/Type\s*\/Page\b/', $content);

    expect($content)->toStartWith('%PDF')
        ->and(strlen($content))->toBeGreaterThan(5000)
        ->and($pageCount)->toBeGreaterThanOrEqual(2)
        ->and($pageCount)->toBeLessThanOrEqual(3);
});
