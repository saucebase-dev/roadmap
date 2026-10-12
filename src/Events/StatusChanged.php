<?php

namespace Modules\Roadmap\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Roadmap\Models\RoadmapItem;

/** Dispatched once the change commits, so a rolled-back merge mails nobody. */
class StatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public RoadmapItem $item,
    ) {}
}
