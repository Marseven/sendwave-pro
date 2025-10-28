<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'user_id',
        'campaign_id',
        'contact_id',
        'recipient_name',
        'recipient_phone',
        'content',
        'type',
        'status',
        'provider',
        'cost',
        'error_message',
        'sent_at',
        'provider_response',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'cost' => 'integer',
        'provider_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
