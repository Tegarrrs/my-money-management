<?php

namespace App\Actions\Category;

use App\Models\Category;
use App\Models\User;

class CreateCategory
{
    public function execute(User $user, array $data): Category
    {
        return $user->categories()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'icon' => $data['icon'] ?? null,
        ]);
    }
}
