<?php

namespace App\Services\Report;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiReportAnalyzer
{
    public function analyze(array $data, int $userId, bool $force = false): array
    {
        $fallback = $this->fallback($data);

        if (($data['transaction_count'] ?? 0) === 0) {
            return [...$fallback, 'status' => 'no_data'];
        }

        if (empty(config('services.gemini.api_key'))) {
            return [...$fallback, 'status' => 'not_configured'];
        }

        $cacheKey = $this->cacheKey($data, $userId);
        $cached = Cache::get($cacheKey);

        if (! $force && is_array($cached)) {
            return $cached;
        }

        try {
            $insight = $this->requestInsight($data);
            Cache::put($cacheKey, $insight, now()->addHours(6));

            return $insight;
        } catch (Throwable $exception) {
            Log::warning('[GeminiReportAnalyzer] Falling back to local analysis', [
                'message' => $exception->getMessage(),
            ]);

            return [...$fallback, 'status' => 'request_failed'];
        }
    }

    public function cachedOrFallback(array $data, int $userId): array
    {
        $cached = Cache::get($this->cacheKey($data, $userId));

        return is_array($cached)
            ? $cached
            : [...$this->fallback($data), 'status' => 'not_requested'];
    }

    private function requestInsight(array $data): array
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.report_model', 'gemini-flash-latest');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $prompt = <<<'PROMPT'
Anda adalah analis keuangan pribadi Indonesia. Analisis data laporan berikut secara objektif dan ringkas.
Jangan mengarang angka atau asumsi di luar data. Nominal harus ditulis dalam format Rupiah yang mudah dibaca.
Kembalikan JSON mentah saja dengan skema:
{
  "summary": "2-3 kalimat rangkuman kondisi keuangan",
  "highlights": ["maksimal 3 temuan penting berbasis angka"],
  "recommendations": ["maksimal 3 saran spesifik dan realistis"]
}

DATA:
PROMPT;

        $response = Http::timeout(20)
            ->retry(2, 500, throw: false)
            ->post($url, [
                'contents' => [[
                    'parts' => [[
                        'text' => $prompt.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE),
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        $response->throw();

        $text = $response->json('candidates.0.content.parts.0.text');
        $decoded = is_string($text) ? json_decode(trim($text), true) : null;

        if (! is_array($decoded) || ! is_string($decoded['summary'] ?? null)) {
            throw new \RuntimeException('Format respons analisis Gemini tidak valid.');
        }

        return [
            'summary' => trim($decoded['summary']),
            'highlights' => $this->cleanList($decoded['highlights'] ?? []),
            'recommendations' => $this->cleanList($decoded['recommendations'] ?? []),
            'source' => 'gemini',
            'status' => 'ready',
            'analyzed_at' => now()->toIso8601String(),
        ];
    }

    private function fallback(array $data): array
    {
        $income = (float) ($data['summary']['income'] ?? 0);
        $expense = (float) ($data['summary']['expense'] ?? 0);
        $balance = $income - $expense;
        $topCategory = $data['categories'][0] ?? null;
        $topExpense = $data['largest_expenses'][0] ?? null;

        if (($data['transaction_count'] ?? 0) === 0) {
            return [
                'summary' => 'Belum ada transaksi pada periode ini, sehingga belum ada pola keuangan yang dapat dianalisis.',
                'highlights' => [],
                'recommendations' => ['Tambahkan transaksi atau pilih periode lain untuk mendapatkan analisis.'],
                'source' => 'local',
            ];
        }

        $summary = $balance >= 0
            ? 'Arus kas periode ini surplus '.$this->rupiah($balance).'. Total pemasukan masih lebih besar daripada pengeluaran.'
            : 'Arus kas periode ini defisit '.$this->rupiah(abs($balance)).'. Pengeluaran lebih besar daripada pemasukan.';

        $highlights = [];
        if ($topCategory) {
            $highlights[] = "Kategori terbesar adalah {$topCategory['category']} sebesar {$this->rupiah($topCategory['total'])} ({$topCategory['percentage']}%).";
        }
        if ($topExpense) {
            $highlights[] = "Transaksi pengeluaran terbesar adalah {$topExpense['description']} sebesar {$this->rupiah($topExpense['amount'])}.";
        }
        if ($income > 0) {
            $highlights[] = 'Rasio pengeluaran terhadap pemasukan mencapai '.number_format(($expense / $income) * 100, 1, ',', '.').'%.';
        }

        $recommendations = [];
        if ($topCategory) {
            $recommendations[] = "Tinjau rincian kategori {$topCategory['category']} dan tentukan batas anggaran untuk periode berikutnya.";
        }
        $recommendations[] = $balance < 0
            ? 'Prioritaskan pengurangan pengeluaran non-esensial sampai arus kas kembali positif.'
            : 'Pertahankan surplus dan alokasikan sebagian ke tabungan atau dana darurat.';

        return [
            'summary' => $summary,
            'highlights' => array_slice($highlights, 0, 3),
            'recommendations' => array_slice($recommendations, 0, 3),
            'source' => 'local',
        ];
    }

    private function cleanList(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_slice(array_filter(array_map(
            fn ($item) => is_string($item) ? trim($item) : null,
            $items
        )), 0, 3));
    }

    private function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    private function cacheKey(array $data, int $userId): string
    {
        $fingerprint = hash('sha256', json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE));

        return "report-insight:{$userId}:{$fingerprint}";
    }
}
