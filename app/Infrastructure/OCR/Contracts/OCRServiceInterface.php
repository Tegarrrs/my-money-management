<?php

namespace App\Infrastructure\OCR\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Contract for any OCR engine integration.
 * Swap TesseractOCRService for any cloud-based engine by binding a different
 * implementation in the service container.
 */
interface OCRServiceInterface
{
    /**
     * Extract raw text from an uploaded image file.
     *
     * @param  UploadedFile  $image
     * @return string  The raw text extracted from the image.
     */
    public function extractText(UploadedFile $image): string;
}
