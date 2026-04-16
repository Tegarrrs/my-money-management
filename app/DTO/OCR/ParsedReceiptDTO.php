<?php

namespace App\DTO\OCR;

/**
 * Represents a fully-parsed receipt with metadata and line items.
 */
final class ParsedReceiptDTO
{
    /**
     * @param  string|null     $date           ISO 8601 date string (Y-m-d) extracted from the receipt, or null if not found.
     * @param  ParsedItemDTO[] $items          Individual line items.
     * @param  int|null        $subtotal       Subtotal amount in smallest currency unit (e.g. IDR cents = rupiah).
     * @param  int|null        $serviceCharge  Service / Biaya Pelayanan amount.
     * @param  int|null        $total          Grand total amount.
     */
    public function __construct(
        public readonly ?string $date,
        public readonly array   $items,
        public readonly ?int    $subtotal,
        public readonly ?int    $serviceCharge,
        public readonly ?int    $total,
    ) {}

    public function toArray(): array
    {
        return [
            'date'           => $this->date,
            'items'          => array_map(fn (ParsedItemDTO $i) => $i->toArray(), $this->items),
            'subtotal'       => $this->subtotal,
            'service_charge' => $this->serviceCharge,
            'total'          => $this->total,
        ];
    }
}
