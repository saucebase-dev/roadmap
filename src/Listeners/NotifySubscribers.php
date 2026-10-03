<?php

namespace Modules\Roadmap\Listeners;

use Modules\Roadmap\Events\StatusChanged;
use Modules\Roadmap\Notifications\RoadmapItemStatusChangedNotification;

class NotifySubscribers
{
    public function handle(StatusChanged $event): void
    {
        foreach ($event->item->subscribers() as $subscriber) {
            $subscriber->notify(new RoadmapItemStatusChangedNotification($event->item));
        }
    }
}
