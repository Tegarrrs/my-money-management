<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'wallet_id',
    'category_id',
    'amount',
    'description',
    'detail',
    'transaction_date',
    'event_id',
    'transfer_group_id',
    'split_group_id',
    'is_balance_adjustment',
    'draft_id',
    'receipt_id',
    'recurring_transaction_id',
    'recurring_run_at',
])]
class Transaction extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'is_balance_adjustment' => 'boolean',
            'recurring_run_at' => 'datetime',
        ];
    }

    /* ---------- Accessors ---------- */

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format(abs($this->amount), 0, ',', '.');
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->transaction_date
            ? $this->transaction_date->translatedFormat('d M Y')
            : '-';
    }

    public function getTypeAttribute(): string
    {
        return $this->amount >= 0 ? 'income' : 'expense';
    }

    /* ---------- Scopes ---------- */

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeIncome($query)
    {
        return $query->where('amount', '>', 0)
            ->whereNull('transfer_group_id')
            ->where('is_balance_adjustment', false);
    }

    public function scopeExpense($query)
    {
        return $query->where('amount', '<', 0)
            ->whereNull('transfer_group_id')
            ->where('is_balance_adjustment', false);
    }

    public function scopeTransfer($query)
    {
        return $query->whereNotNull('transfer_group_id');
    }

    public function scopeRecent($query, int $limit = 5)
    {
        return $query->orderByDesc('transaction_date')->orderByDesc('id')->limit($limit);
    }

    /* ---------- Relations ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(TransactionDraft::class, 'draft_id');
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class);
    }
}
