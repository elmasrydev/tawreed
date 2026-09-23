<?php

namespace App\Http\Controllers\Supplier;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Rfq;
use App\Models\User;
use App\ViewModels\AnonymousRfq;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FeedController extends Controller
{
    /**
     * The anonymised request feed. Buyer identity is stripped by the view model
     * before anything reaches a template.
     */
    public function index(Request $request): View
    {
        $supplier = $request->user();

        $rfqs = Rfq::query()
            ->browsable()
            ->with([...AnonymousRfq::RELATIONS, 'quotes' => $this->ownQuotesOnly($supplier)])
            ->when($request->integer('category'), fn ($query, int $id) => $query->where('category_id', $id))
            ->when($request->integer('governorate'), fn ($query, int $id) => $query->where('governorate_id', $id))
            ->when($request->string('q')->trim()->toString(), fn ($query, string $term) => $query->where(
                fn ($inner) => $inner->where('title', 'like', "%{$term}%")->orWhere('specs', 'like', "%{$term}%")
            ))
            ->orderBy('quote_deadline')
            ->paginate(12)
            ->withQueryString();

        return view('supplier.feed', [
            'rfqs' => $rfqs,
            'items' => $rfqs->map(fn (Rfq $rfq): AnonymousRfq => AnonymousRfq::make($rfq, $supplier)),
            'categories' => Category::query()->main()->get(),
            'categoryCounts' => Rfq::query()->browsable()
                ->selectRaw('category_id, count(*) as total')
                ->groupBy('category_id')
                ->pluck('total', 'category_id'),
            'governorates' => Governorate::query()->orderBy('sort')->get(),
            'quoteBlocker' => $this->quoteBlocker($supplier),
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
            'gate' => $canQuote || $item->hasOwnQuote() ? null : $this->gateFor($supplier, $item),
            'hasSubscription' => $supplier->hasActiveSubscription(),
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
     * Why this supplier's account cannot quote at all, if it cannot.
     *
     * @return 'phone'|'verification'|null
     */
    private function quoteBlocker(User $supplier): ?string
    {
        return match (true) {
            ! $supplier->hasVerifiedPhone() => 'phone',
            ! ($supplier->supplierProfile?->canSubmitQuotes() ?? false) => 'verification',
            default => null,
        };
    }

    /**
     * The dark bar explaining why quoting is locked on this request, with the
     * one place that fixes it.
     *
     * @return array{message: string, action: ?string, url: ?string}
     */
    private function gateFor(User $supplier, AnonymousRfq $item): array
    {
        if (! $item->acceptsQuotes) {
            return ['message' => __('quote.closed'), 'action' => __('supplier.browse_open_rfqs'), 'url' => route('supplier.feed')];
        }

        $verification = $supplier->supplierProfile?->verification_status ?? VerificationStatus::Pending;

        return match ($this->quoteBlocker($supplier)) {
            'phone' => ['message' => __('supplier.gate_phone'), 'action' => __('supplier.gate_phone_action'), 'url' => route('phone.verify')],
            'verification' => [
                'message' => $verification === VerificationStatus::Pending ? __('supplier.gate_review') : $verification->hint(),
                'action' => __('supplier.gate_verification_action'),
                'url' => route('supplier.account', ['tab' => 'documents']),
            ],
            default => ['message' => __('quote.closed'), 'action' => null, 'url' => null],
        };
    }
}
