<?php

namespace App\Events;

use App\Models\SubAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BudgetAlertEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SubAccount $subAccount,
        public float $percentUsed
    ) {}
}
