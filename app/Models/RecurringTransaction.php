<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'account_id',
        'category_id',
        'payee_id',
        'amount',
        'type',
        'frequency',
        'next_date',
        'end_date',
        'end_after_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'recurring_id');
    }
}
