<?php

use App\Services\Report\GeminiReportAnalyzer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('uses structured Gemini output for the report resume', function () {
    config()->set('services.gemini.api_key', 'test-key');
    config()->set('services.gemini.report_model', 'gemini-flash-latest');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'summary' => 'Pengeluaran masih terkendali.',
                            'highlights' => ['Makanan adalah kategori terbesar.'],
                            'recommendations' => ['Tetapkan anggaran makan mingguan.'],
                        ]),
                    ]],
                ],
            ]],
        ]),
    ]);

    $result = app(GeminiReportAnalyzer::class)->analyze([
        'period' => ['start' => '2026-07-01', 'end' => '2026-07-31'],
        'summary' => ['income' => 2000000, 'expense' => 500000, 'balance' => 1500000],
        'transaction_count' => 2,
        'expense_transaction_count' => 1,
        'categories' => [['category' => 'Makanan', 'total' => 500000, 'percentage' => 100]],
        'largest_expenses' => [['description' => 'Belanja', 'amount' => 500000]],
    ], 123);

    expect($result['source'])->toBe('gemini')
        ->and($result['summary'])->toBe('Pengeluaran masih terkendali.')
        ->and($result['highlights'])->toBe(['Makanan adalah kategori terbesar.'])
        ->and($result['recommendations'])->toBe(['Tetapkan anggaran makan mingguan.']);

    Http::assertSentCount(1);
});
