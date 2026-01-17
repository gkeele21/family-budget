<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'name',
        'type',
        'starting_balance',
        'sort_order',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'starting_balance' => 'decimal:2',
            'is_closed' => 'boolean',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->starting_balance + $this->transactions()->sum('amount');
    }

    public function getClearedBalanceAttribute(): float
    {
        return (float) $this->starting_balance + $this->transactions()->where('cleared', true)->sum('amount');
    }
}
