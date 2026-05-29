<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'wallet_id'        => ['required', Rule::exists('wallets', 'id')->where('user_id', $userId)],
            'category_id'      => ['nullable', Rule::exists('categories', 'id')->where('user_id', $userId)],
            'type'             => ['required', 'in:income,expense,transfer'],
            'amount'           => ['required', 'numeric', 'min:1'],
            'description'      => ['nullable', 'string', 'max:255'],
            'detail'           => ['nullable', 'string', 'max:1000'],
            'transaction_date' => ['required', 'date'],
            // Transfer-specific
            'to_wallet_id'     => ['nullable', Rule::exists('wallets', 'id')->where('user_id', $userId)],
            // Split-specific
            'is_split'         => ['nullable', 'boolean'],
            'splits'           => ['nullable', 'array'],
            'splits.*.category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', $userId)],
            'splits.*.amount'      => ['required_if:is_split,1', 'numeric', 'min:1'],
            'splits.*.description' => ['nullable', 'string', 'max:255'],
            // Receipt-specific
            'receipt_image'    => ['nullable', 'image', 'max:5120'], // max 5MB
        ];
    }

    public function attributes(): array
    {
        return [
            'wallet_id'        => 'dompet',
            'category_id'      => 'kategori',
            'amount'           => 'jumlah',
            'transaction_date' => 'tanggal transaksi',
        ];
    }
}
