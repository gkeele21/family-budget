<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payee extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'name',
        'default_category_id',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
