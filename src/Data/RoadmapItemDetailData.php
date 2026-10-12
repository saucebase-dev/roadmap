<?php

namespace Modules\Roadmap\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** What an item's own page carries on top of its `RoadmapItemData`. */
#[TypeScript]
final class RoadmapItemDetailData extends Data
{
    public function __construct(
        public ?string $official_response,
        public ?string $official_response_at,
        public bool $comments_enabled,
        /** @var RoadmapCommentData[] */
        public array $comments,
    ) {}
}
