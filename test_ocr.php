<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parser = app(App\Services\OCRParser::class);

class OCRParserTest extends App\Services\OCRParser {
    public function extractNameAndAmount(string $line): ?array
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
            $amount = $this->normaliseAmount_pub($m[2]);
            return [$name, $amount];
        }

        // Fallback: plain integer at end
        $fallbackPattern = '/^(.*?)\s+(\d+)(?:\s*[^0-9]{1,8})?$/u';
        if (preg_match($fallbackPattern, $line, $m)) {
            $name   = trim($m[1]);
            $amount = $this->normaliseAmount_pub($m[2]);
            return [$name, $amount];
        }

        return null;
    }

    public function normaliseAmount_pub($raw) {
        if (preg_match('/,\d{1,2}$/', $raw)) {
            $raw = preg_replace('/,\d+$/', '', $raw);
            $raw = str_replace('.', '', $raw);
        } elseif (preg_match('/\.\d{1,2}$/', $raw)) {
            $raw = preg_replace('/\.\d+$/', '', $raw);
            $raw = str_replace(',', '', $raw);
        } else {
            $raw = str_replace(['.', ','], '', $raw);
        }
        return (int) $raw;
    }

    public function parseItemLinePublic(string $line) {
        $extracted = $this->extractNameAndAmount($line);
        if (!$extracted) return null;
        
        $nameRaw = $extracted[0];
        $amount  = $extracted[1];
        
        $qty = 1;
        if (preg_match('/^(.*?)\s+(\d+)\s+([\d.,]+)[)]?$/u', $nameRaw, $tm)) {
            $candidateQty = (int) $tm[2];
            $unitPriceRaw = $this->normaliseAmount_pub($tm[3]);
            if ($candidateQty > 0 && $candidateQty < 100 && $unitPriceRaw > 100) {
                // We found a likely inline qty + unit price
                $qty = $candidateQty;
                $nameRaw = $tm[1];

                // OCR error recovery
                if ($amount < $unitPriceRaw) {
                    $amount = $qty * $unitPriceRaw;
                }
            }
        }

        return [$nameRaw, $amount, $qty];
    }
}

$test = new OCRParserTest();
print_r($test->parseItemLinePublic("GLITE BLUE IT FLEXI 1 12700) 12 710"));
print_r($test->parseItemLinePublic("DK KACANG SUKRO 956 1 19900 10, gu"));
print_r($test->parseItemLinePublic("PPN: DPP= 19, 489 PPN 2, 339 a"));
