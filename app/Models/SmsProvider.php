<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsProvider extends Model
{
    protected $fillable = [
        'code',
        'name',
        'api_key',
        'sender_id',
        'priority',
        'cost_per_sms',
        'is_active',
        'status',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'cost_per_sms' => 'decimal:4',
        'priority' => 'integer',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * Scope pour obtenir uniquement les providers actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour trier par priorité
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Obtenir la clé API déchiffrée (pour utilisation interne)
     */
    public function getDecryptedApiKeyAttribute()
    {
        return $this->api_key;
    }
}
