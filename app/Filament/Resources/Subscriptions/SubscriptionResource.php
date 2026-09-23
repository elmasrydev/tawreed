<?php

namespace App\Filament\Resources\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Models\Invoice;
use App\Models\Subscription;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('admin.subscriptions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.group_billing');
    }

    public static function getModelLabel(): string
    {
        return __('admin.subscription');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.subscriptions');
    }

    /**
     * Subscriptions about to lapse need a nudge, so the sidebar flags them.
     */
    public static function getNavigationBadge(): ?string
    {
        $expiring = static::getModel()::query()->expiringWithin(7)->count();

        return $expiring > 0 ? (string) $expiring : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('admin.expiring_soon');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['supplier.supplierProfile', 'plan']))
            ->defaultSort('ends_at', 'desc')
            ->columns([
                TextColumn::make('supplier.supplierProfile.company_name')
                    ->label(__('admin.supplier'))
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('plan.name_ar')
                    ->label(__('admin.plan'))
                    ->getStateUsing(fn (Subscription $record): ?string => $record->plan?->name)
                    ->placeholder('—'),
                TextColumn::make('starts_at')->label(__('admin.starts_at'))->date()->sortable(),
                TextColumn::make('ends_at')->label(__('admin.ends_at'))->date()->sortable(),
                TextColumn::make('amount_egp')
                    ->label(__('admin.amount'))
                    ->numeric(2)
                    ->suffix(' '.__('ui.egp'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn (Subscription $record): string => $record->isExpiringSoon()
                        ? __('admin.expiring_soon')
                        : $record->status->label())
                    ->color(fn (Subscription $record): string => $record->isExpiringSoon()
                        ? 'warning'
                        : $record->status->color()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.status'))
                    ->options(fn (): array => collect(SubscriptionStatus::cases())
                        ->mapWithKeys(fn (SubscriptionStatus $case): array => [$case->value => $case->label()])->all()),
            ])
            ->recordActions([
                Action::make('extend')
                    ->label(__('admin.extend'))
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->color('success')
                    ->schema([
                        TextInput::make('days')
                            ->label(__('admin.days'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->default(30)
                            ->required(),
                    ])
                    ->action(function (Subscription $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $base = $record->ends_at->isFuture() ? $record->ends_at : now();

                            $record->update([
                                'ends_at' => $base->copy()->addDays((int) $data['days']),
                                'status' => SubscriptionStatus::Active,
                            ]);

                            Invoice::create([
                                'subscription_id' => $record->id,
                                'supplier_id' => $record->supplier_id,
                                'number' => Invoice::nextNumber(),
                                'amount_egp' => 0,
                                'vat_egp' => 0,
                                'total_egp' => 0,
                                'issued_at' => now(),
                            ]);
                        });

                        Notification::make()->success()->title(__('admin.extend_done'))->send();
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListSubscriptions::route('/')];
    }
}
