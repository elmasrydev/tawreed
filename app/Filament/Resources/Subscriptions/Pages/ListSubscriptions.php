<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<int, class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [SubscriptionStats::class];
    }
}

class SubscriptionStats extends StatsOverviewWidget
{
    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make(__('subscription.active_title'), Subscription::query()->current()->count())
                ->color('success')
                ->extraAttributes(['class' => 'th-kpi-teal']),
            Stat::make(__('admin.expiring_soon'), Subscription::query()->expiringWithin(7)->count())
                ->color('warning')
                ->extraAttributes(['class' => 'th-kpi-amber']),
            Stat::make(__('admin.expired_paused'), Subscription::query()
                ->where('ends_at', '<=', now())->count())
                ->color('danger')
                ->extraAttributes(['class' => 'th-kpi-red']),
        ];
    }
}
