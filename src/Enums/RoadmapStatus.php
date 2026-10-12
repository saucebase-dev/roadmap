<?php

namespace Modules\Roadmap\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum RoadmapStatus: string implements HasColor, HasLabel
{
    case UnderReview = 'under_review';
    case Backlog = 'backlog';
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Shipped = 'shipped';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::UnderReview => __('Under Review'),
            self::Backlog => __('Backlog'),
            self::Planned => __('Planned'),
            self::InProgress => __('In Progress'),
            self::Shipped => __('Shipped'),
            self::Closed => __('Closed'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::UnderReview => 'warning',
            self::Backlog => 'gray',
            self::Planned => 'info',
            self::InProgress => 'primary',
            self::Shipped => 'success',
            self::Closed => 'danger',
        };
    }

    /**
     * Statuses visible to visitors.
     *
     * @return array<int, self>
     */
    public static function publicStatuses(): array
    {
        return [self::Backlog, ...self::boardStatuses()];
    }

    /**
     * Statuses that get their own column on the roadmap board.
     *
     * @return array<int, self>
     */
    public static function boardStatuses(): array
    {
        return [self::Planned, self::InProgress, self::Shipped];
    }
}
