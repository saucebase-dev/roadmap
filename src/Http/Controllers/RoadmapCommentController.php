<?php

namespace Modules\Roadmap\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use InertiaUI\Modal\Modal;
use Modules\Roadmap\Models\RoadmapItem;
use Modules\Roadmap\Settings\RoadmapSettings;

class RoadmapCommentController
{
    public function store(Request $request, RoadmapItem $item, RoadmapSettings $settings): RedirectResponse|Response|Modal
    {
        abort_unless($settings->comments_enabled, 404);
        abort_unless($item->isPublic(), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $item->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        // A panel asks for the item again rather than following a redirect: a
        // redirect would navigate the page behind it and tear the panel down.
        if ($request->hasHeader(Modal::HEADER_MODAL)) {
            return app(RoadmapController::class)->show($item, $settings);
        }

        return back();
    }
}
