<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
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
            'to_wallet_id'     => ['nullable', 'required_if:type,transfer', Rule::exists('wallets', 'id')->where('user_id', $userId)],
            'admin_fee'        => ['nullable', 'numeric', 'min:0'],
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
