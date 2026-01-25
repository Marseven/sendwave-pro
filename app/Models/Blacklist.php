<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blacklist extends Model
{
    protected $table = 'blacklist';

    protected $fillable = [
        'user_id',
        'phone_number',
        'reason',
        'source',
        'added_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if phone number is blacklisted for user
     */
    public static function isBlacklisted(int $userId, string $phoneNumber): bool
    {
        return static::where('user_id', $userId)
            ->where('phone_number', $phoneNumber)
            ->exists();
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
