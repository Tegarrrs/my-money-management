<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:100'],
            'type'                   => ['required', 'string', 'in:cash,bank,e-wallet,investment,other'],
            'icon'                   => ['nullable', 'string', 'max:50'],
            'color'                  => ['nullable', 'string', 'max:20'],
            'initial_balance'        => ['nullable', 'numeric'],
            'allow_negative_balance' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama dompet',
            'type' => 'jenis dompet',
        ];
    }
}
