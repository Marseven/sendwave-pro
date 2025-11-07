<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\MessageVariableService;

class MessageTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'content',
        'category',
        'variables',
        'is_public',
        'usage_count',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_public' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Available categories for templates
     */
    public const CATEGORIES = [
        'marketing' => 'Marketing',
        'notifications' => 'Notifications',
        'alerts' => 'Alertes',
        'reminders' => 'Rappels',
        'confirmations' => 'Confirmations',
        'promotions' => 'Promotions',
        'other' => 'Autre',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Extract and store variables from content
     */
    public function extractVariables(): void
    {
        $service = new MessageVariableService();
        $variables = $service->findVariables($this->content);
        $this->variables = $variables;
        $this->save();
    }

    /**
     * Scope to filter public templates
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to order by most used
     */
    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }
}
