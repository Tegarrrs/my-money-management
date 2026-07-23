<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'category_id',
    'period',
    'amount',
    'warning_threshold',
])]
class Budget extends Model
{
    protected function casts(): array
    {
        return [
            'period' => 'date',
            'amount' => 'decimal:2',
            'warning_threshold' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->whereDate('period', $period);
    }
}
