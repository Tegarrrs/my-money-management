<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'recurring_transaction_id',
    'transaction_id',
    'scheduled_for',
    'status',
    'error_message',
])]
class RecurringTransactionRun extends Model
{
    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime'];
    }

    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
