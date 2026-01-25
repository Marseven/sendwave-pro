<?php

namespace App\Events;

use App\Models\SubAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BudgetExceededEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SubAccount $subAccount,
        public float $spent,
        public float $budget
    ) {}

    public function getOverageAmount(): float
    {
        return $this->spent - $this->budget;
    }

    public function getPercentOver(): float
    {
        return round((($this->spent - $this->budget) / $this->budget) * 100, 2);
    }
}
