<?php

namespace App\Events;

use App\GrowthSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GrowthSessionDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GrowthSession $growthSession;

    public function __construct(GrowthSession $growthSession)
    {
        $this->growthSession = $growthSession;
    }
}
