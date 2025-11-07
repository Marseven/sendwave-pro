<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignVariant extends Model
{
    protected $fillable = [
        'campaign_id',
        'variant_name',
        'message',
        'percentage',
        'sent_count',
        'delivered_count',
        'failed_count',
        'success_rate',
    ];

    protected $casts = [
        'percentage' => 'integer',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'failed_count' => 'integer',
        'success_rate' => 'decimal:2',
    ];

    /**
     * Get the campaign that owns the variant
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Calculate and update success rate
     */
    public function updateSuccessRate(): void
    {
        if ($this->sent_count > 0) {
            $this->success_rate = ($this->delivered_count / $this->sent_count) * 100;
            $this->save();
        }
    }

    /**
     * Increment sent count
     */
    public function incrementSent(): void
    {
        $this->increment('sent_count');
    }

    /**
     * Increment delivered count
     */
    public function incrementDelivered(): void
    {
        $this->increment('delivered_count');
        $this->updateSuccessRate();
    }

    /**
     * Increment failed count
     */
    public function incrementFailed(): void
    {
        $this->increment('failed_count');
        $this->updateSuccessRate();
    }
}
