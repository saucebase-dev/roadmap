<?php

namespace Modules\Roadmap\Filament\Resources\RoadmapComments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoadmapCommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('Comment'))
                    ->schema([
                        Textarea::make('body')
                            ->hiddenLabel()
                            ->required()
                            ->rows(5)
                            ->maxLength(2000),
                    ]),

                Section::make(__('Moderation'))
                    ->description(__('A hidden comment stays here but disappears from the public page.'))
                    ->schema([
                        TextInput::make('hidden_reason')
                            ->label(__('Reason it is hidden'))
                            ->placeholder(__('Only you and other admins see this'))
                            ->maxLength(255)
                            ->disabled(fn ($record): bool => $record?->hidden_at === null)
                            ->helperText(fn ($record): ?string => $record?->hidden_at === null
                                ? __('This comment is visible. Use the Hide action to take it down.')
                                : null),
                    ]),
            ]);
    }
}
