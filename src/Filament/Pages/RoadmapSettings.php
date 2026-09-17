<?php

namespace Modules\Roadmap\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Roadmap\Settings\RoadmapSettings as Settings;
use Saucebase\Core\Filament\Pages\SettingsPage;

class RoadmapSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = 4;

    protected static string $settings = Settings::class;

    public static function getNavigationLabel(): string
    {
        return __('Roadmap');
    }

    public function getTitle(): string
    {
        return __('Roadmap Settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('Comments'))
                ->description(__('Let visitors discuss roadmap items.'))
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->schema([
                    Toggle::make('comments_enabled')
                        ->label(__('Enable comments'))
                        ->helperText(__('Existing comments stay in the database and reappear when you switch this back on.'))
                        ->extraAttributes(['data-testid' => 'admin-roadmap-comments-enabled']),
                ])
                ->columns(1),
        ]);
    }
}
