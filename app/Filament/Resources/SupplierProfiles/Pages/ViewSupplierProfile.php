<?php

namespace App\Filament\Resources\SupplierProfiles\Pages;

use App\Filament\Resources\SupplierProfiles\SupplierProfileResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSupplierProfile extends ViewRecord
{
    protected static string $resource = SupplierProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('editAccount')
                ->label(__('admin.edit_account'))
                ->icon(Heroicon::OutlinedUser)
                ->color('gray')
                ->url(fn (): string => UserResource::getUrl('edit', ['record' => $this->record->user_id])),
        ];
    }
}
