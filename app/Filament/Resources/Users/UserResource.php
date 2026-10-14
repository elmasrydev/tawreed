<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Filament\Resources\SupplierProfiles\Schemas\SupplierProfileFields;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\BusinessType;
use App\Models\Governorate;
use App\Models\User;
use App\Rules\EgyptianMobile;
use App\Support\PhoneNumber;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('admin.users');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.group_marketplace');
    }

    public static function getModelLabel(): string
    {
        return __('admin.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.users');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.account'))->columns(2)->schema([
                TextInput::make('name')->label(__('admin.full_name'))->required()->maxLength(120),
                TextInput::make('email')
                    ->label(__('ui.email'))
                    ->email()
                    ->required()
                    ->maxLength(180)
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label(__('ui.phone'))
                    ->required()
                    ->tel()
                    ->rules(fn (?User $record): array => [
                        new EgyptianMobile,
                        function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            $taken = User::withTrashed()
                                ->where('phone', PhoneNumber::normalize((string) $value))
                                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($taken) {
                                $fail(__('validation.unique', ['attribute' => __('ui.phone')]));
                            }
                        },
                    ]),
                TextInput::make('password')
                    ->label(__('ui.password'))
                    ->password()
                    ->revealable()
                    ->rule(Password::defaults())
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? __('admin.password_keep_hint') : null),
                Select::make('role')
                    ->label(__('admin.role'))
                    ->options(fn (): array => self::enumOptions(UserRole::cases()))
                    ->default(fn (): string => UserRole::tryFrom((string) request()->query('role'))?->value ?? UserRole::Buyer->value)
                    ->required()
                    ->live()
                    ->visibleOn('create'),
                Select::make('status')
                    ->label(__('ui.account_status'))
                    ->options(fn (): array => self::enumOptions(UserStatus::cases()))
                    ->default(UserStatus::Active->value)
                    ->required()
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                    ->dehydrated(fn (?User $record): bool => ! ($record?->is(auth()->user()) ?? false)),
            ]),

            Section::make(__('admin.buyer_company'))
                ->statePath('buyer')
                ->columns(2)
                ->visible(fn (Get $get, ?User $record): bool => self::roleOf($get, $record) === UserRole::Buyer)
                ->schema([
                    TextInput::make('company_name')->label(__('ui.company_name'))->required()->maxLength(180),
                    TextInput::make('job_title')->label(__('ui.job_title'))->maxLength(120),
                    Select::make('business_type_id')
                        ->label(__('ui.biz_type'))
                        ->options(fn (): array => BusinessType::query()->orderBy('sort')->get()->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    Select::make('governorate_id')
                        ->label(__('ui.governorate'))
                        ->options(fn (): array => Governorate::query()->orderBy('sort')->get()->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    TextInput::make('company_address')->label(__('ui.company_address'))->maxLength(255)->columnSpanFull(),
                    TextInput::make('commercial_reg_no')->label(__('ui.commercial_reg'))->maxLength(60),
                    TextInput::make('tax_card_no')->label(__('ui.tax_card'))->maxLength(60),
                ]),

            Section::make(__('admin.company'))
                ->statePath('supplier')
                ->columns(2)
                ->visible(fn (Get $get, string $operation): bool => $operation === 'create'
                    && self::roleOf($get, null) === UserRole::Supplier)
                ->schema([
                    ...SupplierProfileFields::make(),
                    Select::make('verification_status')
                        ->label(__('admin.verification'))
                        ->options(fn (): array => self::enumOptions(VerificationStatus::cases()))
                        ->default(VerificationStatus::Verified->value)
                        ->required(),
                ]),
        ]);
    }

    /**
     * @param  array<int, UserRole|UserStatus|VerificationStatus>  $cases
     * @return array<string, string>
     */
    private static function enumOptions(array $cases): array
    {
        return collect($cases)->mapWithKeys(fn ($case): array => [$case->value => $case->label()])->all();
    }

    /**
     * The role is chosen while creating and fixed afterwards.
     */
    private static function roleOf(Get $get, ?User $record): ?UserRole
    {
        return $record?->role ?? UserRole::tryFrom((string) $get('role'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['buyerProfile.businessType', 'buyerProfile.governorate', 'supplierProfile']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('ui.manager_name'))->searchable()->sortable()->weight('bold'),
                TextColumn::make('company')
                    ->label(__('ui.company_name'))
                    ->getStateUsing(fn (User $record): ?string => $record->buyerProfile?->company_name
                        ?? $record->supplierProfile?->company_name)
                    ->searchable(query: fn ($query, string $search) => $query
                        ->orWhereHas('buyerProfile', fn ($q) => $q->where('company_name', 'like', "%{$search}%"))
                        ->orWhereHas('supplierProfile', fn ($q) => $q->where('company_name', 'like', "%{$search}%")))
                    ->placeholder('—'),
                TextColumn::make('phone')->label(__('ui.phone'))->searchable(),
                TextColumn::make('email')->label(__('ui.email'))->searchable()->toggleable(),
                TextColumn::make('commercial_reg')
                    ->label(__('ui.commercial_reg'))
                    ->getStateUsing(fn (User $record): ?string => $record->buyerProfile?->commercial_reg_no
                        ?? $record->supplierProfile?->commercial_reg_no)
                    ->searchable(query: fn ($query, string $search) => $query
                        ->orWhereHas('supplierProfile', fn ($q) => $q->where('commercial_reg_no', 'like', "%{$search}%")))
                    ->placeholder('—'),
                TextColumn::make('role')
                    ->label(__('ui.biz_type'))
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label()),
                TextColumn::make('status')
                    ->label(__('ui.account_status'))
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state): string => $state->label())
                    ->color(fn (UserStatus $state): string => $state === UserStatus::Active ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('admin.user'))
                    ->options(fn (): array => collect(UserRole::cases())
                        ->mapWithKeys(fn (UserRole $case): array => [$case->value => $case->label()])->all()),
                SelectFilter::make('status')
                    ->label(__('ui.account_status'))
                    ->options(fn (): array => collect(UserStatus::cases())
                        ->mapWithKeys(fn (UserStatus $case): array => [$case->value => $case->label()])->all()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleSuspension')
                    ->label(fn (User $record): string => $record->isSuspended() ? __('admin.unsuspend') : __('admin.suspend'))
                    ->icon(fn (User $record): BackedEnum => $record->isSuspended()
                        ? Heroicon::OutlinedPlayCircle
                        : Heroicon::OutlinedPauseCircle)
                    ->color(fn (User $record): string => $record->isSuspended() ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->hidden(fn (User $record): bool => $record->isAdmin())
                    ->action(function (User $record): void {
                        $suspended = $record->isSuspended();

                        $record->update([
                            'status' => $suspended ? UserStatus::Active : UserStatus::Suspended,
                        ]);

                        Notification::make()
                            ->success()
                            ->title($suspended ? __('admin.unsuspend_done') : __('admin.suspend_done'))
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
