<?php

namespace App\Http\Controllers\Buyer;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuoteController extends Controller
{
    /**
     * Inbox tabs and the quote statuses each one lists.
     *
     * @var array<string, array<int, QuoteStatus>>
     */
    private const TABS = [
        'pending' => [QuoteStatus::Pending],
        'shortlisted' => [QuoteStatus::Shortlisted],
        'selected' => [QuoteStatus::Selected],
        'rejected' => [QuoteStatus::Rejected],
    ];

    /**
     * The quotes inbox: every quote received across the buyer's requests.
     */
    public function index(Request $request): View
    {
        $base = Quote::query()->whereIn('rfq_id', $request->user()->rfqs()->select('id'));

        $current = array_key_exists((string) $request->query('status'), self::TABS)
            ? (string) $request->query('status')
            : 'all';

        $tabs = [[
            'key' => 'all',
            'label' => __('common.all'),
            'count' => (clone $base)->count(),
            'url' => route('buyer.quotes.index'),
        ]];

        foreach (self::TABS as $key => $statuses) {
            $tabs[] = [
                'key' => $key,
                'label' => __("buyer.quotes_tab_{$key}"),
                'count' => (clone $base)->whereIn('status', $statuses)->count(),
                'url' => route('buyer.quotes.index', ['status' => $key]),
            ];
        }

        return view('buyer.quotes', [
            'tabs' => $tabs,
            'currentTab' => $current,
            'quotes' => (clone $base)
                ->with(['rfq.unit', 'supplier.supplierProfile', 'rejectionReason'])
                ->when($current !== 'all', fn ($query) => $query->whereIn('status', self::TABS[$current]))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }
}
