<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'account_id',
        'category_id',
        'payee_id',
        'amount',
        'type',
        'date',
        'cleared',
        'memo',
        'transfer_pair_id',
        'recurring_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'cleared' => 'boolean',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function payee(): BelongsTo
    {
        return $this->belongsTo(Payee::class);
    }

    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class, 'recurring_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transferPair(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transfer_pair_id');
    }

    public function splits(): HasMany
    {
        return $this->hasMany(SplitTransaction::class);
    }

    public function isSplit(): bool
    {
        return $this->splits()->exists();
    }
}
