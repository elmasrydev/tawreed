<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListUsers::route('/')];
    }
}
