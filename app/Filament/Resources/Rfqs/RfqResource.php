<?php

namespace App\Filament\Resources\Rfqs;

use App\Enums\RfqStatus;
use App\Filament\Resources\Rfqs\Pages\ListRfqs;
use App\Models\Rfq;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RfqResource extends Resource
{
    protected static ?string $model = Rfq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return __('admin.rfqs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.group_marketplace');
    }

    public static function getModelLabel(): string
    {
        return __('admin.rfq');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.rfqs');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['buyer.buyerProfile', 'category', 'governorate', 'unit']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.rfq'))
                    ->searchable(['title', 'reference'])
                    ->weight('bold')
                    ->wrap()
                    ->placeholder('—')
                    ->description(fn (Rfq $record): string => collect([$record->reference, $record->governorate?->name])
                        ->filter()
                        ->implode(' · '))
                    ->extraCellAttributes(['class' => 'th-cell-wide']),
                TextColumn::make('buyer.buyerProfile.company_name')->label(__('admin.buyer'))->searchable()->placeholder('—'),
                TextColumn::make('category.name_ar')
                    ->label(__('admin.category'))
                    ->getStateUsing(fn (Rfq $record): ?string => $record->category?->name)
                    ->placeholder('—'),
                TextColumn::make('quotes_count')->label(__('admin.quotes'))->numeric()->sortable(),
                TextColumn::make('quote_deadline')->label(__('ui.quote_deadline'))->date()->sortable()->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn (RfqStatus $state): string => $state->label())
                    ->color(fn (RfqStatus $state): string => $state->color()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.status'))
                    ->options(fn (): array => collect(RfqStatus::cases())
                        ->mapWithKeys(fn (RfqStatus $case): array => [$case->value => $case->label()])->all()),
            ])
            ->recordActions([DeleteAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListRfqs::route('/')];
    }
}
