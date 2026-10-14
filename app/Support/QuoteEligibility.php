<?php

namespace App\Support;

use App\Enums\VerificationStatus;
use App\Models\User;

/**
 * Whether a supplier's account may send offers at all, and if not, the one
 * friendly explanation and next step shown wherever quoting is locked.
 */
class QuoteEligibility
{
    /**
     * The first thing standing between the supplier and quoting, in the order
     * they should fix it, or null when they can quote.
     *
     * @return 'email'|'review'|'rejected'|'suspended'|'subscription'|null
     */
    public static function blocker(User $supplier): ?string
    {
        $verification = $supplier->supplierProfile?->verification_status ?? VerificationStatus::Pending;

        return match (true) {
            ! $supplier->hasVerifiedEmail() => 'email',
            $verification === VerificationStatus::Pending => 'review',
            $verification === VerificationStatus::Rejected => 'rejected',
            $verification === VerificationStatus::Suspended => 'suspended',
            ! $supplier->hasActiveSubscription() => 'subscription',
            default => null,
        };
    }

    public static function canQuote(User $supplier): bool
    {
        return $supplier->isSupplier() && self::blocker($supplier) === null;
    }

    /**
     * @return array{message: string, action: string, url: string}|null
     */
    public static function explain(User $supplier): ?array
    {
        return match (self::blocker($supplier)) {
            'email' => [
                'message' => __('supplier.gate_email'),
                'action' => __('supplier.gate_email_action'),
                'url' => route('email.verify'),
            ],
            'review' => [
                'message' => __('supplier.gate_review'),
                'action' => __('supplier.gate_verification_action'),
                'url' => route('supplier.account', ['tab' => 'documents']),
            ],
            'rejected' => [
                'message' => __('supplier.gate_rejected'),
                'action' => __('supplier.banner_view_documents'),
                'url' => route('supplier.account', ['tab' => 'documents']),
            ],
            'suspended' => [
                'message' => __('supplier.gate_suspended'),
                'action' => __('supplier.banner_view_account'),
                'url' => route('supplier.account'),
            ],
            'subscription' => [
                'message' => __('supplier.gate_subscription'),
                'action' => __('subscription.view_plans'),
                'url' => route('supplier.subscription'),
            ],
            default => null,
        };
    }
}
