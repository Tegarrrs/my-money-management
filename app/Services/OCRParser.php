<?php

namespace App\Services;

use App\DTO\OCR\ParsedItemDTO;
use App\DTO\OCR\ParsedReceiptDTO;

/**
 * Parses raw OCR-extracted text from a receipt into a ParsedReceiptDTO.
 *
 * Handles common Indonesian receipt patterns including MULTI-LINE formats:
 *  - Single-line:  "Ramen Beef Spicy  86,000"
 *  - Multi-line:   Line 1: "Roti Bakar Coklat"
 *                  Line 2: "27.000 x1          27.000"
 *  - Qty prefix:   "2x Item Name  30.000"
 *  - Date lines:   "Waktu : 26 Mar 26 17:34" or "03 Mei 2026 17:09"
 *  - Summary:      Subtotal / Biaya Layanan / Pajak / Total lines
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
        '/^\s*[-=*_\.]{3,}\s*$/',                              // separator lines (---, ===, ___, ...)
        '/^\s*#[A-Z0-9]+/i',                                   // order/receipt IDs like #073F0DB458
        '/^(no\s*nota|waktu|order|kasir|jenis|nama|jl\.|kec\.|kab\.|kota|alamat|npwp)/i',
        '/^(transfer|tunai|cash|kembali|change|debit|kredit|card|kartu|non\s*tunai)/i',
        '/^(harga\s*jual|\"?mea\s*jual)/i',
        '/total\s*(bayar|belanja)/i',
        '/tota[l!1]\s+(bayar|yg|yang|belanja)/i',
        '/(layanan\s*(konsumen|pelanggan)|konsumen|telp|call|\.co\.id|indomaret\s*co|alfamart)/i',
        '/^[^a-z0-9]*(dilayani|p?e?langgan|customer|member|no\s*meja|meja\s*no|kasir)/i',  // staff/customer info
        '/^(bukan\s*(resi|rest)|resi\s*pembayaran|struk\s*pembayaran)/i',       // receipt title
        '/^[^a-z0-9]*(ol[-\s]?dine|dl[-\s]?dine|dine[-\s]?in|take[-\s]?away|grab|gofood|shopee)/i', // order type lines
        '/^(delivery|pickup|online|offline)\b/i',
        '/^(terima\s*kasih|thank|selamat|silakan)/i',           // thank-you footer
        '/^(no\s*auth|auth[-\s])/i',                            // auth codes
        '/^\s*\d{1,2}[\/:.-]\d{1,2}[\/:.-]\d{2,4}\s*$/i',      // standalone date
        '/^\s*\d{1,2}:\d{2}(:\d{2})?\s*$/i',                   // standalone time
    ];

    /** Lines matching these are treated as summary/fee rows (not items). */
    private const SUMMARY_PATTERNS = [
        'subtotal'       => '/sub\s*total/i',
        'total'          => '/^tota[l!1i]\b(?!\s*(bayar|belanja))/i',
    ];

    /** Fee/tax lines → captured as summary but stored separately */
    private const FEE_PATTERNS = [
        'service_charge' => '/biaya\s*(pelayanan|layanan|service)/i',
        'tax'            => '/(pajak|ppn|pb1)\b/i',
        'rounding'       => '/pembulatan/i',
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

        // First pass: clean and classify lines
        $processedLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $processedLines[] = $line;
        }

        $count = count($processedLines);

        for ($i = 0; $i < $count; $i++) {
            $line = $processedLines[$i];

            // 1. Try to extract date from "Waktu" line or any date-like line
            if ($date === null && preg_match('/waktu\s*[:：]/i', $line)) {
                $date = $this->extractDate($line);
                continue;
            }

            // 1b. Also try to detect date from lines containing date patterns
            if ($date === null) {
                $foundDate = $this->extractDateFromAnyLine($line);
                if ($foundDate !== null) {
                    $date = $foundDate;
                    // Don't continue - the line may also contain other info
                    // but if it's just a date line, skip it
                    if ($this->isDateOnlyLine($line)) {
                        continue;
                    }
                }
            }

            // 2. Try fee/tax lines FIRST (biaya layanan, pajak, pembulatan)
            //    Must come before skip check because skip patterns may overlap
            $feeResult = $this->tryParseFeeLine($line);
            if ($feeResult !== null) {
                [$key, $amount] = $feeResult;
                match ($key) {
                    'service_charge' => $serviceCharge = ($serviceCharge ?? 0) + $amount,
                    'tax'            => $serviceCharge = ($serviceCharge ?? 0) + $amount,
                    'rounding'       => null, // ignore rounding
                    default          => null,
                };
                continue;
            }

            // 3. Try summary rows (subtotal, total)
            $summaryResult = $this->tryParseSummaryLine($line);
            if ($summaryResult !== null) {
                [$key, $amount] = $summaryResult;
                match ($key) {
                    'subtotal' => $subtotal = $amount,
                    'total'    => $total    = $amount,
                    default    => null,
                };
                continue;
            }

            // 4. Skip header / footer noise lines
            if ($this->shouldSkip($line)) {
                continue;
            }

            // 5. Try to parse as a PRICE LINE (e.g. "27.000 x1    27.000")
            //    These are continuation lines belonging to a previous item name line
            $priceLineResult = $this->tryParsePriceLine($line);
            if ($priceLineResult !== null) {
                [$unitPrice, $qty, $totalPrice] = $priceLineResult;

                // Attach to the last item that has no amount yet, or the last item
                if (!empty($items)) {
                    $lastItem = end($items);
                    // If the last item was a "name-only" pending item, complete it
                    if ($lastItem->amount === 0) {
                        array_pop($items);
                        $items[] = new ParsedItemDTO(
                            name:   $lastItem->name,
                            amount: $totalPrice > 0 ? $totalPrice : $unitPrice * $qty,
                            qty:    $qty,
                        );
                        continue;
                    }
                }
                // If no pending item, skip this line (orphan price line)
                continue;
            }

            // 6. Try as a complete item line (name + amount on same line)
            $dto = $this->parseItemLine($line);
            if ($dto !== null) {
                $items[] = $dto;
                continue;
            }

            // 7. If the line looks like just a name (text without a number at the end),
            //    store it as a pending item with amount=0
            //    The next line might be a price line that completes it
            $candidateName = $this->extractItemNameOnly($line);
            if ($candidateName !== null) {
                // If the last item is already a pending name-only item,
                // append this line to it (multi-line item name, e.g.
                // "Air Mineral (Crystalin)" + "Normal")
                if (!empty($items) && end($items)->amount === 0) {
                    $lastItem = array_pop($items);
                    $items[] = new ParsedItemDTO(
                        name:   $lastItem->name . ' ' . $candidateName,
                        amount: 0,
                        qty:    1,
                    );
                } else {
                    $items[] = new ParsedItemDTO(
                        name:   $candidateName,
                        amount: 0,
                        qty:    1,
                    );
                }
            }
        }

        // Post-process: remove any items with amount=0 (unmatched name-only lines)
        $items = array_values(array_filter($items, fn(ParsedItemDTO $item) => $item->amount > 0));

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

        return $this->extractDateFromAnyLine($line);
    }

    /**
     * Try to find a date pattern in any line text.
     */
    private function extractDateFromAnyLine(string $line): ?string
    {
        // Pattern: "03 Mei 2026" or "26 Mar 26"
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

    /**
     * Check if a line is primarily a date (with optional time), nothing else.
     */
    private function isDateOnlyLine(string $line): bool
    {
        // Remove the date part and time part; if nothing meaningful remains, it's date-only
        $stripped = preg_replace('/\d{1,2}\s+[A-Za-z]{3,}\s+\d{2,4}/u', '', $line);
        $stripped = preg_replace('/\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/', '', $stripped);
        $stripped = preg_replace('/\d{1,2}:\d{2}(:\d{2})?/', '', $stripped);
        $stripped = trim($stripped);

        return strlen($stripped) <= 3;
    }

    // ── Skip rules ───────────────────────────────────────────────────

    private function shouldSkip(string $line): bool
    {
        foreach (self::SKIP_PATTERNS as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        // Skip lines that are purely numeric (e.g. phone numbers, IDs)
        if (preg_match('/^\s*[\d\s\-\(\)\.]+\s*$/', $line) && !preg_match('/[.,]\d{3}/', $line)) {
            return true;
        }

        return false;
    }

    // ── Fee/tax lines ────────────────────────────────────────────────

    /**
     * Returns [key, amount] if the line is a fee/tax row, or null otherwise.
     */
    private function tryParseFeeLine(string $line): ?array
    {
        foreach (self::FEE_PATTERNS as $key => $pattern) {
            if (preg_match($pattern, $line)) {
                $extracted = $this->extractNameAndAmount($line);
                $amount = $extracted ? $extracted[1] : 0;
                return [$key, $amount];
            }
        }
        return null;
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

    // ── Price line detection (multi-line receipt support) ─────────────

    /**
     * Detect if a line is a "price line" like:
     *   "27.000 x1          27.000"
     *   "7.000 x1           7.000"
     *   "24.000 x1          24,000"
     *   "27,000 x 1         27,000"
     *
     * Returns [$unitPrice, $qty, $totalPrice] or null.
     */
    private function tryParsePriceLine(string $line): ?array
    {
        // 1. First remove all characters that are NOT digits, dots, commas, x, X, or spaces
        $normalized = preg_replace('/[^\d.,xX\s]/', '', $line);
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized));

        // Pattern: unitPrice xQty [totalPrice]
        // Now that garbage is stripped, "27.000 x1 27.000 | :" becomes "27.000 x1 27.000"
        $pattern = '/^(\d{1,3}(?:[.,]\d{3})*)\s*[xX].*?(\d{1,3}(?:[.,]\d{3})*)$/';
        if (preg_match($pattern, $normalized, $m)) {
            $unitPrice  = $this->normaliseAmount($m[1]);
            $totalPrice = $this->normaliseAmount($m[2]);
            if ($unitPrice > 0 && $totalPrice > 0) {
                // Calculate qty mathematically instead of relying on OCR text
                $qty = (int) round($totalPrice / $unitPrice);
                if ($qty <= 0) $qty = 1;
                return [$unitPrice, $qty, $totalPrice];
            }
        }

        // Pattern without total: "27.000 x1" or "7.000x2"
        $patternNoTotal = '/^\s*(\d{1,3}(?:[.,]\d{3})*)\s*[xX×]\s*(\d+)\s*$/';
        if (preg_match($patternNoTotal, $normalized, $m)) {
            $unitPrice = $this->normaliseAmount($m[1]);
            $qty       = (int) $m[2];
            if ($unitPrice > 0 && $qty > 0) {
                return [$unitPrice, $qty, $unitPrice * $qty];
            }
        }

        // Pattern: just "qty x unitPrice  totalPrice" (qty first)
        $patternQtyFirst = '/^\s*(\d+)\s*[xX×]\s*(\d{1,3}(?:[.,]\d{3})*)\s+(\d{1,3}(?:[.,]\d{3})*)\s*$/';
        if (preg_match($patternQtyFirst, $normalized, $m)) {
            $qty        = (int) $m[1];
            $unitPrice  = $this->normaliseAmount($m[2]);
            $totalPrice = $this->normaliseAmount($m[3]);
            if ($unitPrice > 0 && $qty > 0 && $qty < 100) {
                return [$unitPrice, $qty, $totalPrice];
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
        }

        // Check for trailing standalone qty (Indomaret style: "POCARI SWEAT 350ML    1")
        // The trailing number is qty, NOT part of the item name
        if ($qty === 1 && preg_match('/^(.+?)\s{2,}(\d{1,2})\s*$/u', $nameRaw, $tqm)) {
            $candidateQty = (int) $tqm[2];
            $candidateName = trim($tqm[1]);
            // Only treat as qty if: small number, name still has alpha chars, and
            // the number is separated by multiple spaces (tabular layout)
            if ($candidateQty > 0 && $candidateQty < 100 && preg_match('/[a-zA-Z]/', $candidateName)) {
                $qty = $candidateQty;
                $nameRaw = $candidateName;
            }
        }

        // Leading quantity: "2 Ramen" or "2x Ramen"
        if ($qty === 1 && preg_match('/^(\d+)\.?\s*[xX]?\s+(.+)$/u', $nameRaw, $qm)) {
            $candidateQty = (int) $qm[1];
            if ($candidateQty > 0 && $candidateQty < 100) {
                $qty     = $candidateQty;
                $nameRaw = $qm[2];
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

        // Anti-noise: skip if the name contains NO alphabetic characters at all
        if (!preg_match('/[a-zA-Z]/', $name)) {
            return null;
        }

        // Anti-noise: skip if the "name" looks like a pure number (price without item)
        if (preg_match('/^\d[\d.,\s]*$/', $name)) {
            return null;
        }

        // Anti-noise: skip if name starts with typical price pattern
        if (preg_match('/^\d{1,3}([.,]\d{3})+\s*x/i', $name)) {
            return null;
        }

        return new ParsedItemDTO(name: $name, amount: $amount, qty: $qty);
    }

    // ── Name-only line detection ─────────────────────────────────────

    /**
     * Check if a line is just an item name without any price.
     * Returns the cleaned name, or null if not a valid item name.
     *
     * Valid: "Roti Bakar Coklat", "Air Mineral (Crystalin)"
     * Invalid: "27.000 x1  27.000", "Subtotal", numeric-only lines
     */
    private function extractItemNameOnly(string $line): ?string
    {
        // Must have at least some alpha characters
        if (!preg_match('/[a-zA-Z]{2,}/u', $line)) {
            return null;
        }

        // Must NOT end with a large number (that would be a price line → handled by parseItemLine)
        if (preg_match('/\d{3,}\s*$/', $line)) {
            return null;
        }

        // Must NOT start with a number followed by x (price line)
        if (preg_match('/^\d{1,3}([.,]\d{3})*\s*[xX×]/i', $line)) {
            return null;
        }

        // Must NOT match any skip patterns
        if ($this->shouldSkip($line)) {
            return null;
        }

        // Must NOT match summary or fee patterns
        foreach (self::SUMMARY_PATTERNS as $pattern) {
            if (preg_match($pattern, $line)) {
                return null;
            }
        }
        foreach (self::FEE_PATTERNS as $pattern) {
            if (preg_match($pattern, $line)) {
                return null;
            }
        }

        $name = $this->cleanName(trim($line));

        // Name should be reasonably long (at least 3 chars) and not just noise
        if (strlen($name) < 3) {
            return null;
        }

        // Skip if it explicitly looks like a key-value header (e.g., "Nama Kasir : Tono")
        // but allow it if the colon is just OCR noise at the end of the string
        if (preg_match('/^[a-z\s]+:[\s\w]+$/i', $name)) {
            return null;
        }

        return $name;
    }

    // ── Shared helpers ────────────────────────────────────────────────

    /**
     * Normalize OCR spaces within numbers throughout a line.
     */
    private function normalizeNumberSpaces(string $line): string
    {
        // Many OCR texts have a stray space inside a number: e.g. "132. 300" -> "132.300"
        $line = preg_replace('/(\d+)[.,]\s+(\d+)/', '$1.$2', $line);
        // Normalize OCR space within thousands (very common for Tesseract without commas).
        // For instance "12 710" or "10 900" -> "12.710" or "10.900"
        $line = preg_replace('/(\d+)\s(\d{3})\b/', '$1.$2', $line);
        return $line;
    }

    /**
     * Extract and normalise the last standalone number in a line.
     * Returns an array [$name, $amount] where $name is everything before the amount.
     */
    private function extractNameAndAmount(string $line): ?array
    {
        $line = $this->normalizeNumberSpaces($line);

        // Find all amount-like numbers in the string
        // Match numbers like 58.000, 1.160, 65.500
        $amountRegex = '/(?:Rp\.?)?\s*(?<![.\d,])(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{1,2})?)/iu';
        if (preg_match_all($amountRegex, $line, $matches, PREG_OFFSET_CAPTURE)) {
            // Get the last matched amount
            $lastMatchIndex = count($matches[0]) - 1;
            $amountRaw = $matches[1][$lastMatchIndex][0];
            $amountOffset = $matches[0][$lastMatchIndex][1];

            // Name is everything before the amount
            $nameRaw = substr($line, 0, $amountOffset);
            
            // Clean up trailing noise from name (e.g., "Roti Bakar - " -> "Roti Bakar")
            $nameRaw = trim(preg_replace('/[=\-:]+$/', '', trim($nameRaw)));

            $amount = $this->normaliseAmount($amountRaw);

            if ($nameRaw !== '') {
                return [$nameRaw, $amount];
            }
        }

        return null;
    }

    private function cleanName(string $raw): string
    {
        // Remove FREE REFILL parenthetical suffixes
        $cleaned = preg_replace('/\s*\(FREE\s+REFILL\)/i', '', $raw);
        // Remove "Normal" suffix (common for drink sizes)
        $cleaned = preg_replace('/\s+Normal$/i', '', $cleaned);
        
        // Remove trailing typical OCR noise
        $cleaned = preg_replace('/(?:\s*[:\/\}\]eRs]+)+\s*$/i', '', $cleaned ?? $raw);

        // Strip everything after a pipe character since items never contain pipes
        if (($pos = strpos($cleaned, '|')) !== false) {
            $cleaned = substr($cleaned, 0, $pos);
        }

        // Remove trailing punctuation
        $cleaned = rtrim(trim($cleaned), ':.');
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
