<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'source', 'raw_input', 'parsed_data', 'suggested_category_id', 'event_id', 'status'])]
class TransactionDraft extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
        ];
    }

    /**
     * Get the user that owns the transaction draft.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the suggested category.
     */
    public function suggestedCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'suggested_category_id');
    }

    /**
     * Get the event associated with the draft.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get all transactions created from this draft.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'draft_id');
    }
}
