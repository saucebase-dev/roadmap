<?php

namespace Modules\Roadmap\Filament\Resources\Roadmap\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Enums\RoadmapType;

class RoadmapItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    TextInput::make('title')
                        ->hiddenLabel()
                        ->placeholder(__('Add title'))
                        ->required()
                        ->maxLength(255)
                        ->extraInputAttributes(['class' => 'text-2xl font-semibold']),

                    TextInput::make('slug')
                        ->label(__('Slug'))
                        ->nullable()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->placeholder(__('Auto-generated from title')),

                    Textarea::make('description')
                        ->hiddenLabel()
                        ->nullable()
                        ->rows(6),

                    Section::make(__('Team response'))
                        ->description(__('Shown at the top of the item, above the comments.'))
                        ->schema([
                            Textarea::make('official_response')
                                ->hiddenLabel()
                                ->nullable()
                                ->rows(4)
                                ->maxLength(2000),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make(__('Publish'))
                        ->schema([
                            Select::make('status')
                                ->options(RoadmapStatus::class)
                                ->default(RoadmapStatus::UnderReview)
                                ->required()
                                ->native(false)
                                ->helperText(__('Under Review and Closed items stay hidden from visitors.')),

                            Select::make('type')
                                ->options(RoadmapType::class)
                                ->default(RoadmapType::Feature)
                                ->required()
                                ->native(false),
                        ]),

                    Section::make(__('Submitted by'))
                        ->schema([
                            Select::make('user_id')
                                ->hiddenLabel()
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->placeholder(__('Nobody')),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }
}
