<?php

namespace Modules\Roadmap\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Roadmap\Actions\ToggleVote;
use Modules\Roadmap\Models\RoadmapItem;

class RoadmapVoteController
{
    public function store(Request $request, RoadmapItem $item, ToggleVote $toggleVote): RedirectResponse
    {
        abort_unless($item->isPublic(), 404);

        $toggleVote->handle($item, $request->user());

        return back();
    }
}
