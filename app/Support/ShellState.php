<?php

namespace App\Support;

use App\Enums\QuoteStatus;
use App\Enums\RfqStatus;
use App\Enums\VerificationStatus;
use App\Models\Message;
use App\Models\Quote;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Figures the app shell shows on every page: sidebar badge counts and the
 * supplier's account state (verification and subscription).
 */
class ShellState
{
    public static function unreadMessages(User $user): int
    {
        return Message::query()
            ->whereHas('conversation', fn (Builder $query) => $query->forParticipant($user))
            ->whereNotNull('sender_id')
            ->whereNot('sender_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return array{rfqs: int, quotes: int, messages: int}
     */
    public static function buyerBadges(User $buyer): array
    {
        return [
            'rfqs' => $buyer->rfqs()->where('status', RfqStatus::Open)->count(),
            'quotes' => Quote::query()
                ->whereIn('rfq_id', $buyer->rfqs()->select('id'))
                ->where('status', QuoteStatus::Pending)
                ->count(),
            'messages' => self::unreadMessages($buyer),
        ];
    }

    /**
     * @return array{quotes: int, messages: int}
     */
    public static function supplierBadges(User $supplier): array
    {
        return [
            'quotes' => $supplier->quotes()
                ->whereIn('status', [QuoteStatus::Pending, QuoteStatus::Shortlisted])
                ->count(),
            'messages' => self::unreadMessages($supplier),
        ];
    }

    /**
     * The supplier's standing, used for the account banner, the plan widget
     * and quote gating hints.
     *
     * @return array{verification: VerificationStatus, subscription: ?Subscription, subscriptionState: string, daysLeft: int, progress: int, banner: ?array{tone: string, icon: string, title: string, body: string, action: string, url: string}}
     */
    public static function supplierAccount(User $supplier): array
    {
        $verification = $supplier->supplierProfile?->verification_status ?? VerificationStatus::Pending;
        $subscription = $supplier->activeSubscription();

        $subscriptionState = match (true) {
            $subscription === null => 'none',
            $subscription->isExpiringSoon() => 'expiring',
            $subscription->status->value === 'trial' => 'trial',
            default => 'active',
        };

        $daysLeft = $subscription?->daysRemaining() ?? 0;
        $totalDays = $subscription ? max(1, (int) $subscription->starts_at->diffInDays($subscription->ends_at)) : 1;
        $progress = $subscription ? (int) min(100, max(0, round((($totalDays - $daysLeft) / $totalDays) * 100))) : 100;

        return [
            'verification' => $verification,
            'subscription' => $subscription,
            'subscriptionState' => $subscriptionState,
            'daysLeft' => $daysLeft,
            'progress' => $progress,
            'banner' => self::supplierBanner($verification, $subscriptionState, $daysLeft),
        ];
    }

    /**
     * @return array{tone: string, icon: string, title: string, body: string, action: string, url: string}|null
     */
    private static function supplierBanner(VerificationStatus $verification, string $subscriptionState, int $daysLeft): ?array
    {
        return match (true) {
            $verification === VerificationStatus::Suspended => [
                'tone' => 'danger', 'icon' => 'alert-circle',
                'title' => __('supplier.banner_suspended_title'), 'body' => $verification->hint(),
                'action' => __('supplier.banner_view_account'), 'url' => route('supplier.account'),
            ],
            $verification === VerificationStatus::Rejected => [
                'tone' => 'danger', 'icon' => 'alert-circle',
                'title' => __('supplier.banner_rejected_title'), 'body' => $verification->hint(),
                'action' => __('supplier.banner_view_documents'), 'url' => route('supplier.account', ['tab' => 'documents']),
            ],
            $verification === VerificationStatus::Pending => [
                'tone' => 'neutral', 'icon' => 'clock',
                'title' => __('supplier.banner_review_title'), 'body' => __('supplier.banner_review_body'),
                'action' => __('supplier.banner_view_documents'), 'url' => route('supplier.account', ['tab' => 'documents']),
            ],
            $subscriptionState === 'none' => [
                'tone' => 'danger', 'icon' => 'alert-circle',
                'title' => __('supplier.banner_expired_title'), 'body' => __('supplier.banner_expired_body'),
                'action' => __('supplier.banner_renew'), 'url' => route('supplier.subscription'),
            ],
            $subscriptionState === 'expiring' => [
                'tone' => 'warning', 'icon' => 'alert-circle',
                'title' => trans_choice('supplier.banner_expiring_title', $daysLeft, ['days' => $daysLeft]),
                'body' => __('supplier.banner_expiring_body'),
                'action' => __('supplier.banner_renew'), 'url' => route('supplier.subscription'),
            ],
            default => null,
        };
    }
}
