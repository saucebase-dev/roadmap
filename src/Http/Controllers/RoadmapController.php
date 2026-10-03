<?php

namespace Modules\Roadmap\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;
use Modules\Roadmap\Actions\SubmitSuggestion;
use Modules\Roadmap\Data\RoadmapCommentData;
use Modules\Roadmap\Data\RoadmapItemData;
use Modules\Roadmap\Data\RoadmapItemDetailData;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Enums\RoadmapType;
use Modules\Roadmap\Models\RoadmapComment;
use Modules\Roadmap\Models\RoadmapItem;
use Modules\Roadmap\Models\RoadmapVote;
use Modules\Roadmap\Settings\RoadmapSettings;

class RoadmapController
{
    public function index(Request $request, RoadmapSettings $settings): Response
    {
        $sort = in_array($request->input('sort'), ['trending', 'new', 'old'])
            ? $request->input('sort')
            : 'trending';

        $mine = $request->boolean('mine') && auth()->check();

        $items = RoadmapItem::query()
            ->when($mine,
                fn ($q) => $q->submittedBy((int) auth()->id()),
                fn ($q) => $q->public(),
            )
            ->when($sort === 'new', fn ($q) => $q->orderByDesc('created_at'))
            ->when($sort === 'old', fn ($q) => $q->orderBy('created_at'))
            ->when($sort === 'trending', fn ($q) => $q->orderByDesc('votes_count'))
            ->limit(50)
            ->get();

        return Inertia::render('Roadmap::Index', [
            'items' => $this->itemsPayload($items),
            'authenticated' => auth()->check(),
            'comments_enabled' => $settings->comments_enabled,
            'sort' => $sort,
            'mine' => $mine,
            'columns' => collect(RoadmapStatus::boardStatuses())->map(fn (RoadmapStatus $status) => [
                'value' => $status->value,
                'label' => $status->getLabel(),
            ]),
            'types' => collect(RoadmapType::cases())->map(fn (RoadmapType $t) => [
                'value' => $t->value,
                'label' => $t->getLabel(),
                'color' => $t->getColor(),
            ]),
        ])->withSSR();
    }

    /**
     * One item, as a page or as a modal over the board.
     *
     * Deciding on the request header rather than the referer matters: the modal
     * package would otherwise treat any ordinary link as "open me over the
     * previous page", and a shared link has to stay a real page.
     */
    public function show(RoadmapItem $item, RoadmapSettings $settings): Response|Modal
    {
        abort_unless($item->isPublic(), 404);

        $item->loadCount(['votes', 'visibleComments as comments_count'])->load('visibleComments.user');

        $render = request()->hasHeader(Modal::HEADER_MODAL)
            ? fn (string $component, array $props) => Inertia::modal($component, [...$props, 'modal' => true])
            : fn (string $component, array $props) => Inertia::render($component, $props)->withSSR();

        $detail = new RoadmapItemDetailData(
            official_response: $item->official_response,
            official_response_at: $item->official_response_at?->toIso8601String(),
            comments_enabled: $settings->comments_enabled,
            comments: $item->visibleComments
                ->map(fn (RoadmapComment $comment) => RoadmapCommentData::fromComment($comment, auth()->id()))
                ->all(),
        );

        return $render('Roadmap::Show', [
            'item' => [
                ...RoadmapItemData::fromItem($item, $this->votedItemIds(collect([$item->id]))->isNotEmpty())->toArray(),
                ...$detail->toArray(),
            ],
            'authenticated' => auth()->check(),
        ]);
    }

    public function store(Request $request, SubmitSuggestion $submitSuggestion): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(RoadmapType::class)],
        ]);

        $submitSuggestion->handle(
            $request->user(),
            $validated['title'],
            $validated['description'] ?? null,
            RoadmapType::from($validated['type']),
        );

        return redirect()->route('roadmap.index')
            ->with('toast', [
                'type' => 'success',
                'message' => __('Suggestion submitted!'),
                'description' => __('Your idea is under review. We\'ll publish it once it is approved.'),
            ]);
    }

    /**
     * @param  Collection<int, RoadmapItem>  $items
     * @return \Illuminate\Support\Collection<int, RoadmapItemData>
     */
    private function itemsPayload(Collection $items): \Illuminate\Support\Collection
    {
        $votedItemIds = $this->votedItemIds($items->pluck('id'));

        return $items->map(fn (RoadmapItem $item) => RoadmapItemData::fromItem($item, $votedItemIds->contains($item->id)));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $itemIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function votedItemIds(\Illuminate\Support\Collection $itemIds): \Illuminate\Support\Collection
    {
        if (! auth()->check()) {
            return collect();
        }

        return RoadmapVote::where('user_id', auth()->id())
            ->whereIn('roadmap_item_id', $itemIds)
            ->pluck('roadmap_item_id');
    }
}
