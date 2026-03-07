<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'name',
        'icon',
        'default_amount',
        'projections',
        'sort_order',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'projections' => 'array',
            'is_hidden' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CategoryGroup::class, 'group_id');
    }

    public function monthlyBudgets(): HasMany
    {
        return $this->hasMany(MonthlyBudget::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function splitTransactions(): HasMany
    {
        return $this->hasMany(SplitTransaction::class);
    }

    public function getBudgetedForMonth(string $month): float
    {
        $monthlyBudget = $this->monthlyBudgets()->where('month', $month)->first();
        return $monthlyBudget ? (float) $monthlyBudget->budgeted_amount : 0;
    }

    public function getSpentForMonth(string $month): float
    {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $directActivity = (float) $this->transactions()
            ->where('type', '!=', 'transfer')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $splitActivity = (float) $this->splitTransactions()
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->where('type', '!=', 'transfer')
                    ->whereBetween('date', [$startDate, $endDate]);
            })
            ->sum('amount');

        // Expenses are negative, income/refunds are positive
        // Negate so net outflow shows as a positive "spent" value
        return (float) -($directActivity + $splitActivity);
    }

    public function getCumulativeBudgetedThrough(string $month): float
    {
        return (float) $this->monthlyBudgets()
            ->where('month', '<=', $month)
            ->sum('budgeted_amount');
    }

    public function getCumulativeSpentThrough(string $month): float
    {
        $endDate = date('Y-m-t', strtotime($month . '-01'));

        $directActivity = (float) $this->transactions()
            ->where('type', '!=', 'transfer')
            ->where('date', '<=', $endDate)
            ->sum('amount');

        $splitActivity = (float) $this->splitTransactions()
            ->whereHas('transaction', function ($query) use ($endDate) {
                $query->where('type', '!=', 'transfer')
                    ->where('date', '<=', $endDate);
            })
            ->sum('amount');

        return (float) -($directActivity + $splitActivity);
    }

    public function getAvailableForMonth(string $month): float
    {
        return $this->getCumulativeBudgetedThrough($month) - $this->getCumulativeSpentThrough($month);
    }
}
