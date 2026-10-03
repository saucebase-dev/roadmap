<?php

namespace Modules\Roadmap\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Models\RoadmapItem;

/**
 * Fold a duplicate into another item: its votes and comments move across, and it is
 * closed. A vote from someone who voted on both is dropped, as the unique index would
 * reject it.
 */
class MergeItem
{
    public function handle(RoadmapItem $duplicate, RoadmapItem $target): void
    {
        if ($target->is($duplicate)) {
            throw new InvalidArgumentException('An item cannot be merged into itself.');
        }

        DB::transaction(function () use ($duplicate, $target): void {
            $duplicate->votes()->whereIn('user_id', $target->votes()->pluck('user_id'))->delete();
            $duplicate->votes()->update(['roadmap_item_id' => $target->getKey()]);
            // The relation is ordered, and an ordered UPDATE is not portable.
            $duplicate->comments()->reorder()->update(['roadmap_item_id' => $target->getKey()]);

            $duplicate->update([
                'merged_into_id' => $target->getKey(),
                'status' => RoadmapStatus::Closed,
            ]);
        });
    }
}
