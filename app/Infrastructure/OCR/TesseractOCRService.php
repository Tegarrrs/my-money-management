<?php

namespace App\Infrastructure\OCR;

use App\Infrastructure\OCR\Contracts\OCRServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Tesseract-based OCR engine implementation.
 *
 * Requirements (server-side):
 *  - Tesseract OCR installed and available in PATH  (tesseract --version)
 *  - Language pack for Indonesian: tesseract-ocr-ind  (or 'eng' works too)
 *
 * On Laragon / Windows:
 *   Download from https://github.com/UB-Mannheim/tesseract/wiki
 *   and make sure the install dir is in your system PATH.
 *
 * On Ubuntu/Debian:
 *   sudo apt install tesseract-ocr tesseract-ocr-ind
 */
class TesseractOCRService implements OCRServiceInterface
{
    /**
     * @throws RuntimeException when Tesseract binary is not reachable or fails.
     */
    public function extractText(UploadedFile $image): string
    {
        // Store to a temp path Tesseract can read
        $tmpPath = $image->getPathname(); // UploadedFile already lives in sys tmp

        // Determine Tesseract language – prefer Indonesian + English
        $lang = $this->detectAvailableLang();

        // Build command (output to stdout by passing "-" as output base)
        $cmd = sprintf(
            '%s %s stdout -l %s --psm 6 2>/dev/null',
            escapeshellcmd($this->binary()),
            escapeshellarg($tmpPath),
            escapeshellarg($lang),
        );

        // Windows: redirect stderr differently
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = sprintf(
                '%s %s stdout -l %s --psm 6',
                escapeshellcmd($this->binary()),
                escapeshellarg($tmpPath),
                escapeshellarg($lang),
            );
        }

        Log::debug('[TesseractOCR] Running command', ['cmd' => $cmd]);

        $output     = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('[TesseractOCR] Non-zero exit code', [
                'code'   => $returnCode,
                'output' => implode("\n", $output),
            ]);
            // Gracefully degrade: return empty string so caller can handle it
            return '';
        }

        $text = implode("\n", $output);
        Log::debug('[TesseractOCR] Raw output', ['length' => strlen($text)]);

        return $text;
    }

    // ──────────────────────────────────────────────────────────────────

    private function binary(): string
    {
        return config('ocr.tesseract_binary', 'tesseract');
    }

    private function detectAvailableLang(): string
    {
        // Try to detect if Indonesian tessdata is installed
        $cmd    = $this->binary() . ' --list-langs 2>&1';
        $output = shell_exec($cmd) ?? '';

        if (str_contains($output, 'ind')) {
            return 'ind+eng';
        }

        return 'eng';
    }
}
