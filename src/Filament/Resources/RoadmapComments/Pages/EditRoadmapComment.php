<?php

namespace Modules\Roadmap\Filament\Resources\RoadmapComments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Roadmap\Filament\Resources\RoadmapComments\RoadmapCommentResource;

class EditRoadmapComment extends EditRecord
{
    protected static string $resource = RoadmapCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
