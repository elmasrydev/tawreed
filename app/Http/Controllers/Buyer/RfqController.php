<?php

namespace App\Http\Controllers\Buyer;

use App\Enums\RfqStatus;
use App\Http\Controllers\Controller;
use App\Models\Rfq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RfqController extends Controller
{
    /**
     * Tabs shown on My RFQs even when they are empty.
     *
     * @var array<int, string>
     */
    private const ALWAYS_VISIBLE_TABS = ['all', 'open'];

    public function index(Request $request): View
    {
        $buyer = $request->user();

        /** @var array<string, int> $counts */
        $counts = $buyer->rfqs()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();

        $status = RfqStatus::tryFrom((string) $request->query('status'));

        $tabs = collect([null, ...RfqStatus::cases()])
            ->map(fn (?RfqStatus $case): array => [
                'key' => $case->value ?? 'all',
                'label' => $case?->label() ?? __('common.all'),
                'count' => $case ? ($counts[$case->value] ?? 0) : array_sum($counts),
                'url' => route('buyer.rfqs.index', $case ? ['status' => $case->value] : []),
            ])
            ->filter(fn (array $tab): bool => $tab['count'] > 0
                || in_array($tab['key'], self::ALWAYS_VISIBLE_TABS, true)
                || $tab['key'] === $status?->value)
            ->values()
            ->all();

        return view('buyer.rfqs.index', [
            'tabs' => $tabs,
            'currentTab' => $status->value ?? 'all',
            'rfqs' => $buyer->rfqs()
                ->with(['unit', 'category', 'subcategory'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderByDesc(DB::raw('coalesce(published_at, updated_at)'))
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function show(Request $request, Rfq $rfq): View
    {
        Gate::authorize('view', $rfq);

        $rfq->load(['unit', 'governorate', 'category', 'subcategory', 'media', 'awardedQuote.supplier.supplierProfile', 'conversation']);

        $liveQuotes = $rfq->quotes()->get()->reject->isRejected();

        return view('buyer.rfqs.show', [
            'rfq' => $rfq,
            'lowestTotal' => $liveQuotes->map->grandTotal()->min(),
            'shortlistedCount' => $liveQuotes->filter->isShortlisted()->count(),
            'anonymousLabel' => $request->user()->buyerProfile?->anonymousLabel() ?? '',
        ]);
    }

    /**
     * Discards a draft the buyer no longer needs. Published requests are never
     * deleted from here; suppliers may already have quoted on them.
     */
    public function destroy(Rfq $rfq): RedirectResponse
    {
        Gate::authorize('deleteDraft', $rfq);

        $rfq->clearMediaCollection('attachments');
        $rfq->delete();

        return redirect()
            ->route('buyer.rfqs.index', ['status' => RfqStatus::Draft->value])
            ->with('status', __('buyer.draft_deleted'));
    }
}
