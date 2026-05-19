<?php

namespace App\Contracts;

use App\DTO\OCR\ParsedReceiptDTO;
use Illuminate\Http\UploadedFile;

interface ReceiptScannerInterface
{
    /**
     * Scan a receipt image and return parsed transaction data.
     */
    public function scan(UploadedFile $image): ParsedReceiptDTO;
}
