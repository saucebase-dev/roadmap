<?php

namespace Modules\Roadmap\Data;

use Modules\Roadmap\Models\RoadmapComment;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class RoadmapCommentData extends Data
{
    public function __construct(
        public int $id,
        public string $body,
        public string $created_at,
        public string $author,
        public bool $mine,
    ) {}

    public static function fromComment(RoadmapComment $comment, ?int $viewerId): self
    {
        return new self(
            id: $comment->id,
            body: $comment->body,
            created_at: $comment->created_at->toIso8601String(),
            author: self::displayName($comment->user?->name),
            mine: $comment->user_id === $viewerId,
        );
    }

    /**
     * A commenter is shown as their first name and the initial of the next one,
     * so a public page never carries someone's full name.
     */
    private static function displayName(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return __('Anonymous');
        }

        $first = array_shift($parts);

        return $parts === [] ? $first : $first.' '.mb_strtoupper(mb_substr($parts[0], 0, 1)).'.';
    }
}
