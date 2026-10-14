<?php

namespace App\Filament\Resources\SupplierProfiles\Pages;

use App\Filament\Resources\SupplierProfiles\SupplierProfileResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierProfile extends EditRecord
{
    protected static string $resource = SupplierProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
