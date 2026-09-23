<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\ViewModels\AnonymousRfq;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuoteController extends Controller
{
    /**
     * Tabs shown on My Quotes, in order. Expired and withdrawn only appear
     * when the supplier actually has such quotes.
     *
     * @var list<string>
     */
    private const TABS = ['all', 'pending', 'shortlisted', 'selected', 'rejected', 'expired', 'withdrawn'];

    public function index(Request $request): View
    {
        $supplier = $request->user();

        $counts = $supplier->quotes()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $tab = in_array($request->query('status'), self::TABS, true) ? $request->query('status') : 'all';

        $quotes = $supplier->quotes()
            ->whereHas('rfq')
            ->when($tab !== 'all', fn (Builder $query) => $query->where('status', $tab))
            ->with([
                ...collect(AnonymousRfq::RELATIONS)->map(fn (string $relation): string => "rfq.{$relation}")->all(),
                'rejectionReason',
                'conversation',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $tabs = collect(self::TABS)
            ->filter(fn (string $key): bool => ! in_array($key, ['expired', 'withdrawn'], true) || ($counts[$key] ?? 0) > 0 || $tab === $key)
            ->map(fn (string $key): array => [
                'key' => $key,
                'label' => $key === 'all' ? __('common.all') : __("supplier.quote_tab_{$key}"),
                'count' => $key === 'all' ? (int) $counts->sum() : (int) ($counts[$key] ?? 0),
                'url' => route('supplier.quotes.index', $key === 'all' ? [] : ['status' => $key]),
            ])
            ->values()
            ->all();

        return view('supplier.quotes', [
            'quotes' => $quotes,
            'rows' => $quotes->map(fn (Quote $quote): array => [
                'quote' => $quote,
                'rfq' => AnonymousRfq::make($quote->rfq),
                'rfqIsOpen' => $quote->rfq->isOpen(),
            ]),
            'tabs' => $tabs,
            'tab' => $tab,
            'hasSubscription' => $supplier->hasActiveSubscription(),
            'hasAnyQuotes' => $counts->sum() > 0,
        ]);
    }
}
