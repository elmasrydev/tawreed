<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Rfq;
use App\Models\User;
use App\Support\QuoteEligibility;
use App\ViewModels\AnonymousRfq;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FeedController extends Controller
{
    /**
     * The anonymised request feed, limited to requests in the supplier's
     * categories and coverage. Buyer identity is stripped by the view model
     * before anything reaches a template.
     */
    public function index(Request $request): View
    {
        $supplier = $request->user();

        $rfqs = Rfq::query()
            ->browsable()
            ->matchingSupplier($supplier->supplierProfile)
            ->with([...AnonymousRfq::RELATIONS, 'quotes' => $this->ownQuotesOnly($supplier)])
            ->when($request->integer('category'), fn ($query, int $id) => $query->where('category_id', $id))
            ->when($request->integer('governorate'), fn ($query, int $id) => $query->where('governorate_id', $id))
            ->when($request->string('q')->trim()->toString(), fn ($query, string $term) => $query->search($term))
            ->orderBy('quote_deadline')
            ->paginate(12)
            ->withQueryString();

        return view('supplier.feed', [
            'rfqs' => $rfqs,
            'items' => $rfqs->map(fn (Rfq $rfq): AnonymousRfq => AnonymousRfq::make($rfq, $supplier)),
            'categories' => Category::query()->main()->get(),
            'categoryCounts' => Rfq::query()->browsable()->matchingSupplier($supplier->supplierProfile)
                ->selectRaw('category_id, count(*) as total')
                ->groupBy('category_id')
                ->pluck('total', 'category_id'),
            'governorates' => Governorate::query()->orderBy('sort')->get(),
            'quoteGate' => QuoteEligibility::explain($supplier),
        ]);
    }

    public function show(Request $request, Rfq $rfq): View
    {
        Gate::authorize('browse', $rfq);

        $supplier = $request->user();

        $rfq->load([...AnonymousRfq::RELATIONS, 'quotes' => $this->ownQuotesOnly($supplier), 'media']);

        $item = AnonymousRfq::make($rfq, $supplier);
        $canQuote = Gate::allows('quote', $rfq);

        return view('supplier.rfq', [
            'item' => $item,
            'rfq' => $rfq,
            'attachments' => $rfq->getMedia('attachments'),
            'canQuote' => $canQuote,
            'gate' => $canQuote || $item->hasOwnQuote() ? null : $this->gateFor($supplier, $rfq, $item),
        ]);
    }

    /**
     * Streams one of the request's attachments to a supplier allowed to see
     * the request. Files live on a private disk, so this is the only way in.
     */
    public function attachment(Rfq $rfq, Media $media): Media
    {
        Gate::authorize('browse', $rfq);

        abort_unless(
            $media->model_type === $rfq->getMorphClass()
                && (int) $media->model_id === $rfq->id
                && $media->collection_name === 'attachments',
            404,
        );

        return $media;
    }

    /**
     * Rival quotes are never loaded into the supplier's views.
     */
    private function ownQuotesOnly(User $supplier): \Closure
    {
        return fn (HasMany $query) => $query->where('supplier_id', $supplier->id);
    }

    /**
     * The dark bar explaining why quoting is locked on this request, with the
     * one place that fixes it.
     *
     * @return array{message: string, action: ?string, url: ?string}
     */
    private function gateFor(User $supplier, Rfq $rfq, AnonymousRfq $item): array
    {
        if (! $item->acceptsQuotes) {
            return ['message' => __('quote.closed'), 'action' => __('supplier.browse_open_rfqs'), 'url' => route('supplier.feed')];
        }

        if ($explanation = QuoteEligibility::explain($supplier)) {
            return $explanation;
        }

        if (! $rfq->matchesSupplier($supplier->supplierProfile)) {
            return [
                'message' => __('supplier.gate_out_of_scope'),
                'action' => __('supplier.gate_out_of_scope_action'),
                'url' => route('supplier.account', ['tab' => 'coverage']),
            ];
        }

        return ['message' => __('quote.closed'), 'action' => null, 'url' => null];
    }
}
