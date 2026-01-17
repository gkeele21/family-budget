<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'current_budget_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedBudgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'owner_id');
    }

    public function budgets(): BelongsToMany
    {
        return $this->belongsToMany(Budget::class, 'budget_users')
            ->withPivot('role', 'invited_at', 'accepted_at')
            ->withTimestamps();
    }

    public function currentBudget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'current_budget_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }
}
