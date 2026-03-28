<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'image_path', 'raw_text', 'parsed_json', 'status'])]
class Receipt extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parsed_json' => 'array',
        ];
    }

    /**
     * Get the user that owns the receipt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions linked to this receipt.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
