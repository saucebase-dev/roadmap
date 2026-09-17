<?php

namespace Modules\Roadmap\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Models\RoadmapItem;

class RoadmapVoteController
{
    public function store(RoadmapItem $item): RedirectResponse
    {
        abort_unless(in_array($item->status, RoadmapStatus::publicStatuses()), 404);

        // Two clicks can arrive at once; without the lock both see no vote and
        // both insert, and the unique index turns the second into a 500.
        DB::transaction(function () use ($item): void {
            $existing = $item->votes()->where('user_id', auth()->id())->lockForUpdate()->first();

            if ($existing) {
                $existing->delete();

                return;
            }

            $item->votes()->create(['user_id' => auth()->id()]);
        });

        return back();
    }
}
