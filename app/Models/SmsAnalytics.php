<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsAnalytics extends Model
{
    protected $fillable = [
        'user_id',
        'sub_account_id',
        'campaign_id',
        'message_id',
        'api_key_id',
        'country_code',
        'operator',
        'gateway',
        'message_type',
        'unit_cost',
        'sms_parts',
        'total_cost',
        'status',
        'period_key',
        'is_closed',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'sms_parts' => 'integer',
        'is_closed' => 'boolean',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subAccount(): BelongsTo
    {
        return $this->belongsTo(SubAccount::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    // Scopes
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySubAccount($query, int $subAccountId)
    {
        return $query->where('sub_account_id', $subAccountId);
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period_key', $period);
    }

    public function scopeNotClosed($query)
    {
        return $query->where('is_closed', false);
    }

    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    public function scopeByOperator($query, string $operator)
    {
        return $query->where('operator', $operator);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('message_type', $type);
    }
}
