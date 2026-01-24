<?php

namespace App\Models;

use App\Enums\WebhookEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Webhook extends Model
{
    /**
     * Available webhook events
     * @deprecated Use WebhookEvent enum instead
     */
    public const EVENTS = [
        'message.sent' => 'Message envoyé',
        'message.delivered' => 'Message délivré',
        'message.failed' => 'Message échoué',
        'campaign.started' => 'Campagne démarrée',
        'campaign.completed' => 'Campagne terminée',
        'campaign.failed' => 'Campagne échouée',
        'contact.created' => 'Contact créé',
        'contact.updated' => 'Contact mis à jour',
        'contact.deleted' => 'Contact supprimé',
        'sub_account.created' => 'Sous-compte créé',
        'sub_account.suspended' => 'Sous-compte suspendu',
        'blacklist.added' => 'Numéro ajouté à la liste noire',
    ];

    /**
     * Get available webhook events using enum
     */
    public static function getAvailableEvents(): array
    {
        return WebhookEvent::toArray();
    }

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'retry_limit',
        'timeout',
        'last_triggered_at',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'retry_limit' => 'integer',
        'timeout' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($webhook) {
            if (empty($webhook->secret)) {
                $webhook->secret = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    /**
     * Check if webhook is subscribed to an event
     */
    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    /**
     * Increment success counter
     */
    public function recordSuccess(): void
    {
        $this->increment('success_count');
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Increment failure counter
     */
    public function recordFailure(): void
    {
        $this->increment('failure_count');
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Generate signature for payload verification
     */
    public function generateSignature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), $this->secret);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get active webhooks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by event
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }
}
