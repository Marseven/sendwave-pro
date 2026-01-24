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
     * Map day_of_week integer to Carbon day constant
     * 1 = Monday, 7 = Sunday (ISO-8601)
     */
    protected const DAY_MAP = [
        1 => Carbon::MONDAY,
        2 => Carbon::TUESDAY,
        3 => Carbon::WEDNESDAY,
        4 => Carbon::THURSDAY,
        5 => Carbon::FRIDAY,
        6 => Carbon::SATURDAY,
        7 => Carbon::SUNDAY,
    ];

    /**
     * Get the campaign that owns the schedule
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Parse the time field (stored as HH:MM:SS string) into hour and minute
     */
    protected function parseTime(): array
    {
        $timeParts = explode(':', $this->time ?? '09:00:00');
        return [
            'hour' => (int) ($timeParts[0] ?? 9),
            'minute' => (int) ($timeParts[1] ?? 0),
            'second' => (int) ($timeParts[2] ?? 0),
        ];
    }

    /**
     * Set time on a Carbon instance from the schedule's time field
     */
    protected function setScheduleTime(Carbon $date): Carbon
    {
        $time = $this->parseTime();
        return $date->setTime($time['hour'], $time['minute'], $time['second']);
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

        // Check if we're past the end date
        if ($this->end_date && $now->gt($this->end_date)) {
            return null;
        }

        // Check if we're before the start date
        if ($this->start_date && $now->lt($this->start_date)) {
            return $this->setScheduleTime(Carbon::parse($this->start_date));
        }

        $next = null;

        switch ($this->frequency) {
            case 'once':
                // For one-time schedules, use existing next_run_at or calculate from start_date
                if ($this->next_run_at && $this->next_run_at->gt($now)) {
                    return $this->next_run_at;
                }
                if ($this->start_date) {
                    $next = $this->setScheduleTime(Carbon::parse($this->start_date));
                    return $next->gt($now) ? $next : null;
                }
                return null;

            case 'daily':
                $next = $this->setScheduleTime(Carbon::today());
                if ($next->lte($now)) {
                    $next->addDay();
                }
                break;

            case 'weekly':
                if (!$this->day_of_week || !isset(self::DAY_MAP[$this->day_of_week])) {
                    return null;
                }
                $carbonDay = self::DAY_MAP[$this->day_of_week];
                $next = Carbon::now()->next($carbonDay);
                $next = $this->setScheduleTime($next);

                // If the calculated day is today and time hasn't passed, use today
                if (Carbon::now()->dayOfWeekIso === $this->day_of_week) {
                    $todayAtTime = $this->setScheduleTime(Carbon::today());
                    if ($todayAtTime->gt($now)) {
                        $next = $todayAtTime;
                    }
                }
                break;

            case 'monthly':
                if (!$this->day_of_month || $this->day_of_month < 1 || $this->day_of_month > 31) {
                    return null;
                }

                // Handle months with fewer days
                $daysInMonth = Carbon::now()->daysInMonth;
                $targetDay = min($this->day_of_month, $daysInMonth);

                $next = Carbon::now()->day($targetDay);
                $next = $this->setScheduleTime($next);

                if ($next->lte($now)) {
                    // Move to next month
                    $next->addMonth();
                    // Recalculate target day for new month
                    $daysInNextMonth = $next->daysInMonth;
                    $targetDay = min($this->day_of_month, $daysInNextMonth);
                    $next->day($targetDay);
                }
                break;

            default:
                return null;
        }

        // Final check: ensure next run is within the schedule window
        if ($next && $this->end_date && $next->gt($this->end_date)) {
            return null;
        }

        return $next;
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
