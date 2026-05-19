<?php

namespace App\Infrastructure\OCR;

use App\Contracts\ReceiptScannerInterface;
use App\DTO\OCR\ParsedItemDTO;
use App\DTO\OCR\ParsedReceiptDTO;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiReceiptScanner implements ReceiptScannerInterface
{
    private const MAX_IMAGE_SIZE_KB = 500;
    private const COMPRESS_QUALITY = 75;
    private const MAX_DIMENSION = 1200;
    private const API_TIMEOUT_SEC = 45;
    private const MAX_RETRIES = 3;

    public function scan(UploadedFile $image): ParsedReceiptDTO
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('Gemini API key is not configured. Please set GEMINI_API_KEY in .env');
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $apiKey;

        [$imageData, $mimeType] = $this->compressImage($image);
        $base64Image = base64_encode($imageData);

        $prompt = <<<PROMPT
You are an expert receipt data extractor. Extract the items, subtotal, service charge, tax, and total from this Indonesian receipt.
Return the data STRICTLY as a JSON object matching this exact schema, without any markdown formatting or code blocks:
{
    "date": "YYYY-MM-DD",
    "items": [
        {
            "name": "Item Name",
            "qty": 1,
            "amount": 25000
        },
        {
            "name": "Pajak Resto / PPN / PB1",
            "qty": 1,
            "amount": 5000
        }
    ],
    "subtotal": 50000,
    "serviceCharge": 2500,
    "tax": 5000,
    "total": 57500
}
Rules:
- Amounts must be integers (no decimals, no thousands separators).
- If an exact field is not on the receipt, do your best to calculate it or use 0.
- Ignore generic headers/footers (e.g. "Terima Kasih", store addresses, cash tendered).
- CRITICAL: All extra charges found below the subtotal (Tax, Service Charge, Rounding/Pembulatan, Delivery, etc.) MUST be inserted into the `items` array so they can be saved as transactions.
- ONLY return the raw JSON object, no backticks, no markdown.
PROMPT;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64Image,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.0,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = $this->sendWithRetry($url, $payload);

        $responseData = $response->json();

        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            Log::error('[GeminiReceiptScanner] Unexpected response format', [
                'response' => $responseData,
            ]);
            throw new RuntimeException('Unexpected response format from Gemini API');
        }

        $jsonText = $responseData['candidates'][0]['content']['parts'][0]['text'];
        $jsonText = preg_replace('/^```json\s*/i', '', $jsonText);
        $jsonText = preg_replace('/```\s*$/', '', $jsonText);
        $jsonText = trim($jsonText);

        $data = json_decode($jsonText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('[GeminiReceiptScanner] JSON parsing error', [
                'jsonText' => $jsonText,
                'error' => json_last_error_msg(),
            ]);
            throw new RuntimeException('Failed to parse JSON response from Gemini');
        }

        $items = [];
        foreach ($data['items'] ?? [] as $item) {
            $items[] = new ParsedItemDTO(
                name: $item['name'] ?? 'Unknown Item',
                amount: (int) ($item['amount'] ?? 0),
                qty: (int) ($item['qty'] ?? 1),
            );
        }

        return new ParsedReceiptDTO(
            date: $data['date'] ?? null,
            items: $items,
            subtotal: $data['subtotal'] ?? null,
            serviceCharge: $data['serviceCharge'] ?? null,
            total: $data['total'] ?? null,
        );
    }

    /**
     * Kirim request ke Gemini dengan retry + exponential backoff.
     * Menangani: timeout, 429 rate limit, 503 overload.
     */
    private function sendWithRetry(string $url, array $payload): \Illuminate\Http\Client\Response
    {
        $attempt = 0;

        while (true) {
            $attempt++;
            Log::info('[GeminiReceiptScanner] Sending request to Gemini API', [
                'attempt' => $attempt,
            ]);

            try {
                $response = Http::timeout(self::API_TIMEOUT_SEC)->post($url, $payload);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // cURL error: timeout atau tidak bisa konek
                $isTimeout = str_contains($e->getMessage(), 'timed out')
                    || str_contains($e->getMessage(), 'cURL error 28');

                if ($isTimeout) {
                    Log::warning('[GeminiReceiptScanner] Request timeout', [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);

                    if ($attempt >= self::MAX_RETRIES) {
                        throw new RuntimeException(
                            'Koneksi ke Gemini API timeout setelah ' . self::MAX_RETRIES . 'x percobaan. ' .
                            'Kemungkinan quota harian habis atau server sedang sibuk. Coba lagi nanti.'
                        );
                    }
                } else {
                    // Bukan timeout — bisa jadi Gemini diblokir dari server ini
                    Log::error('[GeminiReceiptScanner] Connection error', [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);

                    if ($attempt >= self::MAX_RETRIES) {
                        throw new RuntimeException(
                            'Tidak dapat terhubung ke Gemini API: ' . $e->getMessage() . '. ' .
                            'Pastikan server memiliki akses ke generativelanguage.googleapis.com.'
                        );
                    }
                }

                $this->waitBeforeRetry($attempt);
                continue;
            }

            // --- Handle HTTP error responses ---
            $status = $response->status();

            if ($status === 429) {
                Log::warning('[GeminiReceiptScanner] Rate limit hit (429)', [
                    'attempt' => $attempt,
                    'body' => $response->body(),
                ]);

                if ($attempt >= self::MAX_RETRIES) {
                    throw new RuntimeException(
                        'Quota atau rate limit Gemini API habis (429). ' .
                        'Free tier hanya 50 request/hari. Coba lagi besok atau upgrade ke paid plan.'
                    );
                }

                $this->waitBeforeRetry($attempt);
                continue;
            }

            if ($status === 503) {
                Log::warning('[GeminiReceiptScanner] Gemini overloaded (503)', [
                    'attempt' => $attempt,
                    'body' => $response->body(),
                ]);

                if ($attempt >= self::MAX_RETRIES) {
                    throw new RuntimeException(
                        'Gemini API sedang kelebihan beban (503). Coba beberapa menit lagi.'
                    );
                }

                $this->waitBeforeRetry($attempt);
                continue;
            }

            if (!$response->successful()) {
                Log::error('[GeminiReceiptScanner] API Error', [
                    'status' => $status,
                    'body' => $response->body(),
                ]);
                throw new RuntimeException(
                    'Gemini API error (' . $status . '): ' . $response->body()
                );
            }

            // Sukses
            Log::info('[GeminiReceiptScanner] Request successful', ['attempt' => $attempt]);
            return $response;
        }
    }

    /**
     * Tunggu sebelum retry — exponential backoff: 2s, 4s, 8s, dst.
     */
    private function waitBeforeRetry(int $attempt): void
    {
        $seconds = pow(2, $attempt); // 2, 4, 8, ...
        Log::info('[GeminiReceiptScanner] Waiting before retry', [
            'wait_seconds' => $seconds,
            'next_attempt' => $attempt + 1,
        ]);
        sleep($seconds);
    }

    /**
     * Kompres gambar jika ukurannya melebihi MAX_IMAGE_SIZE_KB.
     * Mengembalikan [string $imageData, string $mimeType].
     */
    private function compressImage(UploadedFile $image): array
    {
        $originalPath = $image->getPathname();
        $originalSize = $image->getSize();

        if ($originalSize <= self::MAX_IMAGE_SIZE_KB * 1024) {
            Log::info('[GeminiReceiptScanner] Image size OK, skipping compression', [
                'size_kb' => round($originalSize / 1024, 1),
            ]);
            return [file_get_contents($originalPath), $image->getMimeType()];
        }

        Log::info('[GeminiReceiptScanner] Compressing image', [
            'original_size_kb' => round($originalSize / 1024, 1),
        ]);

        $mime = $image->getMimeType();
        $src = match (true) {
            str_contains($mime, 'jpeg') => imagecreatefromjpeg($originalPath),
            str_contains($mime, 'png') => imagecreatefrompng($originalPath),
            default => imagecreatefromstring(file_get_contents($originalPath)),
        };

        if ($src === false) {
            Log::warning('[GeminiReceiptScanner] GD failed to load image, sending original');
            return [file_get_contents($originalPath), $mime];
        }

        [$origW, $origH] = [imagesx($src), imagesy($src)];
        $scale = min(self::MAX_DIMENSION / $origW, self::MAX_DIMENSION / $origH, 1.0);
        $newW = (int) round($origW * $scale);
        $newH = (int) round($origH * $scale);

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        ob_start();
        imagejpeg($dst, null, self::COMPRESS_QUALITY);
        $compressed = ob_get_clean();
        imagedestroy($dst);

        Log::info('[GeminiReceiptScanner] Compression done', [
            'original_kb' => round($originalSize / 1024, 1),
            'compressed_kb' => round(strlen($compressed) / 1024, 1),
            'dimension' => "{$newW}x{$newH}",
        ]);

        return [$compressed, 'image/jpeg'];
    }
}