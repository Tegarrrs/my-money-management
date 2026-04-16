<?php

namespace App\Services;

use App\DTO\OCR\ParsedItemDTO;
use App\DTO\OCR\ParsedReceiptDTO;

/**
 * Parses raw OCR-extracted text from a receipt into a ParsedReceiptDTO.
 *
 * Handles common Indonesian receipt patterns:
 *  - "2 Ramen Beef Spicy  86,000"  (qty at start, total at end)
 *  - "2x Item Name  30.000"
 *  - "(FREE REFILL)" suffixes are stripped
 *  - Waktu / date lines like "Waktu : 26 Mar 26 17:34" are detected
 *  - Subtotal / Biaya Pelayanan / Total lines are parsed separately
 */
class OCRParser
{
    // ── Month name → number map (Indonesian & abbreviated English) ───
    private const MONTH_MAP = [
        'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
        'mei' => '05', 'may' => '05', 'jun' => '06', 'jul' => '07',
        'agu' => '08', 'aug' => '08', 'sep' => '09', 'okt' => '10',
        'oct' => '10', 'nov' => '11', 'des' => '12', 'dec' => '12',
    ];

    /** Lines matching these keywords are NOT parsed as items. */
    private const SKIP_PATTERNS = [
        '/^\s*[-=*]+\s*$/',                           // separator lines
        '/^(no\s*nota|waktu|order|kasir|jenis|nama|jl\.|kec\.|kab\.|kota|alamat|npwp)/i', // receipt header fields
        '/^(transfer|tunai|cash|kembali|change|debit|kredit|card|kartu|non\s*tunai|harga\s*jual|\"?mea\s*jual)\b/i',
        '/total\s*bayar/i',
        '/tota[l!1]\s+(bayar|yg|yang|belanja)/i',       // ocr typo bypass
        '/(layanan|konsumen|telp|call|\.co\.id|indomaret\s*co|alfamart)/i', // common footer noise
    ];

    /** Lines matching these are treated as summary rows (subtotal / total). */
    private const SUMMARY_PATTERNS = [
        'subtotal'       => '/sub\s*total/i',
        'total'          => '/^tota[l!1i]\b/i',
    ];

    // ─────────────────────────────────────────────────────────────────

    public function parse(string $rawText): ParsedReceiptDTO
    {
        $lines = preg_split('/\r?\n/', trim($rawText));

        $date          = null;
        $items         = [];
        $subtotal      = null;
        $serviceCharge = null;
        $total         = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // 1. Try to extract date from "Waktu" line
            if ($date === null && preg_match('/waktu\s*[:：]/i', $line)) {
                $date = $this->extractDate($line);
                continue;
            }

            // 2. Skip header / footer noise lines
            if ($this->shouldSkip($line)) {
                continue;
            }

            // 3. Try summary rows (subtotal, service charge, total)
            $summaryAmount = $this->tryParseSummaryLine($line);
            if ($summaryAmount !== null) {
                [$key, $amount] = $summaryAmount;
                match ($key) {
                    'subtotal'       => $subtotal      = $amount,
                    'service_charge' => $serviceCharge = $amount,
                    'total'          => $total         = $amount,
                    default          => null,
                };
                continue;
            }

            // 4. Try item line
            $dto = $this->parseItemLine($line);
            if ($dto !== null) {
                $items[] = $dto;
            }
        }

        return new ParsedReceiptDTO(
            date:          $date,
            items:         $items,
            subtotal:      $subtotal,
            serviceCharge: $serviceCharge,
            total:         $total,
        );
    }

    // ── Date extraction ──────────────────────────────────────────────

    /**
     * Parse a "Waktu : 26 Mar 26 17:34" style line into "YYYY-MM-DD".
     * Also handles "26/03/2026", "2026-03-26" etc.
     */
    private function extractDate(string $line): ?string
    {
        // Strip the key prefix (e.g. "Waktu :")
        $line = preg_replace('/^[^:：]+[:：]\s*/u', '', $line);

        // Pattern: "26 Mar 26" or "26 Mar 2026"
        if (preg_match('/(\d{1,2})\s+([A-Za-z]{3,})\s+(\d{2,4})/u', $line, $m)) {
            $day   = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = self::MONTH_MAP[strtolower(substr($m[2], 0, 3))] ?? null;
            $year  = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            if ($month) {
                return "{$year}-{$month}-{$day}";
            }
        }

        // Pattern: "26/03/2026" or "26-03-2026"
        if (preg_match('/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})/', $line, $m)) {
            $day   = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year  = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            return "{$year}-{$month}-{$day}";
        }

        return null;
    }

    // ── Skip rules ───────────────────────────────────────────────────

    private function shouldSkip(string $line): bool
    {
        foreach (self::SKIP_PATTERNS as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }
        return false;
    }

    // ── Summary lines ─────────────────────────────────────────────────

    /**
     * Returns [key, amount] if the line is a summary row, or null otherwise.
     *
     * @return array{0:string,1:int}|null
     */
    private function tryParseSummaryLine(string $line): ?array
    {
        $extracted = $this->extractNameAndAmount($line);
        if (!$extracted) {
            return null;
        }

        $amount = $extracted[1];

        // Check if the overall line matches a summary pattern
        foreach (self::SUMMARY_PATTERNS as $key => $pattern) {
            if (preg_match($pattern, $line)) {
                return [$key, $amount];
            }
        }
        return null;
    }

    // ── Item lines ────────────────────────────────────────────────────

    /**
     * Try to extract a ParsedItemDTO from a receipt item line.
     *
     * Supported formats:
     *   "2 Ramen Beef Spicy  86,000"
     *   "2x Ramen Beef Spicy  86.000"
     *   "1 Ocha Tea (FREE REFILL)  9,000"
     *   "Nasi Goreng  35.000"   ← no qty → qty=1
     */
    private function parseItemLine(string $line): ?ParsedItemDTO
    {
        // Strip currency prefix
        $line = preg_replace('/\bRp\.?\s*/i', '', $line);

        $extracted = $this->extractNameAndAmount($line);
        if (!$extracted) {
            return null;
        }

        $nameRaw = $extracted[0];
        $amount  = $extracted[1];

        if ($amount <= 0) {
            return null;
        }

        // Check for inline qty and unit price anywhere at the end of the nameRaw
        // Example: "GLITE BLUE IT FLEXI 1 12700) 12" or "DK KACANG SUKRO 956 1 19900"
        $qty = 1;
        if (preg_match('/^(.*?)\s+(\d+)\s+([\d.,]+)[)]?$/u', $nameRaw, $tm)) {
            $candidateQty = (int) $tm[2];
            $unitPriceRaw = $this->normaliseAmount($tm[3]);
            if ($candidateQty > 0 && $candidateQty < 100 && $unitPriceRaw > 100) {
                // We found a likely inline qty + unit price
                $qty = $candidateQty;
                $nameRaw = $tm[1];

                // OCR error recovery: If the extracted total amount is strangely small
                // but the inline unit price is natural, we override the amount!
                if ($amount < $unitPriceRaw) {
                    $amount = $qty * $unitPriceRaw;
                }
            }
        } else {
            // Original fallback for leading quantity: "2 Ramen"
            if (preg_match('/^(\d+)\.?\s*[xX]?\s+(.+)$/u', $nameRaw, $qm)) {
                $candidateQty = (int) $qm[1];
                if ($candidateQty > 0 && $candidateQty < 100) {
                    $qty     = $candidateQty;
                    $nameRaw = $qm[2];
                }
            }
        }

        $name = $this->cleanName($nameRaw);

        if ($name === '') {
            return null;
        }

        // Anti-noise heuristic:
        // Ignore extremely short names unless they match common short item names
        if (strlen($name) <= 2) {
            if (!preg_match('/^(es|te|mi|ro|pb|sc)$/i', $name)) {
                return null;
            }
        }

        return new ParsedItemDTO(name: $name, amount: $amount, qty: $qty);
    }

    // ── Shared helpers ────────────────────────────────────────────────

    /**
     * Extract and normalise the last standalone number in a line.
     * Returns an array [$name, $amount] where $name is everything before the amount.
     */
    private function extractNameAndAmount(string $line): ?array
    {
        // Many OCR texts have a stray space inside a number: e.g. "132. 300" -> "132.300"
        $line = preg_replace('/(\d+)[.,]\s+(\d+)/', '$1.$2', $line);
        // Normalize OCR space within thousands (very common for Tesseract without commas).
        // For instance "12 710" or "10 900" -> "12.710" or "10.900"
        $line = preg_replace('/(\d+)\s(\d{3})\b/', '$1.$2', $line);

        // Match text followed by a large number at the end, allowing up to 8 chars of noise
        $pattern = '/^(.*?)\s+(?<![.\d,])(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{1,2})?)(?:\s*[^0-9]{1,8})?$/u';
        if (preg_match($pattern, $line, $m)) {
            $name   = trim($m[1]);
            $amount = $this->normaliseAmount($m[2]);
            return [$name, $amount];
        }

        // Fallback: plain integer at end
        $fallbackPattern = '/^(.*?)\s+(\d+)(?:\s*[^0-9]{1,8})?$/u';
        if (preg_match($fallbackPattern, $line, $m)) {
            $name   = trim($m[1]);
            $amount = $this->normaliseAmount($m[2]);
            return [$name, $amount];
        }

        return null;
    }

    private function cleanName(string $raw): string
    {
        // Remove FREE REFILL parenthetical suffixes
        $cleaned = preg_replace('/\s*\(FREE\s+REFILL\)/i', '', $raw);
        // Remove other parentheticals that look like notes (optional, conservative)
        // Remove trailing colons or dots
        $cleaned = rtrim(trim($cleaned ?? $raw), ':.');
        return $cleaned;
    }

    private function normaliseAmount(string $raw): int
    {
        // "15.000,00" → decimal comma
        if (preg_match('/,\d{1,2}$/', $raw)) {
            $raw = preg_replace('/,\d+$/', '', $raw);
            $raw = str_replace('.', '', $raw);
        // "15,000.50" → decimal dot
        } elseif (preg_match('/\.\d{1,2}$/', $raw)) {
            $raw = preg_replace('/\.\d+$/', '', $raw);
            $raw = str_replace(',', '', $raw);
        } else {
            // Plain thousands separators only
            $raw = str_replace(['.', ','], '', $raw);
        }

        return (int) $raw;
    }
}
