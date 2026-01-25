<?php

namespace App\Models;

use App\Enums\SubAccountRole;
use App\Enums\SubAccountPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class SubAccount extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'parent_user_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'sms_credit_limit',
        'sms_used',
        'permissions',
        'last_connection',
        'monthly_budget',
        'budget_alert_threshold',
        'block_on_budget_exceeded',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'last_connection' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array',
        'sms_credit_limit' => 'integer',
        'sms_used' => 'integer',
        'monthly_budget' => 'decimal:2',
        'budget_alert_threshold' => 'decimal:2',
        'block_on_budget_exceeded' => 'boolean',
    ];

    /**
     * Relations
     */
    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function smsAnalytics(): HasMany
    {
        return $this->hasMany(SmsAnalytics::class);
    }

    /**
     * Permissions Management
     */
    public function hasPermission(string $permission): bool
    {
        // Admin has all permissions
        if ($this->role === SubAccountRole::ADMIN->value) {
            return true;
        }

        // Check in permissions array
        return in_array($permission, $this->permissions ?? []);
    }

    public function grantPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }

    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_filter($permissions, fn($p) => $p !== $permission);
        $this->update(['permissions' => array_values($permissions)]);
    }

    /**
     * SMS Credits Management
     */
    public function canSendSms(): bool
    {
        // Check if account is active
        if ($this->status !== 'active') {
            return false;
        }

        // Check permission
        if (!$this->hasPermission('send_sms')) {
            return false;
        }

        // Check credit limit (null = unlimited)
        if ($this->sms_credit_limit === null) {
            return true;
        }

        return $this->sms_used < $this->sms_credit_limit;
    }

    public function incrementSmsUsed(int $count = 1): void
    {
        $this->increment('sms_used', $count);
    }

    public function resetSmsUsed(): void
    {
        $this->update(['sms_used' => 0]);
    }

    public function addCredits(int $amount): void
    {
        if ($this->sms_credit_limit === null) {
            $this->update(['sms_credit_limit' => $amount]);
        } else {
            $this->increment('sms_credit_limit', $amount);
        }
    }

    public function getRemainingCreditsAttribute(): ?int
    {
        if ($this->sms_credit_limit === null) {
            return null; // Unlimited
        }

        return max(0, $this->sms_credit_limit - $this->sms_used);
    }

    /**
     * Role-based permissions
     */
    public function getDefaultPermissions(): array
    {
        $role = SubAccountRole::tryFrom($this->role);
        return $role?->defaultPermissions() ?? [];
    }

    /**
     * Get the role as enum
     */
    public function getRoleEnum(): ?SubAccountRole
    {
        return SubAccountRole::tryFrom($this->role);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByParent($query, int $parentUserId)
    {
        return $query->where('parent_user_id', $parentUserId);
    }
}
