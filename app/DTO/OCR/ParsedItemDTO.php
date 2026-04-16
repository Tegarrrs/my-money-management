<?php

namespace App\DTO\OCR;

/**
 * Represents a single transaction item parsed from an OCR-extracted receipt text.
 */
final class ParsedItemDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int    $amount,
        public readonly int    $qty = 1,
    ) {}

    public function toArray(): array
    {
        return [
            'name'   => $this->name,
            'amount' => $this->amount,
            'qty'    => $this->qty,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name:   trim($data['name'] ?? ''),
            amount: (int) ($data['amount'] ?? 0),
            qty:    (int) ($data['qty']    ?? 1),
        );
    }
}
