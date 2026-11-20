<?php

namespace Modules\Roadmap\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Models\RoadmapItem;
use Modules\Roadmap\Notifications\RoadmapItemMergedNotification;

/**
 * Fold a duplicate into another item: its votes and comments move across, and it is
 * closed. A vote from someone who voted on both is dropped, as the unique index would
 * reject it. Its voters and author are told where it went, once the merge commits; its
 * address redirects there (RoadmapController::show()).
 */
class MergeItem
{
    public function handle(RoadmapItem $duplicate, RoadmapItem $target): void
    {
        if ($target->is($duplicate)) {
            throw new InvalidArgumentException('An item cannot be merged into itself.');
        }

        // Voters follow their votes and the mail names the target, so it must be one they can see.
        if (! $target->isPublic()) {
            throw new InvalidArgumentException('An item can only be merged into a public one.');
        }

        $subscribers = DB::transaction(function () use ($duplicate, $target) {
            // Locked and read again: a double click, or two admins at once, merges it once.
            if (RoadmapItem::lockForUpdate()->find($duplicate->getKey())?->merged_into_id !== null) {
                return null;
            }

            // Read before the votes move: afterwards the duplicate has no voters.
            $subscribers = $duplicate->subscribers();

            $duplicate->votes()->whereIn('user_id', $target->votes()->pluck('user_id'))->delete();
            $duplicate->votes()->update(['roadmap_item_id' => $target->getKey()]);
            // The relation is ordered, and an ordered UPDATE is not portable.
            $duplicate->comments()->reorder()->update(['roadmap_item_id' => $target->getKey()]);

            $duplicate->update([
                'merged_into_id' => $target->getKey(),
                'status' => RoadmapStatus::Closed,
            ]);

            return $subscribers;
        });

        if ($subscribers !== null) {
            Notification::send($subscribers, (new RoadmapItemMergedNotification($duplicate, $target))->afterCommit());
        }
    }
}
