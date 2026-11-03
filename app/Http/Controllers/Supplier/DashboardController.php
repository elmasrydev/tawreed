<?php

namespace App\Http\Controllers\Supplier;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Rfq;
use App\Models\User;
use App\Support\ShellState;
use App\ViewModels\AnonymousRfq;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $supplier = $request->user();
        $profile = $supplier->supplierProfile->load(['categories', 'governorates']);

        $matching = Rfq::query()->browsable()->matchingSupplier($profile);

        $newRfqs = (clone $matching)
            ->with([...AnonymousRfq::RELATIONS, 'quotes' => fn (HasMany $query) => $query->where('supplier_id', $supplier->id)])
            ->latest('published_at')
            ->latest('id')
            ->take(3)
            ->get()
            ->map(fn (Rfq $rfq): AnonymousRfq => AnonymousRfq::make($rfq, $supplier));

        $recentQuotes = $supplier->quotes()
            ->whereHas('rfq')
            ->with(collect(AnonymousRfq::RELATIONS)->map(fn (string $relation): string => "rfq.{$relation}")->all())
            ->with('rejectionReason')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Quote $quote): array => ['quote' => $quote, 'rfq' => AnonymousRfq::make($quote->rfq)]);

        return view('supplier.dashboard', [
            'supplier' => $supplier,
            'profile' => $profile,
            'account' => ShellState::supplierAccount($supplier),
            'matchingCount' => (clone $matching)->count(),
            'matchingToday' => (clone $matching)->where('published_at', '>=', today())->count(),
            'stats' => $this->quoteStats($supplier),
            'newRfqs' => $newRfqs,
            'recentQuotes' => $recentQuotes,
        ]);
    }

    /**
     * Quote figures for the KPI row. The win rate counts only quotes a buyer
     * has already decided on (selected or turned down), so pending quotes do
     * not drag it down.
     *
     * @return array{submittedThisMonth: int, active: int, selected: int, wonValue: float, decided: int, winRate: ?int}
     */
    private function quoteStats(User $supplier): array
    {
        $selected = $supplier->quotes()->selected()->get(['id', 'total_price', 'vat_mode', 'vat_amount', 'delivery_cost']);
        $rejected = $supplier->quotes()->where('status', QuoteStatus::Rejected)->count();
        $decided = $selected->count() + $rejected;

        return [
            'submittedThisMonth' => $supplier->quotes()->where('created_at', '>=', now()->startOfMonth())->count(),
            'active' => $supplier->quotes()->whereIn('status', [QuoteStatus::Pending, QuoteStatus::Shortlisted])->count(),
            'selected' => $selected->count(),
            'wonValue' => $selected->sum(fn (Quote $quote): float => $quote->grandTotal()),
            'decided' => $decided,
            'winRate' => $decided > 0 ? (int) round($selected->count() / $decided * 100) : null,
        ];
    }
}
