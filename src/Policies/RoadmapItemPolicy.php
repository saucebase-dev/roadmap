<?php

namespace Modules\Roadmap\Policies;

use App\Models\User;
use Modules\Roadmap\Models\RoadmapItem;

/**
 * Who may read an item. An application that wants another rule replaces this policy.
 */
class RoadmapItemPolicy
{
    /**
     * A public item is for everyone, guests included. One that is not is for its author,
     * who finds it under "My feedback", and for whoever moderates the roadmap.
     */
    public function view(?User $user, RoadmapItem $item): bool
    {
        return $item->isPublic()
            || ($user !== null && ($item->user_id === $user->getKey() || $user->can('manage roadmap')));
    }
}
