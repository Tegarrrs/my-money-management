<?php

namespace App\Actions\OCR;

use App\Contracts\ReceiptScannerInterface;
use App\DTO\OCR\ParsedReceiptDTO;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Extract and parse transactions from a receipt image using an AI vision model.
 *
 * Flow:
 *   UploadedFile  →  ReceiptScannerInterface::scan()  →  ParsedReceiptDTO
 *
 * This class deliberately contains no framework/HTTP knowledge.
 */
class ImportFromImage
{
    public function __construct(
        private readonly ReceiptScannerInterface $scanner,
    ) {}

    /**
     * @param  UploadedFile  $image
     * @return ParsedReceiptDTO
     */
    public function handle(UploadedFile $image): ParsedReceiptDTO
    {
        Log::info('[ImportFromImage] Starting receipt scanning', [
            'original_name' => $image->getClientOriginalName(),
            'size_bytes'    => $image->getSize(),
        ]);

        try {
            $receipt = $this->scanner->scan($image);
            
            Log::info('[ImportFromImage] Parsing complete', [
                'item_count'     => count($receipt->items),
                'date_extracted' => $receipt->date,
                'total'          => $receipt->total,
            ]);

            return $receipt;
        } catch (\Exception $e) {
            Log::error('[ImportFromImage] Scanning failed: ' . $e->getMessage());
            
            // Return empty DTO on failure so frontend doesn't crash
            return new ParsedReceiptDTO(
                date:          null,
                items:         [],
                subtotal:      null,
                serviceCharge: null,
                total:         null,
            );
        }
    }
}
