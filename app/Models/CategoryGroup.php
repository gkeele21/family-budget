<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'name',
        'sort_order',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'group_id')->orderBy('sort_order');
    }
}
