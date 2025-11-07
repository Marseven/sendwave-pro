<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'status_code',
        'response_body',
        'error_message',
        'attempt',
        'success',
        'triggered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'status_code' => 'integer',
        'attempt' => 'integer',
        'success' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Scope to filter successful deliveries
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope to filter failed deliveries
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope to filter by event
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }
}
