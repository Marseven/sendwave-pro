<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CampaignSchedule extends Model
{
    protected $fillable = [
        'campaign_id',
        'frequency',
        'day_of_week',
        'day_of_month',
        'time',
        'start_date',
        'end_date',
        'next_run_at',
        'last_run_at',
        'is_active',
        'run_count',
    ];

    protected $casts = [
        'time' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'is_active' => 'boolean',
        'run_count' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
    ];

    /**
     * Get the campaign that owns the schedule
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Calculate next run time based on frequency
     */
    public function calculateNextRun(): ?Carbon
    {
        if (!$this->is_active) {
            return null;
        }

        $now = Carbon::now();
        $time = Carbon::parse($this->time);

        // Check if we're within the schedule window
        if ($this->start_date && $now->lt($this->start_date)) {
            $next = Carbon::parse($this->start_date)->setTimeFrom($time);
            return $next;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return null;
        }

        switch ($this->frequency) {
            case 'once':
                return $this->next_run_at ?? Carbon::now()->setTimeFrom($time);

            case 'daily':
                $next = Carbon::now()->setTimeFrom($time);
                if ($next->lte($now)) {
                    $next->addDay();
                }
                return $next;

            case 'weekly':
                if (!$this->day_of_week) {
                    return null;
                }
                $next = Carbon::now()->next($this->day_of_week)->setTimeFrom($time);
                if ($next->lte($now)) {
                    $next->addWeek();
                }
                return $next;

            case 'monthly':
                if (!$this->day_of_month) {
                    return null;
                }
                $next = Carbon::now()->day($this->day_of_month)->setTimeFrom($time);
                if ($next->lte($now)) {
                    $next->addMonth();
                }
                return $next;

            default:
                return null;
        }
    }

    /**
     * Mark schedule as executed
     */
    public function markAsExecuted(): void
    {
        $this->last_run_at = Carbon::now();
        $this->run_count++;

        if ($this->frequency === 'once') {
            $this->is_active = false;
            $this->next_run_at = null;
        } else {
            $this->next_run_at = $this->calculateNextRun();
        }

        $this->save();
    }

    /**
     * Scope to get active schedules ready to run
     */
    public function scopeReadyToRun($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', Carbon::now());
    }
}
