<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OCRUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:5120'], // max 5 MB
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Silakan pilih gambar struk.',
            'image.image'    => 'File harus berupa gambar (jpg, png, dll.).',
            'image.max'      => 'Ukuran gambar maksimal 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'image' => 'gambar struk',
        ];
    }
}
