<?php

namespace App\Filament\Widgets;

use App\Enums\RfqStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Rfqs\RfqResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Resources\SupplierProfiles\SupplierProfileResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Rfq;
use App\Models\Subscription;
use App\Models\SupplierProfile;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The headline KPI cards from the design's admin overview. Each card carries a
 * coloured top accent (blue for marketplace volume, orange for work waiting on
 * the team, teal for revenue) and links to the list it summarises.
 */
class MarketplaceStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * @var array<string, int>
     */
    protected int|array|null $columns = ['default' => 2, '@2xl' => 3, '@5xl' => 6, '!@md' => 3, '!@xl' => 6];

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $thisMonth = (float) Invoice::query()
            ->whereBetween('issued_at', [now()->startOfMonth(), now()])
            ->sum('total_egp');

        $lastMonth = (float) Invoice::query()
            ->whereBetween('issued_at', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->sum('total_egp');

        $pendingVerifications = SupplierProfile::query()->awaitingReview()->count();
        $expiringSoon = Subscription::query()->expiringWithin(7)->count();

        return [
            Stat::make(__('admin.users'), number_format(User::query()->where('role', '!=', UserRole::Admin)->count()))
                ->description(__('admin.this_month', [
                    'count' => '+'.User::query()->where('created_at', '>=', now()->startOfMonth())->count(),
                ]))
                ->descriptionColor('success')
                ->url(UserResource::getUrl())
                ->extraAttributes(['class' => 'th-kpi-blue']),

            Stat::make(__('admin.pending_verifications'), number_format($pendingVerifications))
                ->description(__('admin.waiting_review'))
                ->descriptionColor($pendingVerifications > 0 ? 'danger' : 'gray')
                ->url(SupplierProfileResource::getUrl(parameters: ['tab' => 'pending']))
                ->extraAttributes(['class' => 'th-kpi-orange']),

            Stat::make(__('admin.active_rfqs'), number_format(Rfq::query()->where('status', RfqStatus::Open)->count()))
                ->description(__('admin.this_week', [
                    'count' => '+'.Rfq::query()->where('published_at', '>=', now()->subWeek())->count(),
                ]))
                ->descriptionColor('success')
                ->url(RfqResource::getUrl())
                ->extraAttributes(['class' => 'th-kpi-blue']),

            Stat::make(__('admin.quotes_submitted'), number_format(Quote::query()->count()))
                ->description(__('admin.this_week', [
                    'count' => '+'.Quote::query()->where('created_at', '>=', now()->subWeek())->count(),
                ]))
                ->descriptionColor('gray')
                ->extraAttributes(['class' => 'th-kpi-orange']),

            Stat::make(__('admin.active_subscriptions'), number_format(Subscription::query()->current()->count()))
                ->description(__('admin.expiring_soon').': '.$expiringSoon)
                ->descriptionColor($expiringSoon > 0 ? 'warning' : 'gray')
                ->url(SubscriptionResource::getUrl())
                ->extraAttributes(['class' => 'th-kpi-teal']),

            Stat::make(__('admin.monthly_revenue'), number_format($thisMonth, 2).' '.__('ui.egp'))
                ->description(__('admin.vs_last_month', [
                    'percent' => $this->growth($thisMonth, $lastMonth),
                ]))
                ->descriptionColor($thisMonth >= $lastMonth ? 'success' : 'danger')
                ->extraAttributes(['class' => 'th-kpi-teal']),
        ];
    }

    private function growth(float $current, float $previous): string
    {
        if ($previous <= 0.0) {
            return $current > 0.0 ? '+100%' : '0%';
        }

        return sprintf('%+.0f%%', (($current - $previous) / $previous) * 100);
    }
}
