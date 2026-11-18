<?php

namespace App\Filament\Resources\Payments;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only log of every subscription payment attempt. Refunds are handled
 * in the Paymob dashboard.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 31;

    public static function getNavigationLabel(): string
    {
        return __('admin.payments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.group_billing');
    }

    public static function getModelLabel(): string
    {
        return __('admin.payment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.payments');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['supplier.supplierProfile', 'plan']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('supplier.supplierProfile.company_name')
                    ->label(__('admin.supplier'))
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('plan.name_ar')
                    ->label(__('admin.plan'))
                    ->getStateUsing(fn (Payment $record): ?string => $record->plan?->name)
                    ->placeholder('—'),
                TextColumn::make('total_egp')
                    ->label(__('admin.amount'))
                    ->numeric(2)
                    ->suffix(' '.__('ui.egp'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->color(fn (PaymentStatus $state): string => $state->color()),
                TextColumn::make('provider_transaction_id')
                    ->label(__('admin.transaction_id'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('failure_reason')
                    ->label(__('admin.failure_reason'))
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reference')
                    ->label(__('admin.reference'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label(__('admin.created_at'))->dateTime()->sortable(),
                TextColumn::make('paid_at')->label(__('admin.paid_at'))->dateTime()->sortable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.status'))
                    ->options(fn (): array => collect(PaymentStatus::cases())
                        ->mapWithKeys(fn (PaymentStatus $case): array => [$case->value => $case->label()])->all()),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListPayments::route('/')];
    }
}
