<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'month',
        'budgeted_amount',
    ];

    protected function casts(): array
    {
        return [
            'budgeted_amount' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
