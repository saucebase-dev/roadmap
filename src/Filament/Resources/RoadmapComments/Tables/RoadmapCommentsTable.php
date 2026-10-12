<?php

namespace Modules\Roadmap\Filament\Resources\RoadmapComments\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\Roadmap\Models\RoadmapComment;
use Modules\Roadmap\Models\RoadmapItem;

class RoadmapCommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('body')
                    ->label(__('Comment'))
                    ->searchable()
                    ->wrap()
                    ->limit(120),
                TextColumn::make('user.name')
                    ->label(__('Author'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('roadmapItem.title')
                    ->label(__('Item'))
                    ->searchable()
                    ->limit(40)
                    ->url(fn (RoadmapComment $record): string => $record->roadmapItem->url()),
                IconColumn::make('hidden_at')
                    ->label(__('Hidden'))
                    ->boolean()
                    ->tooltip(fn (RoadmapComment $record): ?string => $record->hidden_reason),
                TextColumn::make('created_at')
                    ->label(__('Posted'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roadmap_item_id')
                    ->label(__('Item'))
                    ->options(fn (): array => RoadmapItem::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),
                TernaryFilter::make('hidden_at')
                    ->label(__('Hidden'))
                    ->nullable()
                    ->placeholder(__('All comments'))
                    ->trueLabel(__('Hidden only'))
                    ->falseLabel(__('Visible only')),
            ])
            ->recordActions([
                self::hideAction(),
                self::showAction(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Taking a comment down keeps it in the database, so the decision can be
     * looked at later or undone.
     */
    private static function hideAction(): Action
    {
        return Action::make('hide')
            ->label(__('Hide'))
            ->icon('heroicon-o-eye-slash')
            ->color('danger')
            ->hidden(fn (RoadmapComment $record): bool => $record->hidden_at !== null)
            ->modalHeading(__('Hide this comment'))
            ->modalDescription(__('It disappears from the public page. Nobody outside the admin sees the reason.'))
            ->schema([
                TextInput::make('hidden_reason')
                    ->label(__('Reason'))
                    ->placeholder(__('Spam, abuse, off topic…'))
                    ->maxLength(255),
            ])
            ->action(fn (RoadmapComment $record, array $data) => $record->update([
                'hidden_at' => now(),
                'hidden_reason' => $data['hidden_reason'] ?? null,
            ]));
    }

    private static function showAction(): Action
    {
        return Action::make('show')
            ->label(__('Show again'))
            ->icon('heroicon-o-eye')
            ->hidden(fn (RoadmapComment $record): bool => $record->hidden_at === null)
            ->action(fn (RoadmapComment $record) => $record->update([
                'hidden_at' => null,
                'hidden_reason' => null,
            ]));
    }
}
