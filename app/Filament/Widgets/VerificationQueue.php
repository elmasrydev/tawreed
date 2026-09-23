<?php

namespace App\Filament\Widgets;

use App\Actions\Admin\DecideSupplierVerification;
use App\Enums\VerificationStatus;
use App\Filament\Resources\SupplierProfiles\SupplierProfileResource;
use App\Models\SupplierProfile;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Suppliers waiting on a verification decision, surfaced on the dashboard so
 * the queue is the first thing an admin sees.
 */
class VerificationQueue extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): string
    {
        return __('admin.verification_queue');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => SupplierProfile::query()->awaitingReview()->with(['user', 'governorates']))
            ->emptyStateHeading(__('admin.no_pending'))
            ->emptyStateDescription(__('admin.no_pending_body'))
            ->emptyStateIcon(Heroicon::OutlinedShieldCheck)
            ->recordUrl(fn (SupplierProfile $record): string => SupplierProfileResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('ui.company_name'))
                    ->weight('bold')
                    ->description(fn (SupplierProfile $record): ?string => $record->user?->phone),
                TextColumn::make('activity_description')->label(__('ui.activity_desc'))->limit(60),
                TextColumn::make('governorates.name_ar')->label(__('ui.coverage_areas'))->badge()->limitList(2),
                TextColumn::make('created_at')->label(__('admin.applied_at'))->since(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('admin.approve'))
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (SupplierProfile $record): void {
                        app(DecideSupplierVerification::class)
                            ->handle($record, VerificationStatus::Verified, auth()->user());

                        Notification::make()->success()->title(__('admin.approve_done'))->send();
                    }),
            ]);
    }
}
