<?php

namespace App\Filament\Resources\Reports;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Models\Message;
use App\Models\Quote;
use App\Models\Report;
use App\Models\Rfq;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('admin.reports');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.group_moderation');
    }

    public static function getModelLabel(): string
    {
        return __('admin.report');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.reports');
    }

    public static function getNavigationBadge(): ?string
    {
        $open = static::getModel()::query()->open()->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['reporter', 'reportable']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label(__('admin.report'))
                    ->badge()
                    ->formatStateUsing(fn (ReportType $state): string => $state->label()),
                TextColumn::make('reportable')
                    ->label(__('admin.reported_item'))
                    ->getStateUsing(fn (Report $record): string => static::describe($record))
                    ->wrap(),
                TextColumn::make('reason')->label(__('admin.note'))->wrap()->limit(120),
                TextColumn::make('reporter.name')
                    ->label(__('admin.reporter'))
                    ->placeholder(__('admin.system')),
                TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn (ReportStatus $state): string => $state->label())
                    ->color(fn (ReportStatus $state): string => $state->color()),
                TextColumn::make('created_at')->label(__('admin.applied_at'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.status'))
                    ->options(fn (): array => collect(ReportStatus::cases())
                        ->mapWithKeys(fn (ReportStatus $case): array => [$case->value => $case->label()])->all())
                    ->default(ReportStatus::Open->value),
            ])
            ->recordActions([
                Action::make('remove')
                    ->label(__('admin.remove_content'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Report $record): bool => $record->isOpen())
                    ->schema([Textarea::make('note')->label(__('admin.resolution'))->maxLength(500)])
                    ->action(fn (Report $record, array $data) => static::resolve($record, ReportStatus::Removed, $data['note'] ?? null)),

                Action::make('dismiss')
                    ->label(__('admin.dismiss'))
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Report $record): bool => $record->isOpen())
                    ->action(fn (Report $record) => static::resolve($record, ReportStatus::Dismissed, null)),
            ]);
    }

    /**
     * Removing content soft-deletes the offending record so the decision stays
     * auditable, then closes the report.
     */
    private static function resolve(Report $record, ReportStatus $status, ?string $note): void
    {
        DB::transaction(function () use ($record, $status, $note): void {
            if ($status === ReportStatus::Removed) {
                $target = $record->reportable;

                match (true) {
                    $target instanceof Message => $target->update(['is_removed' => true]),
                    $target instanceof Quote, $target instanceof Rfq => $target->delete(),
                    default => null,
                };
            }

            $record->forceFill([
                'status' => $status,
                'resolved_by' => auth()->id(),
                'resolution_note' => $note,
                'resolved_at' => now(),
            ])->save();
        });

        Notification::make()
            ->success()
            ->title($status === ReportStatus::Removed ? __('admin.remove_done') : __('admin.dismiss_done'))
            ->send();
    }

    private static function describe(Report $record): string
    {
        return match (true) {
            $record->reportable instanceof Quote => __('admin.quote').' #'.$record->reportable_id,
            $record->reportable instanceof Rfq => $record->reportable->title,
            $record->reportable instanceof Message => __('admin.report').' #'.$record->reportable_id,
            default => class_basename($record->reportable_type).' #'.$record->reportable_id,
        };
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListReports::route('/')];
    }
}
