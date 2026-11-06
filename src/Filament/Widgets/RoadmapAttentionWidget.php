<?php

namespace Modules\Roadmap\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Filament\Resources\Roadmap\RoadmapItemResource;
use Modules\Roadmap\Models\RoadmapItem;

/** Attention band (20–29): only while suggestions wait for review. */
class RoadmapAttentionWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 21;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    /** Half the dashboard: pairs with its neighbour in the band. */
    protected int|string|array $columnSpan = 1;

    protected int|array|null $columns = 1;

    public static function canView(): bool
    {
        return (auth()->user()?->can('manage roadmap') ?? false) && self::underReview() > 0;
    }

    protected function getStats(): array
    {
        return [
            Stat::make(__('Suggestions to review'), self::underReview())
                ->description(__('Waiting for a status'))
                ->descriptionIcon('heroicon-m-light-bulb')
                ->color('warning')
                ->url(RoadmapItemResource::getUrl('index')),
        ];
    }

    private static function underReview(): int
    {
        return RoadmapItem::where('status', RoadmapStatus::UnderReview)->count();
    }
}
