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

        return (float) abs($this->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount'));
    }

    public function getAvailableForMonth(string $month): float
    {
        return $this->getBudgetedForMonth($month) - $this->getSpentForMonth($month);
    }
}
