<?php

namespace Modules\Roadmap\Data;

use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Enums\RoadmapType;
use Modules\Roadmap\Models\RoadmapItem;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An item as the board lists it. Load `votes_count` and `comments_count` first. */
#[TypeScript]
final class RoadmapItemData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $url,
        public ?string $description,
        public RoadmapStatus $status,
        public string $status_label,
        public RoadmapType $type,
        public string $type_label,
        public int $votes_count,
        public int $comments_count,
        public bool $has_voted,
        public string $created_at,
    ) {}

    public static function fromItem(RoadmapItem $item, bool $hasVoted): self
    {
        return new self(
            id: $item->id,
            title: $item->title,
            slug: $item->slug,
            url: $item->url(),
            description: $item->description,
            status: $item->status,
            status_label: $item->status->getLabel(),
            type: $item->type,
            type_label: $item->type->getLabel(),
            votes_count: $item->votes_count,
            comments_count: $item->comments_count,
            has_voted: $hasVoted,
            created_at: $item->created_at->toDateString(),
        );
    }
}
