<?php

namespace Modules\Roadmap\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Enums\RoadmapType;
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
        abort_unless(in_array($item->status, RoadmapStatus::publicStatuses()), 404);

        $item->loadCount(['votes', 'visibleComments as comments_count'])->load('visibleComments.user');

        $render = request()->hasHeader(Modal::HEADER_MODAL)
            ? fn (string $component, array $props) => Inertia::modal($component, [...$props, 'modal' => true])
            : fn (string $component, array $props) => Inertia::render($component, $props)->withSSR();

        return $render('Roadmap::Show', [
            'item' => [
                ...$this->itemPayload($item, $this->votedItemIds(collect([$item->id]))),
                'official_response' => $item->official_response,
                'official_response_at' => $item->official_response_at?->toIso8601String(),
                'comments_enabled' => $settings->comments_enabled,
                'comments' => $item->visibleComments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at->toIso8601String(),
                    'author' => $this->displayName($comment->user?->name),
                    'mine' => $comment->user_id === auth()->id(),
                ]),
            ],
            'authenticated' => auth()->check(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(RoadmapType::class)],
        ]);

        // Submitting is itself a vote: nobody suggests an idea they do not want,
        // so the two writes stand or fall together.
        DB::transaction(function () use ($validated, $request): void {
            $item = RoadmapItem::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => RoadmapStatus::UnderReview,
                'type' => RoadmapType::from($validated['type']),
                'user_id' => $request->user()->id,
            ]);

            $item->votes()->create(['user_id' => $request->user()->id]);
        });

        return redirect()->route('roadmap.index')
            ->with('toast', [
                'type' => 'success',
                'message' => __('Suggestion submitted!'),
                'description' => __('Your idea is under review. We\'ll publish it once it is approved.'),
            ]);
    }

    /**
     * A commenter is shown as their first name and the initial of the next one,
     * so a public page never carries someone's full name.
     */
    private function displayName(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return __('Anonymous');
        }

        $first = array_shift($parts);

        return $parts === [] ? $first : $first.' '.mb_strtoupper(mb_substr($parts[0], 0, 1)).'.';
    }

    /**
     * @param  Collection<int, RoadmapItem>  $items
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function itemsPayload(Collection $items): \Illuminate\Support\Collection
    {
        $votedItemIds = $this->votedItemIds($items->pluck('id'));

        return $items->map(fn (RoadmapItem $item) => $this->itemPayload($item, $votedItemIds));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $votedItemIds
     * @return array<string, mixed>
     */
    private function itemPayload(RoadmapItem $item, \Illuminate\Support\Collection $votedItemIds): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'slug' => $item->slug,
            'url' => $item->url(),
            'description' => $item->description,
            'status' => $item->status->value,
            'status_label' => $item->status->getLabel(),
            'type' => $item->type->value,
            'type_label' => $item->type->getLabel(),
            'votes_count' => $item->votes_count,
            'comments_count' => $item->comments_count,
            'has_voted' => $votedItemIds->contains($item->id),
            'created_at' => $item->created_at->toDateString(),
        ];
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
