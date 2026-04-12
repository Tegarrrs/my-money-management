<?php

namespace App\Actions\OCR;

use App\DTO\OCR\ParsedItemDTO;
use App\Infrastructure\OCR\Contracts\OCRServiceInterface;
use App\Services\OCRParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Extract and parse transactions from a receipt image.
 *
 * Flow:
 *   UploadedFile  →  OCRServiceInterface::extractText()  →  OCRParser::parse()  →  ParsedItemDTO[]
 *
 * This class deliberately contains no framework/HTTP knowledge.
 */
class ImportFromImage
{
    public function __construct(
        private readonly OCRServiceInterface $ocr,
        private readonly OCRParser           $parser,
    ) {}

    /**
     * @param  UploadedFile  $image
     * @return ParsedItemDTO[]
     */
    public function handle(UploadedFile $image): array
    {
        Log::info('[ImportFromImage] Starting OCR extraction', [
            'original_name' => $image->getClientOriginalName(),
            'size_bytes'    => $image->getSize(),
        ]);

        $rawText = $this->ocr->extractText($image);

        Log::info('[ImportFromImage] OCR done', [
            'text_length' => strlen($rawText),
        ]);

        if (trim($rawText) === '') {
            Log::warning('[ImportFromImage] OCR returned empty text.');
            return [];
        }

        $items = $this->parser->parse($rawText);

        Log::info('[ImportFromImage] Parsing complete', ['item_count' => count($items)]);

        return $items;
    }
}
