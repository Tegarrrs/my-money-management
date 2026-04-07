<?php

namespace App\Actions\Category;

use App\Models\Category;

class UpdateCategory
{
    public function execute(Category $category, array $data): Category
    {
        $category->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'icon' => $data['icon'] ?? $category->icon,
        ]);

        return $category->fresh();
    }
}
