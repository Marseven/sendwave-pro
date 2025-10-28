<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubAccount extends Model
{
    protected $fillable = [
        'parent_user_id',
        'name',
        'email',
        'password',
        'status',
        'last_connection',
        'credits_remaining',
        'credits_used_this_month',
        'delivery_rate',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'last_connection' => 'datetime',
        'password' => 'hashed',
    ];

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }
}
