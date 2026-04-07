<?php

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    public function execute(Category $category): void
    {
        if ($category->transactions()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Kategori tidak dapat dihapus karena masih digunakan oleh transaksi.',
            ]);
        }

        $category->delete();
    }
}
