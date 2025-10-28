<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'group',
        'status',
        'last_connection',
    ];

    protected $casts = [
        'last_connection' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
