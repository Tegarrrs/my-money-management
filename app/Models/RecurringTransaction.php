<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'wallet_id',
    'category_id',
    'amount',
    'description',
    'detail',
    'frequency',
    'next_run_at',
    'last_run_at',
    'ends_at',
    'is_active',
])]
class RecurringTransaction extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the recurring transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet for this recurring transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the category for this recurring transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(RecurringTransactionRun::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
