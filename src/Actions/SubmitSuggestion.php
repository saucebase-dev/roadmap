<?php

namespace Modules\Roadmap\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Enums\RoadmapType;
use Modules\Roadmap\Models\RoadmapItem;

/**
 * File a suggestion for review. Submitting is itself a vote: nobody suggests an idea they
 * do not want, so the two writes stand or fall together.
 */
class SubmitSuggestion
{
    public function handle(User $user, string $title, ?string $description, RoadmapType $type): RoadmapItem
    {
        return DB::transaction(function () use ($user, $title, $description, $type): RoadmapItem {
            $item = RoadmapItem::create([
                'title' => $title,
                'description' => $description,
                'status' => RoadmapStatus::UnderReview,
                'type' => $type,
                'user_id' => $user->id,
            ]);

            $item->votes()->create(['user_id' => $user->id]);

            return $item;
        });
    }
}
