<?php

namespace Modules\Roadmap\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Roadmap\Events\StatusChanged;
use Modules\Roadmap\Notifications\RoadmapItemStatusChangedNotification;

class NotifySubscribers implements ShouldQueue
{
    public function handle(StatusChanged $event): void
    {
        foreach ($event->item->subscribers() as $subscriber) {
            $subscriber->notify(new RoadmapItemStatusChangedNotification($event->item));
        }
    }
}
