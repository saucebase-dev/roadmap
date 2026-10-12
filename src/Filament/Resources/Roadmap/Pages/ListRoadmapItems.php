<?php

namespace Modules\Roadmap\Filament\Resources\Roadmap\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Roadmap\Filament\Resources\Roadmap\RoadmapItemResource;
use Modules\Roadmap\Filament\Resources\RoadmapComments\RoadmapCommentResource;

class ListRoadmapItems extends ListRecords
{
    protected static string $resource = RoadmapItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('comments')
                ->label(__('Comments'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->url(fn (): string => RoadmapCommentResource::getUrl('index')),
            CreateAction::make(),
        ];
    }
}
