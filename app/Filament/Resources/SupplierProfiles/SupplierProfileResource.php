<?php

namespace App\Filament\Resources\SupplierProfiles;

use App\Actions\Admin\DecideSupplierVerification;
use App\Enums\SupplierDocumentType;
use App\Enums\VerificationStatus;
use App\Filament\Resources\SupplierProfiles\Pages\ListSupplierProfiles;
use App\Filament\Resources\SupplierProfiles\Pages\ViewSupplierProfile;
use App\Models\SupplierProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

use function Filament\Support\original_request;

class SupplierProfileResource extends Resource
{
    protected static ?string $model = SupplierProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'company_name';

    public static function getNavigationLabel(): string
    {
        return __('admin.suppliers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.group_marketplace');
    }

    public static function getModelLabel(): string
    {
        return __('admin.supplier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.suppliers');
    }

    /**
     * The badge draws the admin's eye to the verification queue.
     */
    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()->awaitingReview()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * The design lists the verification queue and the full supplier directory
     * as two sidebar entries; both open this resource, on different tabs.
     *
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        $isOnIndex = fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.index');
        $isQueueTab = fn (): bool => in_array(original_request()->query('tab'), [null, 'pending'], true);

        return [
            NavigationItem::make(__('admin.verification_queue'))
                ->key(static::class.'.queue')
                ->group(static::getNavigationGroup())
                ->icon(Heroicon::OutlinedShieldCheck)
                ->isActiveWhen(fn (): bool => $isOnIndex() && $isQueueTab())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->sort(static::getNavigationSort())
                ->url(static::getUrl(parameters: ['tab' => 'pending'])),

            NavigationItem::make(static::getNavigationLabel())
                ->key(static::class)
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.*') && ! ($isOnIndex() && $isQueueTab()))
                ->sort(static::getNavigationSort() + 1)
                ->url(static::getUrl(parameters: ['tab' => 'all'])),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.company'))->columns(2)->schema([
                TextEntry::make('company_name')->label(__('ui.company_name')),
                TextEntry::make('user.name')->label(__('ui.responsible_person')),
                TextEntry::make('user.phone')->label(__('ui.phone')),
                TextEntry::make('user.email')->label(__('ui.email')),
                TextEntry::make('commercial_reg_no')->label(__('ui.commercial_reg')),
                TextEntry::make('tax_number')->label(__('ui.tax_number')),
                TextEntry::make('facility_address')->label(__('ui.facility_address'))->columnSpanFull(),
                TextEntry::make('activity_description')->label(__('ui.activity_desc'))->columnSpanFull(),
            ]),

            Section::make(__('admin.verification'))->columns(2)->schema([
                TextEntry::make('verification_status')
                    ->label(__('ui.account_status'))
                    ->badge()
                    ->formatStateUsing(fn (VerificationStatus $state): string => $state->label())
                    ->color(fn (VerificationStatus $state): string => $state->color()),
                TextEntry::make('verified_at')->label(__('admin.verified_at'))->dateTime()->placeholder('—'),
                TextEntry::make('verifier.name')->label(__('admin.decided_by'))->placeholder('—'),
                TextEntry::make('verification_note')->label(__('admin.note'))->placeholder('—')->columnSpanFull(),
                TextEntry::make('documents.type')
                    ->label(__('ui.documents'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof SupplierDocumentType ? $state->label() : (string) $state)
                    ->columnSpanFull(),
            ]),

            Section::make(__('admin.reach'))->columns(3)->schema([
                TextEntry::make('governorates.name_ar')->label(__('ui.coverage_areas'))->badge(),
                TextEntry::make('categories.name_ar')->label(__('ui.categories'))->badge(),
                TextEntry::make('completed_deals_count')->label(__('admin.completed_deals'))->numeric(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'governorates']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('ui.company_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('user.phone')->label(__('ui.phone'))->searchable(),
                TextColumn::make('commercial_reg_no')->label(__('ui.commercial_reg'))->searchable(),
                TextColumn::make('governorates.name_ar')->label(__('ui.coverage_areas'))->badge()->limitList(2),
                TextColumn::make('verification_status')
                    ->label(__('ui.account_status'))
                    ->badge()
                    ->formatStateUsing(fn (VerificationStatus $state): string => $state->label())
                    ->color(fn (VerificationStatus $state): string => $state->color()),
                TextColumn::make('created_at')->label(__('admin.applied_at'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('verification_status')
                    ->label(__('ui.account_status'))
                    ->options(fn (): array => collect(VerificationStatus::cases())
                        ->mapWithKeys(fn (VerificationStatus $case): array => [$case->value => $case->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    static::decisionAction('approve', VerificationStatus::Verified, 'success', Heroicon::OutlinedCheckBadge),
                    static::decisionAction('reject', VerificationStatus::Rejected, 'danger', Heroicon::OutlinedXCircle, requiresNote: true),
                    static::decisionAction('suspend', VerificationStatus::Suspended, 'gray', Heroicon::OutlinedPauseCircle, requiresNote: true),
                ]),
            ]);
    }

    /**
     * Builds one verification decision action. Rejection and suspension ask for
     * a note, because the supplier is shown that text in their account.
     */
    private static function decisionAction(
        string $key,
        VerificationStatus $status,
        string $color,
        BackedEnum $icon,
        bool $requiresNote = false,
    ): Action {
        return Action::make($key)
            ->label(__("admin.{$key}"))
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->visible(fn (SupplierProfile $record): bool => $record->verification_status !== $status)
            ->schema($requiresNote ? [
                Textarea::make('note')->label(__('admin.note'))->required()->maxLength(500),
            ] : [])
            ->action(function (SupplierProfile $record, array $data) use ($status, $key): void {
                app(DecideSupplierVerification::class)
                    ->handle($record, $status, auth()->user(), $data['note'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__("admin.{$key}_done"))
                    ->send();
            });
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplierProfiles::route('/'),
            'view' => ViewSupplierProfile::route('/{record}'),
        ];
    }
}
