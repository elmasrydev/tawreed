<?php

namespace App\Filament\Resources\SupplierProfiles\Pages;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Filament\Resources\SupplierProfiles\SupplierProfileResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListSupplierProfiles extends ListRecords
{
    protected static string $resource = SupplierProfileResource::class;

    /**
     * New suppliers are created as accounts, with their company details on
     * the same form.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createSupplier')
                ->label(__('admin.new_supplier'))
                ->icon(Heroicon::OutlinedPlus)
                ->url(UserResource::getUrl('create', ['role' => UserRole::Supplier->value])),
        ];
    }

    /**
     * The queue opens on suppliers awaiting review, which is the work item.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [
            'pending' => Tab::make(__('admin.awaiting_review'))
                ->modifyQueryUsing(fn ($query) => $query->awaitingReview())
                ->badge(SupplierProfileResource::getModel()::query()->awaitingReview()->count()),
        ];

        foreach ([VerificationStatus::Verified, VerificationStatus::Rejected, VerificationStatus::Suspended] as $status) {
            $tabs[$status->value] = Tab::make($status->label())
                ->modifyQueryUsing(fn ($query) => $query->where('verification_status', $status));
        }

        $tabs['all'] = Tab::make(__('admin.all'));

        return $tabs;
    }
}
