<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
        'default_monthly_income',
    ];

    protected function casts(): array
    {
        return [
            'default_monthly_income' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'budget_users')
            ->withPivot('role', 'invited_at', 'accepted_at')
            ->withTimestamps();
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class)->orderBy('sort_order');
    }

    public function categoryGroups(): HasMany
    {
        return $this->hasMany(CategoryGroup::class)->orderBy('sort_order');
    }

    public function payees(): HasMany
    {
        return $this->hasMany(Payee::class)->orderBy('name');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }
}
