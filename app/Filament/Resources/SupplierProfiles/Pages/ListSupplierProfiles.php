<?php

namespace App\Filament\Resources\SupplierProfiles\Pages;

use App\Enums\VerificationStatus;
use App\Filament\Resources\SupplierProfiles\SupplierProfileResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListSupplierProfiles extends ListRecords
{
    protected static string $resource = SupplierProfileResource::class;

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
