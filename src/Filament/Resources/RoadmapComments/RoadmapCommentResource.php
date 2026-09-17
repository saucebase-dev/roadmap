<?php

namespace Modules\Roadmap\Filament\Resources\RoadmapComments;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Roadmap\Filament\Resources\RoadmapComments\Pages\EditRoadmapComment;
use Modules\Roadmap\Filament\Resources\RoadmapComments\Pages\ListRoadmapComments;
use Modules\Roadmap\Filament\Resources\RoadmapComments\Schemas\RoadmapCommentForm;
use Modules\Roadmap\Filament\Resources\RoadmapComments\Tables\RoadmapCommentsTable;
use Modules\Roadmap\Models\RoadmapComment;

class RoadmapCommentResource extends Resource
{
    protected static ?string $model = RoadmapComment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    /**
     * Reached from the Roadmap list, not the sidebar: comments belong to an
     * item, and the sidebar already carries Roadmap and its settings.
     */
    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return __('Comment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Comments');
    }

    public static function form(Schema $schema): Schema
    {
        return RoadmapCommentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoadmapCommentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoadmapComments::route('/'),
            'edit' => EditRoadmapComment::route('/{record}/edit'),
        ];
    }
}
