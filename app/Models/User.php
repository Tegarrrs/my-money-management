<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function transactionDrafts(): HasMany
    {
        return $this->hasMany(TransactionDraft::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function categorySuggestions(): HasMany
    {
        return $this->hasMany(CategorySuggestion::class);
    }

    protected static function booted()
    {
        static::created(function (User $user) {
            $categories = [
                ['name' => 'Makanan & Minuman', 'type' => 'expense', 'icon' => 'bi-cup-hot', 'color' => '#f97316'],
                ['name' => 'Transportasi', 'type' => 'expense', 'icon' => 'bi-car-front', 'color' => '#3b82f6'],
                ['name' => 'Belanja', 'type' => 'expense', 'icon' => 'bi-bag', 'color' => '#ec4899'],
                ['name' => 'Gaji', 'type' => 'income', 'icon' => 'bi-cash-stack', 'color' => '#22c55e'],
                ['name' => 'Lain-lain', 'type' => 'expense', 'icon' => 'bi-three-dots', 'color' => '#6b7280'],
            ];
            foreach ($categories as $cat) {
                $user->categories()->create($cat);
            }
        });
    }
}
