<?php

namespace App\Filament\Resources\SupplierProfiles\RelationManagers;

use App\Actions\Admin\DecideSupplierDocument;
use App\Actions\Supplier\UploadSupplierDocument;
use App\Enums\DocumentStatus;
use App\Enums\SupplierDocumentType;
use App\Models\SupplierDocument;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The supplier's legal documents. Admins open each file from the private
 * disk and accept or reject it; a rejection note is shown to the supplier.
 * Admins may also upload a document on the supplier's behalf.
 */
class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('ui.documents');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('media'))
            ->defaultSort('uploaded_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label(__('admin.document_type'))
                    ->formatStateUsing(fn (SupplierDocumentType $state): string => $state->label())
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn (DocumentStatus $state): string => $state->label())
                    ->color(fn (DocumentStatus $state): string => $state->color()),
                TextColumn::make('note')->label(__('admin.note'))->placeholder('—')->wrap(),
                TextColumn::make('uploaded_at')->label(__('admin.uploaded_at'))->since()->sortable(),
                TextColumn::make('reviewed_at')->label(__('admin.reviewed_at'))->since()->placeholder('—')->sortable(),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label(__('admin.upload_document'))
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->schema([
                        Select::make('type')
                            ->label(__('admin.document_type'))
                            ->options(collect(SupplierDocumentType::required())
                                ->mapWithKeys(fn (SupplierDocumentType $type): array => [$type->value => $type->label()])
                                ->all())
                            ->required(),
                        FileUpload::make('file')
                            ->label(__('admin.file'))
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        app(UploadSupplierDocument::class)->handle(
                            $this->getOwnerRecord(),
                            SupplierDocumentType::from($data['type']),
                            $data['file'],
                        );

                        Notification::make()->success()->title(__('admin.document_uploaded'))->send();
                    }),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('admin.open_file'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->visible(fn (SupplierDocument $record): bool => $record->hasMedia('file'))
                    ->url(fn (SupplierDocument $record): string => route('supplier-documents.show', $record), shouldOpenInNewTab: true),
                $this->decisionAction('accept_document', DocumentStatus::Accepted, 'success', Heroicon::OutlinedCheckCircle),
                $this->decisionAction('reject_document', DocumentStatus::Rejected, 'danger', Heroicon::OutlinedXCircle, requiresNote: true),
            ]);
    }

    private function decisionAction(
        string $key,
        DocumentStatus $status,
        string $color,
        BackedEnum $icon,
        bool $requiresNote = false,
    ): Action {
        return Action::make($key)
            ->label(__("admin.{$key}"))
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->visible(fn (SupplierDocument $record): bool => $record->status !== $status)
            ->schema($requiresNote ? [
                Textarea::make('note')->label(__('admin.note'))->required()->maxLength(500),
            ] : [])
            ->action(function (SupplierDocument $record, array $data) use ($status, $key): void {
                app(DecideSupplierDocument::class)->handle($record, $status, $data['note'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__("admin.{$key}_done"))
                    ->send();
            });
    }
}
