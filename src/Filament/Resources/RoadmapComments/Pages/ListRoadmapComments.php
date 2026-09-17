<?php

namespace Modules\Roadmap\Filament\Resources\RoadmapComments\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Roadmap\Filament\Resources\RoadmapComments\RoadmapCommentResource;

class ListRoadmapComments extends ListRecords
{
    protected static string $resource = RoadmapCommentResource::class;
}
