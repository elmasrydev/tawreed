<?php

namespace App\Http\Controllers\Buyer;

use App\Enums\RfqStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * The buyer's order history: every request they awarded to a supplier,
     * newest first.
     */
    public function index(Request $request): View
    {
        $orders = $request->user()->rfqs()
            ->where('status', RfqStatus::Awarded)
            ->whereNotNull('awarded_quote_id');

        return view('buyer.orders', [
            'ordersCount' => (clone $orders)->count(),
            'orders' => $orders
                ->with(['unit', 'category', 'subcategory', 'awardedQuote.supplier.supplierProfile', 'conversation'])
                ->latest('awarded_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }
}
