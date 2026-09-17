<?php

namespace Modules\Roadmap\Filament\Resources\Roadmap\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Enums\RoadmapType;
use Modules\Roadmap\Filament\Resources\RoadmapComments\RoadmapCommentResource;
use Modules\Roadmap\Models\RoadmapItem;

class RoadmapItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // The title column names the item a row was merged into.
            ->modifyQueryUsing(fn (Builder $query) => $query->with('mergedInto'))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->limit(60)
                    ->description(fn (RoadmapItem $record): ?string => $record->mergedInto
                        ? __('Merged into :title', ['title' => $record->mergedInto->title])
                        : null),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('votes_count')
                    ->label(__('Votes'))
                    ->counts('votes')
                    ->sortable(),
                TextColumn::make('comments_count')
                    ->label(__('Comments'))
                    ->counts('comments')
                    ->sortable()
                    ->url(fn (RoadmapItem $record): string => RoadmapCommentResource::getUrl('index', [
                        'tableFilters' => ['roadmap_item_id' => ['value' => $record->getKey()]],
                    ])),
                TextColumn::make('user.name')
                    ->label(__('Submitted by'))
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('Submitted'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(RoadmapStatus::class),
                SelectFilter::make('type')
                    ->options(RoadmapType::class),
            ])
            ->recordActions([
                EditAction::make(),
                self::mergeAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function mergeAction(): Action
    {
        return Action::make('merge')
            ->label(__('Merge'))
            ->icon('heroicon-o-arrows-pointing-in')
            ->modalHeading(__('Merge into another item'))
            ->modalDescription(__('Votes and comments move to the item you pick. This one is closed.'))
            ->hidden(fn (RoadmapItem $record): bool => $record->merged_into_id !== null)
            ->schema([
                Select::make('target_id')
                    ->label(__('Merge into'))
                    ->searchable()
                    // Fetched as the admin types: a long roadmap should not ship
                    // every title into the page each time the modal opens.
                    ->getSearchResultsUsing(fn (RoadmapItem $record, string $search) => RoadmapItem::query()
                        ->whereKeyNot($record->getKey())
                        ->whereNull('merged_into_id')
                        ->where('title', 'like', "%{$search}%")
                        ->orderBy('title')
                        ->limit(50)
                        ->pluck('title', 'id'))
                    ->getOptionLabelUsing(fn (string $value): ?string => RoadmapItem::find($value)?->title)
                    ->required(),
            ])
            ->action(function (RoadmapItem $record, array $data): void {
                $target = RoadmapItem::findOrFail($data['target_id']);

                $record->mergeInto($target);

                Notification::make()
                    ->success()
                    ->title(__('Merged into :title', ['title' => $target->title]))
                    ->send();
            });
    }
}
