<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_id',
        'address',
        'city',
        'country',
        'logo',
        'sms_credits',
        'monthly_budget',
        'budget_used',
        'budget_alert_threshold',
        'block_on_budget_exceeded',
        'sms_sent_total',
        'sms_sent_month',
        'campaigns_count',
        'contacts_count',
        'status',
        'settings',
        'notes',
        'last_activity_at',
    ];

    protected $casts = [
        'sms_credits' => 'decimal:2',
        'monthly_budget' => 'decimal:2',
        'budget_used' => 'decimal:2',
        'budget_alert_threshold' => 'decimal:2',
        'block_on_budget_exceeded' => 'boolean',
        'sms_sent_total' => 'integer',
        'sms_sent_month' => 'integer',
        'campaigns_count' => 'integer',
        'contacts_count' => 'integer',
        'settings' => 'array',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Get all users belonging to this account
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get admin users of this account
     */
    public function admins(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'admin');
    }

    /**
     * Get agent users of this account
     */
    public function agents(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'agent');
    }

    /**
     * Get contacts belonging to this account (through users)
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get campaigns belonging to this account
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if account is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if account can send SMS
     */
    public function canSendSms(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->sms_credits <= 0) {
            return false;
        }

        if ($this->block_on_budget_exceeded && $this->monthly_budget !== null) {
            if ($this->budget_used >= $this->monthly_budget) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add SMS credits to the account
     */
    public function addCredits(float $amount): void
    {
        $this->increment('sms_credits', $amount);
    }

    /**
     * Use SMS credits
     */
    public function useCredits(float $amount): bool
    {
        if ($this->sms_credits < $amount) {
            return false;
        }

        $this->decrement('sms_credits', $amount);
        $this->increment('budget_used', $amount);
        $this->increment('sms_sent_total');
        $this->increment('sms_sent_month');
        $this->update(['last_activity_at' => now()]);

        return true;
    }

    /**
     * Get remaining credits
     */
    public function getRemainingCreditsAttribute(): float
    {
        return max(0, $this->sms_credits);
    }

    /**
     * Get budget usage percentage
     */
    public function getBudgetUsagePercentAttribute(): ?float
    {
        if ($this->monthly_budget === null || $this->monthly_budget <= 0) {
            return null;
        }

        return min(100, ($this->budget_used / $this->monthly_budget) * 100);
    }

    /**
     * Check if budget alert threshold is reached
     */
    public function isBudgetAlertReached(): bool
    {
        $usage = $this->budget_usage_percent;
        return $usage !== null && $usage >= $this->budget_alert_threshold;
    }

    /**
     * Reset monthly usage (to be called at the start of each month)
     */
    public function resetMonthlyUsage(): void
    {
        $this->update([
            'budget_used' => 0,
            'sms_sent_month' => 0,
        ]);
    }

    /**
     * Update statistics
     */
    public function updateStats(): void
    {
        $this->update([
            'contacts_count' => $this->users()->withCount('contacts')->get()->sum('contacts_count'),
            'campaigns_count' => Campaign::whereIn('user_id', $this->users()->pluck('id'))->count(),
        ]);
    }

    /**
     * Suspend the account
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Activate the account
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }
}
