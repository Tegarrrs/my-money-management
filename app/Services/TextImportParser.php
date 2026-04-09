<?php

namespace App\Services;

/**
 * Parses raw Indonesian expense notes into structured transaction arrays.
 *
 * Rules mirror the prompt specification exactly:
 *  - Date headers like "1 Mei 2025" define the current date context.
 *  - Each subsequent non-empty line is parsed as "[description] [amount]".
 *  - Amounts like "15.000" are normalised to integer 15000.
 *  - Lines without a valid numeric amount are skipped.
 */
class TextImportParser
{
    private const MONTH_MAP = [
        'januari'   => '01', 'februari' => '02', 'maret'    => '03',
        'april'     => '04', 'mei'      => '05', 'juni'     => '06',
        'juli'      => '07', 'agustus'  => '08', 'september'=> '09',
        'oktober'   => '10', 'november' => '11', 'desember' => '12',
    ];

    /**
     * @return array<int, array{date: string, description: string, amount: int}>
     */
    public function parse(string $rawText): array
    {
        $lines   = preg_split('/\r?\n/', trim($rawText));
        $results = [];
        $currentDate = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // ── Try to parse as a date header ─────────────────────────────
            $date = $this->tryParseDate($line);
            if ($date !== null) {
                $currentDate = $date;
                continue;
            }

            // ── If we don't have a date yet, skip ─────────────────────────
            if ($currentDate === null) {
                continue;
            }

            // ── Try to parse as a transaction line ────────────────────────
            $tx = $this->tryParseLine($line, $currentDate);
            if ($tx !== null) {
                $results[] = $tx;
            }
        }

        return $results;
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Attempts to parse a line as "D MonthName YYYY".
     * Returns "YYYY-MM-DD" string or null.
     */
    private function tryParseDate(string $line): ?string
    {
        // Pattern: optional day-of-week prefix, e.g. "Senin, 5 Mei 2025" or "5 Mei 2025"
        $pattern = '/^(?:[a-zA-Z]+,\s*)?(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$/ui';

        if (! preg_match($pattern, $line, $m)) {
            return null;
        }

        $monthName = mb_strtolower(trim($m[2]));
        if (! array_key_exists($monthName, self::MONTH_MAP)) {
            return null;
        }

        $day   = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $month = self::MONTH_MAP[$monthName];
        $year  = $m[3];

        return "{$year}-{$month}-{$day}";
    }

    /**
     * Attempts to parse "[description] [amount]" from a line.
     * Amount format: digits optionally separated by dots (e.g. "15.000").
     * Returns a transaction array or null.
     *
     * @return array{date: string, description: string, amount: int}|null
     */
    private function tryParseLine(string $line, string $date): ?array
    {
        // Match: anything, then whitespace, then a number like 15.000 or 15000 at end
        // Also handles "15.000,00" — we strip decimals after comma.
        $pattern = '/^(.+?)\s+([\d]{1,3}(?:\.[\d]{3})*(?:,\d+)?)$/u';

        if (! preg_match($pattern, $line, $m)) {
            return null;
        }

        $description = trim($m[1]);
        $rawAmount   = $m[2];

        // Normalise: remove trailing ",xx" cents, then strip dots
        $rawAmount = preg_replace('/,\d+$/', '', $rawAmount);  // remove cents
        $rawAmount = str_replace('.', '', $rawAmount);          // remove thousand separators

        $amount = (int) $rawAmount;

        if ($amount <= 0 || $description === '') {
            return null;
        }

        return [
            'date'        => $date,
            'description' => $description,
            'amount'      => $amount,
        ];
    }
}
