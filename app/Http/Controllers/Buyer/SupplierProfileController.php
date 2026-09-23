<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\SupplierProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierProfileController extends Controller
{
    /**
     * How many reviews the profile lists.
     */
    private const REVIEW_LIMIT = 10;

    /**
     * A supplier's public, read-only profile as buyers see it. Only verified
     * suppliers have one, and contact details are never part of it: they are
     * shared only after the buyer selects one of the supplier's quotes.
     */
    public function __invoke(Request $request, SupplierProfile $supplierProfile): View
    {
        abort_unless($supplierProfile->isVerified() && ! $supplierProfile->user->isSuspended(), 404);

        $supplierProfile->load(['governorates', 'categories', 'media']);

        return view('buyer.supplier-profile', [
            'profile' => $supplierProfile,
            'reviews' => $supplierProfile->user->receivedReviews()
                ->with('buyer.buyerProfile.businessType', 'buyer.buyerProfile.governorate')
                ->latest()
                ->take(self::REVIEW_LIMIT)
                ->get(),
            'conversation' => Conversation::query()
                ->where('buyer_id', $request->user()->id)
                ->where('supplier_id', $supplierProfile->user_id)
                ->latest('last_message_at')
                ->first(),
        ]);
    }
}
