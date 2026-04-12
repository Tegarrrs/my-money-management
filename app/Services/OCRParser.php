<?php

namespace App\Services;

use App\DTO\OCR\ParsedItemDTO;

/**
 * Parses raw OCR-extracted text from a receipt into a list of ParsedItemDTO.
 *
 * Handles common Indonesian receipt patterns:
 *  - "Item Name  15.000"
 *  - "Item Name  15,000"
 *  - Lines containing quantity like "2x Item Name  30.000"
 *  - Total / subtotal / tax lines are skipped
 */
class OCRParser
{
    /** Lines matching these keywords are treated as noise and skipped. */
    private const SKIP_PATTERNS = [
        '/^(total|subtotal|sub\s*total|pajak|ppn|ppn|tax|service|diskon|discount|kembalian|change|cash|tunai|kartu|card|debit|kredit|retur|struk|bill|receipt|nota|terima\s*kasih|thank)/i',
        '/^\s*[-=*]+\s*$/', // separator lines
        '/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/',  // date-only lines
    ];

    /**
     * @param  string  $rawText  Raw text extracted by the OCR engine.
     * @return ParsedItemDTO[]
     */
    public function parse(string $rawText): array
    {
        $lines   = preg_split('/\r?\n/', trim($rawText));
        $results = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if ($this->shouldSkip($line)) {
                continue;
            }

            $dto = $this->parseLine($line);
            if ($dto !== null) {
                $results[] = $dto;
            }
        }

        return $results;
    }

    // ──────────────────────────────────────────────────────────────────

    private function shouldSkip(string $line): bool
    {
        foreach (self::SKIP_PATTERNS as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Try to extract (name, amount) from a single text line.
     *
     * Supported amount formats:
     *   15000  |  15.000  |  15,000  |  Rp15.000  |  Rp 15.000
     */
    private function parseLine(string $line): ?ParsedItemDTO
    {
        // Strip currency prefix anywhere in the line
        $line = preg_replace('/\bRp\.?\s*/i', '', $line);

        // Pattern: capture everything before a final standalone number
        // Amount can be: 15000 | 15.000 | 15,000 | 15.000,00
        $pattern = '/^(.+?)\s{2,}([\d]{1,3}(?:[.,][\d]{3})*(?:[.,]\d{1,2})?)$/u';

        if (! preg_match($pattern, $line, $m)) {
            // Fallback: try single-space separator
            $pattern2 = '/^(.+?)\s+([\d]{1,3}(?:[.,][\d]{3})+)$/u';
            if (! preg_match($pattern2, $line, $m)) {
                return null;
            }
        }

        $name      = $this->cleanName(trim($m[1]));
        $amount    = $this->normaliseAmount($m[2]);

        if ($name === '' || $amount <= 0) {
            return null;
        }

        return new ParsedItemDTO(name: $name, amount: $amount);
    }

    private function cleanName(string $raw): string
    {
        // Remove leading quantity like "2x " or "2 x "
        $cleaned = preg_replace('/^\d+\s*[xX]\s*/', '', $raw);
        // Remove trailing colons or dots
        $cleaned = rtrim(trim($cleaned ?? $raw), ':.');
        return $cleaned;
    }

    private function normaliseAmount(string $raw): int
    {
        // Determine decimal separator: if last separator is comma followed by 1-2 digits, it's decimal
        if (preg_match('/,\d{1,2}$/', $raw)) {
            // e.g. "15.000,00" → strip decimal part, then strip dots
            $raw = preg_replace('/,\d+$/', '', $raw);
            $raw = str_replace('.', '', $raw);
        } elseif (preg_match('/\.\d{1,2}$/', $raw)) {
            // e.g. "15,000.50" → strip decimal, strip commas
            $raw = preg_replace('/\.\d+$/', '', $raw);
            $raw = str_replace(',', '', $raw);
        } else {
            // Plain separators only – treat both as thousands separators
            $raw = str_replace(['.', ','], '', $raw);
        }

        return (int) $raw;
    }
}
