<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodClosure extends Model
{
    protected $fillable = [
        'user_id',
        'period_key',
        'total_sms',
        'total_cost',
        'breakdown_by_subaccount',
        'breakdown_by_operator',
        'breakdown_by_type',
        'status',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'total_sms' => 'integer',
        'total_cost' => 'decimal:2',
        'breakdown_by_subaccount' => 'array',
        'breakdown_by_operator' => 'array',
        'breakdown_by_type' => 'array',
        'closed_at' => 'datetime',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period_key', $period);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Helpers
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getFormattedPeriodAttribute(): string
    {
        $parts = explode('-', $this->period_key);
        $months = [
            '01' => 'Janvier', '02' => 'Février', '03' => 'Mars',
            '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
            '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre',
            '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre',
        ];

        return ($months[$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
    }
}
