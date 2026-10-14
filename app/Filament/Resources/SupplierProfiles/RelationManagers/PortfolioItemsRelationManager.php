<?php

namespace App\Filament\Resources\SupplierProfiles\RelationManagers;

use App\Models\PortfolioItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The projects the supplier showcases on their public profile. Admins can
 * remove an entry that should not be public.
 */
class PortfolioItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'portfolioItems';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.portfolio');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('media'))
            ->columns([
                ImageColumn::make('images')
                    ->label(__('admin.images'))
                    ->state(fn (PortfolioItem $record): array => $record->getMedia('images')
                        ->map(fn (Media $media): string => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl())
                        ->all())
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),
                TextColumn::make('title')->label(__('supplier.portfolio_title'))->weight('bold')->wrap(),
                TextColumn::make('description')->label(__('supplier.portfolio_description'))->limit(120)->wrap()->placeholder('—'),
                TextColumn::make('client_type')->label(__('supplier.portfolio_client_type'))->placeholder('—'),
                TextColumn::make('year')->label(__('supplier.portfolio_year'))->placeholder('—'),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
