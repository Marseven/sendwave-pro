<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsConfig extends Model
{
    protected $fillable = [
        'provider',
        'api_url',
        'port',
        'username',
        'password',
        'origin_addr',
        'cost_per_sms',
        'is_active',
        'additional_config',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_active' => 'boolean',
        'cost_per_sms' => 'integer',
        'additional_config' => 'array',
    ];

    protected $hidden = [
        'password', // Masquer le mot de passe dans les réponses JSON
    ];
}
