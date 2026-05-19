<?php

namespace App\Infrastructure\OCR;

use App\Infrastructure\OCR\Contracts\OCRServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Tesseract-based OCR engine implementation with image preprocessing.
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

        // Try to preprocess the image for better OCR accuracy
        $processedPath = $this->preprocessImage($tmpPath);

        // Determine Tesseract language – prefer Indonesian + English
        $lang = $this->detectAvailableLang();

        // Use the processed path if available, otherwise fall back to original
        $ocrInputPath = $processedPath ?? $tmpPath;

        // Build command (output to stdout by passing "-" as output base)
        // PSM 6 = Assume a single uniform block of text
        // PSM 6 works best for standard Indonesian receipts with spacing
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = sprintf(
                '%s %s stdout -l %s --psm 6 --oem 3 2>NUL',
                escapeshellcmd($this->binary()),
                escapeshellarg($ocrInputPath),
                escapeshellarg($lang),
            );
        } else {
            $cmd = sprintf(
                '%s %s stdout -l %s --psm 6 --oem 3 2>/dev/null',
                escapeshellcmd($this->binary()),
                escapeshellarg($ocrInputPath),
                escapeshellarg($lang),
            );
        }

        Log::debug('[TesseractOCR] Running command', ['cmd' => $cmd]);

        $output     = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $text = implode("\n", $output);

        if ($returnCode !== 0 && strlen(trim($text)) === 0) {
            Log::error('[TesseractOCR] Non-zero exit code', [
                'code'   => $returnCode,
                'output' => $text,
            ]);
            // Gracefully degrade: return empty string so caller can handle it
            return '';
        }

        // Clean up preprocessed temp file
        if ($processedPath && $processedPath !== $tmpPath && file_exists($processedPath)) {
            @unlink($processedPath);
        }

        Log::debug('[TesseractOCR] Raw output', ['length' => strlen($text), 'text' => $text]);

        return $text;
    }

    // ──────────────────────────────────────────────────────────────────

    /**
     * Preprocess image for better OCR accuracy.
     * - Convert to grayscale
     * - Increase contrast
     * - Apply sharpening
     * - Scale up if too small
     *
     * Requires PHP GD extension (usually enabled by default in Laragon).
     *
     * @return string|null Path to processed image, or null if preprocessing failed
     */
    private function preprocessImage(string $inputPath): ?string
    {
        if (!extension_loaded('gd')) {
            Log::info('[TesseractOCR] GD extension not loaded, skipping preprocessing');
            return null;
        }

        try {
            $imageInfo = @getimagesize($inputPath);
            if (!$imageInfo) {
                return null;
            }

            $mime = $imageInfo['mime'] ?? '';
            $srcImage = match ($mime) {
                'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($inputPath),
                'image/png'               => @imagecreatefrompng($inputPath),
                'image/webp'              => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($inputPath) : false,
                'image/bmp'               => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($inputPath) : false,
                default                   => false,
            };

            if (!$srcImage) {
                return null;
            }

            $width  = imagesx($srcImage);
            $height = imagesy($srcImage);

            // Scale up small images (receipts from phone cameras are usually fine,
            // but if someone sends a tiny crop, scaling helps OCR)
            $scaleFactor = 1;
            if ($width < 800) {
                $scaleFactor = 2;
            }

            $newWidth  = (int) ($width * $scaleFactor);
            $newHeight = (int) ($height * $scaleFactor);

            // Create new image with higher resolution
            $processed = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($processed, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($srcImage);

            // Convert to grayscale for better OCR
            imagefilter($processed, IMG_FILTER_GRAYSCALE);

            // Note: Aggressive contrast/brightness and sharpening were removed
            // because they often destroy thin characters like '1', making them
            // look like '}' or '|', which degrades Tesseract's built-in Otsu binarization.

            // Save to temp file
            $processedPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ocr_processed_' . uniqid() . '.png';
            imagepng($processed, $processedPath);
            imagedestroy($processed);

            Log::debug('[TesseractOCR] Image preprocessed', [
                'original_size' => "{$width}x{$height}",
                'processed_size' => "{$newWidth}x{$newHeight}",
                'scale_factor' => $scaleFactor,
                'output_path' => $processedPath,
            ]);

            return $processedPath;

        } catch (\Throwable $e) {
            Log::warning('[TesseractOCR] Image preprocessing failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

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
