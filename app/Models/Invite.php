<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invite extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'email',
        'invited_by',
        'token',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invite) {
            if (empty($invite->token)) {
                $invite->token = Str::random(64);
            }
        });
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return is_null($this->accepted_at);
    }
}
