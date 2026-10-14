<?php

namespace App\Filament\Resources\SupplierProfiles\RelationManagers;

use App\Models\Rfq;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The supplier's successful deals: every request awarded to them.
 */
class AwardedRfqsRelationManager extends RelationManager
{
    protected static string $relationship = 'awardedRfqs';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.successful_deals');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['category', 'unit', 'buyer.buyerProfile', 'awardedQuote']))
            ->columns([
                TextColumn::make('reference')->label(__('admin.reference'))->searchable(),
                TextColumn::make('title')->label(__('buyer.col_title'))->weight('bold')->wrap()->searchable(),
                TextColumn::make('category.name_ar')->label(__('ui.categories'))->placeholder('—'),
                TextColumn::make('buyer.buyerProfile.company_name')->label(__('admin.buyer'))->placeholder('—'),
                TextColumn::make('deal_total')
                    ->label(__('buyer.col_total'))
                    ->state(fn (Rfq $record): ?float => $record->awardedQuote?->grandTotal())
                    ->money('EGP'),
                TextColumn::make('awarded_at')->label(__('buyer.col_awarded_on'))->date(),
            ]);
    }
}
