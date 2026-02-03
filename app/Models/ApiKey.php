<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'sub_account_id',
        'name',
        'key',
        'provider',
        'permissions',
        'rate_limit',
        'allowed_ips',
        'last_used',
        'is_active',
    ];

    protected $casts = [
        'last_used' => 'datetime',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'rate_limit' => 'integer',
        'allowed_ips' => 'array',
    ];

    /**
     * Available permissions
     */
    public const PERMISSIONS = [
        'send_sms' => 'Envoyer des SMS',
        'view_history' => 'Voir l\'historique',
        'manage_contacts' => 'Gérer les contacts',
        'view_balance' => 'Voir le solde',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subAccount(): BelongsTo
    {
        return $this->belongsTo(SubAccount::class);
    }

    /**
     * Scope for filtering by sub account
     */
    public function scopeForSubAccount($query, int $subAccountId)
    {
        return $query->where('sub_account_id', $subAccountId);
    }

    /**
     * Check if the given IP address is allowed for this key.
     * Returns true if no IP restrictions are set.
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Check if the key has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Update last used timestamp
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used' => now()]);
    }
}
