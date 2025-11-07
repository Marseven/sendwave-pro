<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAnalytic extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'sms_sent',
        'sms_delivered',
        'sms_failed',
        'airtel_count',
        'moov_count',
        'total_cost',
        'campaigns_sent',
        'contacts_added',
    ];

    protected $casts = [
        'date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Helpers
    public function getSuccessRateAttribute()
    {
        if ($this->sms_sent == 0) return 0;
        return round(($this->sms_delivered / $this->sms_sent) * 100, 2);
    }

    public function getFailureRateAttribute()
    {
        if ($this->sms_sent == 0) return 0;
        return round(($this->sms_failed / $this->sms_sent) * 100, 2);
    }

    public function getAverageCostPerSmsAttribute()
    {
        if ($this->sms_sent == 0) return 0;
        return round($this->total_cost / $this->sms_sent, 2);
    }
}
