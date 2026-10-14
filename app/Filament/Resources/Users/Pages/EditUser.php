<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Admin\SaveUserAccount;
use App\Filament\Resources\SupplierProfiles\SupplierProfileResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property User $record
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * A supplier's company details live on the supplier page, next to their
     * documents and verification decisions.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('editSupplierProfile')
                ->label(__('admin.edit_supplier_profile'))
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->visible(fn (): bool => $this->record->supplierProfile !== null)
                ->url(fn (): string => SupplierProfileResource::getUrl('edit', ['record' => $this->record->supplierProfile])),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['buyer'] = $this->record->buyerProfile?->only([
            'company_name', 'company_address', 'job_title', 'business_type_id',
            'governorate_id', 'commercial_reg_no', 'tax_card_no',
        ]) ?? [];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(SaveUserAccount::class)->handle($data, auth()->user(), $record);
    }
}
