<?php

namespace App\Filament\Resources\SupplierProfiles\Actions;

use App\Actions\Admin\DecideSupplierVerification;
use App\Enums\VerificationStatus;
use App\Models\SupplierProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * The verification decisions, shown as plain buttons wherever a supplier is
 * reviewed: the dashboard queue, the suppliers table and the supplier pages.
 * Approve and reject apply to accounts under review; suspend applies to any
 * account that is not already suspended, and reactivate brings one back.
 */
class VerificationActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        return [
            self::decision('approve', VerificationStatus::Verified, 'success', Heroicon::OutlinedCheckBadge,
                visibleWhen: [VerificationStatus::Pending]),
            self::decision('reject', VerificationStatus::Rejected, 'danger', Heroicon::OutlinedXCircle,
                visibleWhen: [VerificationStatus::Pending], requiresNote: true),
            self::decision('suspend', VerificationStatus::Suspended, 'gray', Heroicon::OutlinedPauseCircle,
                visibleWhen: [VerificationStatus::Pending, VerificationStatus::Verified, VerificationStatus::Rejected], requiresNote: true),
            self::decision('reactivate', VerificationStatus::Verified, 'success', Heroicon::OutlinedPlayCircle,
                visibleWhen: [VerificationStatus::Suspended]),
        ];
    }

    /**
     * Rejection and suspension ask for a note, because the supplier is shown
     * that text in their account.
     *
     * @param  array<int, VerificationStatus>  $visibleWhen
     */
    private static function decision(
        string $key,
        VerificationStatus $status,
        string $color,
        BackedEnum $icon,
        array $visibleWhen,
        bool $requiresNote = false,
    ): Action {
        return Action::make($key)
            ->label(__("admin.{$key}"))
            ->icon($icon)
            ->color($color)
            ->button()
            ->size('sm')
            ->requiresConfirmation()
            ->modalDescription(__("admin.{$key}_confirm"))
            ->visible(fn (SupplierProfile $record): bool => in_array($record->verification_status, $visibleWhen, true))
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
}
