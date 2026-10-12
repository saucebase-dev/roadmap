<?php

namespace Modules\Roadmap\Actions;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Roadmap\Models\RoadmapItem;

/** Upvote an item, or take the vote back. True when the user has voted afterwards. */
class ToggleVote
{
    public function handle(RoadmapItem $item, User $user): bool
    {
        if ($item->votes()->where('user_id', $user->id)->delete() > 0) {
            return false;
        }

        try {
            $item->votes()->create(['user_id' => $user->id]);
        } catch (UniqueConstraintViolationException) {
            // ponytail: a double click inserted the same vote first; the user has voted either way.
        }

        return true;
    }
}
