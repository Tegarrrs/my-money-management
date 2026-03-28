<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'keyword', 'category_id', 'confidence'])]
class CategorySuggestion extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:4',
        ];
    }

    /**
     * Get the user that owns the category suggestion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category associated with the suggestion.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
