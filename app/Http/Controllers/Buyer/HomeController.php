<?php

namespace App\Http\Controllers\Buyer;

use App\Enums\QuoteStatus;
use App\Enums\RfqStatus;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Rfq;
use App\Models\User;
use App\Support\ShellState;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The buyer greeting is phrased in Egypt local time; stored times stay UTC.
     */
    private const LOCAL_TIMEZONE = 'Africa/Cairo';

    /**
     * How many rows the "Needs attention" column shows at most.
     */
    private const ATTENTION_LIMIT = 4;

    public function __invoke(Request $request): View
    {
        $buyer = $request->user();
        $localNow = now()->setTimezone(self::LOCAL_TIMEZONE);

        $openRfqs = $buyer->rfqs()
            ->where('status', RfqStatus::Open)
            ->with(['unit', 'quotes'])
            ->orderBy('quote_deadline')
            ->get();

        $attention = $this->attentionItems($openRfqs);

        return view('buyer.home', [
            'firstName' => str($buyer->name)->before(' ')->toString(),
            'greetingKey' => match (true) {
                $localNow->hour < 12 => 'buyer.greeting_morning',
                $localNow->hour < 17 => 'buyer.greeting_afternoon',
                default => 'buyer.greeting_evening',
            },
            'today' => $localNow->translatedFormat('l j F Y'),
            'kpis' => $this->kpis($buyer, $openRfqs),
            'recentQuotes' => Quote::query()
                ->whereIn('rfq_id', $buyer->rfqs()->select('id'))
                ->with(['rfq.unit', 'supplier.supplierProfile', 'rejectionReason'])
                ->latest()
                ->take(5)
                ->get(),
            'attention' => $attention->take(self::ATTENTION_LIMIT),
            'attentionCount' => $attention->count(),
        ]);
    }

    /**
     * @param  Collection<int, Rfq>  $openRfqs
     * @return array{open: int, closingSoon: int, pending: int, pendingSinceYesterday: int, unread: int, awardedThisMonth: int, awardedValue: float}
     */
    private function kpis(User $buyer, Collection $openRfqs): array
    {
        $pendingQuotes = Quote::query()
            ->whereIn('rfq_id', $buyer->rfqs()->select('id'))
            ->where('status', QuoteStatus::Pending);

        $awardedThisMonth = $buyer->rfqs()
            ->where('status', RfqStatus::Awarded)
            ->where('awarded_at', '>=', now()->startOfMonth())
            ->with('awardedQuote')
            ->get();

        return [
            'open' => $openRfqs->count(),
            'closingSoon' => $openRfqs->filter(fn (Rfq $rfq): bool => $rfq->isEndingSoon())->count(),
            'pending' => (clone $pendingQuotes)->count(),
            'pendingSinceYesterday' => (clone $pendingQuotes)->where('created_at', '>=', now()->subDay())->count(),
            'unread' => ShellState::unreadMessages($buyer),
            'awardedThisMonth' => $awardedThisMonth->count(),
            'awardedValue' => (float) $awardedThisMonth->sum(fn (Rfq $rfq): float => $rfq->awardedQuote?->grandTotal() ?? 0.0),
        ];
    }

    /**
     * Open requests that want the buyer's action: those closing within the
     * ending-soon window, then those still waiting for a first quote.
     *
     * @param  Collection<int, Rfq>  $openRfqs
     * @return Collection<int, array{rfq: Rfq, reason: string, quotesCount: int, lowestTotal: ?float}>
     */
    private function attentionItems(Collection $openRfqs): Collection
    {
        $live = $openRfqs->filter(fn (Rfq $rfq): bool => $rfq->acceptsQuotes());

        $closingSoon = $live->filter(fn (Rfq $rfq): bool => $rfq->isEndingSoon());
        $withoutQuotes = $live
            ->reject(fn (Rfq $rfq): bool => $rfq->isEndingSoon())
            ->filter(fn (Rfq $rfq): bool => $rfq->quotes->isEmpty());

        return $closingSoon->map(fn (Rfq $rfq): array => [
            'rfq' => $rfq,
            'reason' => 'closing',
            'quotesCount' => $rfq->quotes->count(),
            'lowestTotal' => $rfq->quotes
                ->reject(fn (Quote $quote): bool => $quote->isRejected())
                ->map(fn (Quote $quote): float => $quote->grandTotal())
                ->min(),
        ])->concat($withoutQuotes->map(fn (Rfq $rfq): array => [
            'rfq' => $rfq,
            'reason' => 'no_quotes',
            'quotesCount' => 0,
            'lowestTotal' => null,
        ]))->values();
    }
}
