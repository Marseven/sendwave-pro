<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'status',
        'messages_sent',
        'recipients_count',
        'sms_count',
        'cost',
        'delivery_rate',
        'ctr',
        'sms_provider',
        'message',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'messages_sent' => 'integer',
        'recipients_count' => 'integer',
        'sms_count' => 'integer',
        'cost' => 'integer',
        'delivery_rate' => 'decimal:2',
        'ctr' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function schedule(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CampaignSchedule::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(CampaignVariant::class);
    }

    /**
     * Check if campaign has A/B testing enabled
     */
    public function hasABTesting(): bool
    {
        return $this->variants()->count() > 1;
    }

    /**
     * Check if campaign is scheduled/recurring
     */
    public function isScheduled(): bool
    {
        return $this->schedule()->exists();
    }
}
