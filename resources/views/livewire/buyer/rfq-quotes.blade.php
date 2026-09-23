<?php

use App\Actions\Quote\RejectQuote;
use App\Actions\Quote\SelectQuote;
use App\Actions\Quote\ShortlistQuote;
use App\Models\Quote;
use App\Models\RejectionReason;
use App\Models\Rfq;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Quote comparison for one request. The buyer shortlists, rejects with a
 * reason, or selects — which awards the request and opens the chat.
 */
new #[Layout('layouts::buyer', ['current' => 'requests'])] class extends Component
{
    public Rfq $rfq;

    /** The quote awaiting a rejection reason, if the buyer has opened that prompt. */
    public ?int $rejectingQuoteId = null;

    public bool $showRejectModal = false;

    /** The quote the buyer is about to award, while the confirmation is open. */
    public ?int $selectingQuoteId = null;

    public bool $showAwardModal = false;

    public function mount(Rfq $rfq): void
    {
        Gate::authorize('view', $rfq);

        if ($rfq->isDraft()) {
            $this->redirectRoute('buyer.rfqs.edit', $rfq, navigate: true);
        }

        $this->rfq = $rfq;
    }

    #[Computed]
    public function quotes(): Collection
    {
        return $this->rfq->quotes()
            ->with(['supplier.supplierProfile', 'rejectionReason', 'conversation'])
            ->orderByRaw('FIELD(status, ?, ?, ?, ?, ?, ?) ', ['selected', 'shortlisted', 'pending', 'rejected', 'withdrawn', 'expired'])
            ->orderBy('total_price')
            ->get()
            ->each(fn (Quote $quote) => $quote->setRelation('rfq', $this->rfq));
    }

    /**
     * Quotes still in play: the ones "lowest" and "fastest" are measured against.
     */
    #[Computed]
    public function liveQuotes(): Collection
    {
        return $this->quotes->filter(fn (Quote $quote): bool => $quote->isActionable() || $quote->isSelected());
    }

    /**
     * The cheapest grand total across quotes still in play. Only meaningful
     * when there is something to compare against.
     */
    #[Computed]
    public function bestTotal(): ?float
    {
        return $this->liveQuotes->count() > 1
            ? $this->liveQuotes->map(fn (Quote $quote): float => $quote->grandTotal())->min()
            : null;
    }

    #[Computed]
    public function fastestDelivery(): ?string
    {
        return $this->liveQuotes->count() > 1
            ? $this->liveQuotes->min(fn (Quote $quote): string => $quote->expected_delivery_date->toDateString())
            : null;
    }

    #[Computed]
    public function reasons(): Collection
    {
        return RejectionReason::query()->orderBy('sort')->get();
    }

    #[Computed]
    public function selectingQuote(): ?Quote
    {
        return $this->quotes->firstWhere('id', $this->selectingQuoteId);
    }

    #[Computed]
    public function rejectingQuote(): ?Quote
    {
        return $this->quotes->firstWhere('id', $this->rejectingQuoteId);
    }

    public function isLowest(Quote $quote): bool
    {
        return $this->bestTotal !== null
            && ! $quote->isRejected()
            && abs($quote->grandTotal() - $this->bestTotal) < 0.01;
    }

    public function isFastest(Quote $quote): bool
    {
        return $this->fastestDelivery !== null
            && ! $quote->isRejected()
            && $quote->expected_delivery_date->toDateString() === $this->fastestDelivery;
    }

    public function shortlist(int $quoteId, ShortlistQuote $shortlistQuote): void
    {
        $quote = $this->findQuote($quoteId);
        Gate::authorize('decide', $quote);

        $shortlistQuote->handle($quote);

        $this->forgetQuotes();
    }

    public function startSelect(int $quoteId): void
    {
        Gate::authorize('decide', $this->findQuote($quoteId));

        $this->selectingQuoteId = $quoteId;
        $this->showAwardModal = true;
    }

    public function cancelSelect(): void
    {
        $this->selectingQuoteId = null;
        $this->showAwardModal = false;
    }

    public function startReject(int $quoteId): void
    {
        Gate::authorize('decide', $this->findQuote($quoteId));

        $this->rejectingQuoteId = $quoteId;
        $this->showRejectModal = true;
    }

    public function cancelReject(): void
    {
        $this->rejectingQuoteId = null;
        $this->showRejectModal = false;
    }

    public function reject(int $reasonId, RejectQuote $rejectQuote): void
    {
        $quote = $this->findQuote($this->rejectingQuoteId);
        Gate::authorize('decide', $quote);

        $rejectQuote->handle($quote, RejectionReason::findOrFail($reasonId));

        $this->cancelReject();
        $this->forgetQuotes();

        $this->dispatch('toast', message: __('buyer.quote_rejected_toast', [
            'name' => $quote->supplier->supplierProfile?->company_name,
        ]));
    }

    public function select(int $quoteId, SelectQuote $selectQuote): void
    {
        $quote = $this->findQuote($quoteId);
        Gate::authorize('decide', $quote);

        $selectQuote->handle($quote);

        session()->flash('status', __('rfq.quote_selected'));

        $this->redirectRoute('buyer.rfqs.show', $this->rfq, navigate: true);
    }

    private function findQuote(?int $quoteId): Quote
    {
        return $this->rfq->quotes()->whereKey($quoteId)->firstOrFail();
    }

    private function forgetQuotes(): void
    {
        unset($this->quotes, $this->liveQuotes, $this->bestTotal, $this->fastestDelivery, $this->selectingQuote, $this->rejectingQuote);
    }
};
?>

<div class="shell-page" x-data="{ view: 'table' }">
    @php $buyerProfile = auth()->user()->buyerProfile; @endphp

    <x-page-header :title="__('rfq.received_quotes')" :back="route('buyer.rfqs.show', $rfq)" :back-label="__('buyer.back_to_request')">
        <x-slot:meta>
            <span class="font-medium text-gray-900">{{ $rfq->title }}</span>
            <x-status-badge :status="$rfq->status" size="sm" />
            <span dir="ltr" class="tabular">{{ $rfq->reference }}</span>
            @if ($rfq->formattedQuantity())
                <span aria-hidden="true" class="text-gray-300">·</span>
                <span class="tabular"><span dir="ltr">{{ $rfq->formattedQuantity() }}</span> {{ $rfq->unit?->name }}</span>
            @endif
            <x-buyer.rfq-deadline :rfq="$rfq" />
        </x-slot:meta>
    </x-page-header>

    <x-alert tone="info" icon="lock">
        {{ __('rfq.anon_banner', ['label' => $buyerProfile?->anonymousLabel()]) }}
    </x-alert>

    @if ($this->quotes->isEmpty())
        <x-empty-state icon="tag" :title="__('rfq.no_quotes_yet')" :body="__('rfq.no_quotes_yet_body')">
            <a href="{{ route('buyer.rfqs.show', $rfq) }}" wire:navigate class="btn btn-secondary">{{ __('buyer.view_request') }}</a>
        </x-empty-state>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="card-title text-base">
                <x-lucide name="tag" :size="18" class="text-brand-500" />
                {{ trans_choice('rfq.quotes_count', $this->quotes->count(), ['count' => $this->quotes->count()]) }}
            </h2>

            <div class="hidden h-8 overflow-hidden rounded-lg border border-gray-300 text-[13px] font-semibold lg:inline-flex" role="group"
                 aria-label="{{ __('buyer.view_mode') }}">
                <button type="button" x-on:click="view = 'table'" :aria-pressed="view === 'table'"
                        :class="view === 'table' ? 'bg-brand-700 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                        class="flex items-center gap-1.5 px-3">
                    <x-lucide name="menu" :size="14" />
                    {{ __('buyer.view_table') }}
                </button>
                <button type="button" x-on:click="view = 'cards'" :aria-pressed="view === 'cards'"
                        :class="view === 'cards' ? 'bg-brand-700 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                        class="flex items-center gap-1.5 border-s border-gray-300 px-3">
                    <x-lucide name="layout-grid" :size="14" />
                    {{ __('buyer.view_cards') }}
                </button>
            </div>
        </div>

        {{-- Table: desktop only, the default view. --}}
        @php $columns = 'lg:grid-cols-[minmax(0,2.5fr)_minmax(0,0.9fr)_minmax(0,1.1fr)_minmax(0,0.9fr)_minmax(0,0.7fr)_minmax(0,1.1fr)_minmax(0,0.8fr)_172px]'; @endphp
        <div x-show="view === 'table'" class="card hidden overflow-hidden lg:block">
            <div class="table-head md:hidden lg:grid lg:gap-3 {{ $columns }}">
                <span>{{ __('buyer.col_supplier') }}</span>
                <span class="text-end">{{ __('buyer.col_unit_price') }}</span>
                <span class="text-end">{{ __('buyer.col_total') }}</span>
                <span>{{ __('buyer.col_delivery') }}</span>
                <span>{{ __('buyer.col_valid') }}</span>
                <span>{{ __('buyer.col_payment') }}</span>
                <span>{{ __('buyer.col_sample') }}</span>
                <span></span>
            </div>

            @foreach ($this->quotes as $quote)
                @php $profile = $quote->supplier->supplierProfile; @endphp
                <div wire:key="row-{{ $quote->id }}" @class([
                    'data-row text-[13px] lg:gap-3', $columns,
                    'bg-brand-100/60 hover:bg-brand-100' => $quote->isSelected(),
                    'opacity-55' => ! $quote->isActionable() && ! $quote->isSelected(),
                ])>
                    <div class="flex min-w-0 items-center gap-2.5">
                        <x-avatar :name="$profile?->company_name" :src="$profile?->getFirstMediaUrl('logo') ?: null" :size="32" />
                        <div class="min-w-0">
                            <x-buyer.supplier-link :profile="$profile" :seal="14" class="font-semibold text-gray-900" />
                            <p class="flex items-center gap-1 text-[11px] text-gray-500 tabular">
                                <x-lucide name="star" :size="11" class="text-accent-500" />
                                {{ number_format((float) ($profile?->rating_avg ?? 0), 1) }}
                                · {{ trans_choice('buyer.reviews_count', $profile?->reviews_count ?? 0, ['count' => $profile?->reviews_count ?? 0]) }}
                            </p>
                        </div>
                    </div>
                    <div class="text-end">
                        <x-buyer.money :amount="$quote->unit_price" class="font-bold text-brand-700" />
                    </div>
                    <div class="text-end">
                        <x-buyer.money :amount="$quote->grandTotal()" class="font-semibold text-gray-900" />
                        @if ($this->isLowest($quote))
                            <p class="text-[10px] font-bold tracking-[0.4px] text-success-700 uppercase">{{ __('buyer.tag_lowest') }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="tabular">{{ $quote->expected_delivery_date->translatedFormat('j M') }}</p>
                        @if ($this->isFastest($quote))
                            <p class="text-[10px] font-bold tracking-[0.4px] text-brand-500 uppercase">{{ __('buyer.tag_fastest') }}</p>
                        @endif
                    </div>
                    <span class="text-gray-700 tabular">{{ trans_choice('buyer.days_count', $quote->validity_days, ['count' => $quote->validity_days]) }}</span>
                    <span class="line-clamp-2 text-xs text-gray-700">{{ $quote->payment_terms ?: '—' }}</span>
                    <span class="line-clamp-2 text-xs text-gray-700">{{ $quote->sample_availability ?: '—' }}</span>
                    <div class="flex items-center justify-end gap-1">
                        @can('decide', $quote)
                            <button type="button" wire:click="startSelect({{ $quote->id }})" class="btn btn-primary btn-sm">
                                {{ __('buyer.select') }}
                            </button>
                            <button type="button" wire:click="shortlist({{ $quote->id }})"
                                    title="{{ $quote->isShortlisted() ? __('rfq.shortlisted') : __('rfq.shortlist') }}"
                                    aria-label="{{ $quote->isShortlisted() ? __('rfq.shortlisted') : __('rfq.shortlist') }}"
                                    aria-pressed="{{ $quote->isShortlisted() ? 'true' : 'false' }}"
                                    @class([
                                        'btn btn-icon btn-sm border',
                                        'border-warning-200 bg-warning-100 text-warning-800' => $quote->isShortlisted(),
                                        'border-gray-300 bg-white text-gray-400 hover:text-warning-600' => ! $quote->isShortlisted(),
                                    ])>
                                <x-lucide name="star" :size="14" />
                            </button>
                            <button type="button" wire:click="startReject({{ $quote->id }})"
                                    title="{{ __('rfq.reject') }}" aria-label="{{ __('rfq.reject') }}"
                                    class="btn btn-icon btn-sm border border-gray-300 bg-white text-danger-600 hover:bg-danger-50">
                                <x-lucide name="x" :size="14" />
                            </button>
                        @else
                            <x-status-badge :status="$quote->status" :quote="$quote" size="sm" />
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Cards: always on phones and tablets, optional on desktop. --}}
        <div :class="view === 'cards' ? 'lg:grid' : 'lg:hidden'" class="grid gap-4 pt-1 md:grid-cols-2">
            @foreach ($this->quotes as $quote)
                @php
                    $profile = $quote->supplier->supplierProfile;
                    $isLowest = $this->isLowest($quote);
                    $isFastest = $this->isFastest($quote);
                @endphp
                <article wire:key="card-{{ $quote->id }}" @class([
                    'relative flex min-w-0 flex-col gap-3 rounded-card border-2 bg-white p-4',
                    'border-success-500' => $quote->isSelected(),
                    'border-warning-400' => $quote->isShortlisted(),
                    'border-gray-200' => ! $quote->isSelected() && ! $quote->isShortlisted(),
                    'opacity-60' => ! $quote->isActionable() && ! $quote->isSelected(),
                ])>
                    @if ($isLowest || $isFastest)
                        <div class="absolute -top-2.5 start-3.5 flex gap-1.5">
                            @if ($isLowest)
                                <span class="inline-flex items-center gap-1 rounded bg-success-500 px-2 py-0.5 text-[10px] font-bold tracking-[0.4px] text-white uppercase">
                                    <x-lucide name="arrow-down" :size="10" />
                                    {{ __('buyer.tag_lowest_price') }}
                                </span>
                            @endif
                            @if ($isFastest)
                                <span class="inline-flex items-center gap-1 rounded bg-brand-500 px-2 py-0.5 text-[10px] font-bold tracking-[0.4px] text-white uppercase">
                                    <x-lucide name="zap" :size="10" />
                                    {{ __('buyer.tag_fastest') }}
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <x-avatar :name="$profile?->company_name" :src="$profile?->getFirstMediaUrl('logo') ?: null" :size="36" />
                            <div class="min-w-0">
                                <x-buyer.supplier-link :profile="$profile" :seal="14" class="text-sm font-semibold text-gray-900" />
                                <p class="flex items-center gap-1 text-[11px] text-gray-500 tabular">
                                    <x-lucide name="star" :size="11" class="text-accent-500" />
                                    {{ number_format((float) ($profile?->rating_avg ?? 0), 1) }}
                                    · {{ trans_choice('buyer.reviews_count', $profile?->reviews_count ?? 0, ['count' => $profile?->reviews_count ?? 0]) }}
                                </p>
                            </div>
                        </div>
                        <x-status-badge :status="$quote->status" :quote="$quote" size="sm" />
                    </div>

                    <dl class="grid grid-cols-2 gap-x-3 gap-y-2 rounded-lg bg-gray-50 px-3 py-2.5 xl:grid-cols-4">
                        <div class="min-w-0">
                            <dt class="text-[10px] font-semibold tracking-[0.5px] text-gray-500 uppercase">{{ __('buyer.col_unit_price') }}</dt>
                            <dd class="text-[15px] font-bold text-brand-700"><x-buyer.money :amount="$quote->unit_price" /></dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[10px] font-semibold tracking-[0.5px] text-gray-500 uppercase">{{ __('buyer.col_total') }}</dt>
                            <dd class="text-[15px] font-bold text-gray-900"><x-buyer.money :amount="$quote->grandTotal()" :decimals="0" /></dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[10px] font-semibold tracking-[0.5px] text-gray-500 uppercase">{{ __('buyer.col_delivery') }}</dt>
                            <dd class="text-sm font-semibold tabular">{{ $quote->expected_delivery_date->translatedFormat('j M') }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[10px] font-semibold tracking-[0.5px] text-gray-500 uppercase">{{ __('buyer.col_valid') }}</dt>
                            <dd class="text-sm font-semibold tabular">{{ trans_choice('buyer.days_count', $quote->validity_days, ['count' => $quote->validity_days]) }}</dd>
                        </div>
                    </dl>

                    <ul class="flex flex-wrap gap-1.5 text-xs text-gray-700">
                        @if ($quote->payment_terms)
                            <li class="inline-flex max-w-full items-center gap-1.5 rounded-[5px] bg-gray-100 px-2 py-1">
                                <x-lucide name="credit-card" :size="12" class="text-gray-500" />
                                <span class="truncate">{{ $quote->payment_terms }}</span>
                            </li>
                        @endif
                        @if ($quote->min_order_qty)
                            <li class="inline-flex items-center gap-1.5 rounded-[5px] bg-gray-100 px-2 py-1">
                                <x-lucide name="package" :size="12" class="text-gray-500" />
                                {{ __('buyer.moq') }} <span dir="ltr" class="tabular">{{ rtrim(rtrim(number_format((float) $quote->min_order_qty, 3), '0'), '.') }}</span> {{ $rfq->unit?->name }}
                            </li>
                        @endif
                        @if ($quote->sample_availability)
                            <li class="inline-flex max-w-full items-center gap-1.5 rounded-[5px] bg-gray-100 px-2 py-1">
                                <x-lucide name="flask" :size="12" class="text-gray-500" />
                                <span class="truncate">{{ __('buyer.sample_chip', ['value' => $quote->sample_availability]) }}</span>
                            </li>
                        @endif
                        @if ($quote->brand_origin)
                            <li class="inline-flex max-w-full items-center gap-1.5 rounded-[5px] bg-gray-100 px-2 py-1">
                                <x-lucide name="map-pin" :size="12" class="text-gray-500" />
                                <span class="truncate">{{ $quote->brand_origin }}</span>
                            </li>
                        @endif
                    </ul>

                    <p class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
                        <span>
                            {{ __('ui.vat') }}: {{ $quote->vat_mode->label() }}@if ((float) $quote->vat_amount > 0) · <x-buyer.money :amount="$quote->vat_amount" />@endif
                        </span>
                        <span>
                            {{ __('ui.delivery_cost') }}:
                            @if ((float) $quote->delivery_cost === 0.0)
                                {{ __('rfq.free') }}
                            @else
                                <x-buyer.money :amount="$quote->delivery_cost" />
                            @endif
                        </span>
                    </p>

                    @if ($quote->extra_specs || $quote->warranty_policy)
                        <details class="group text-[13px]">
                            <summary class="link inline-flex cursor-pointer list-none items-center gap-1 text-xs">
                                {{ __('buyer.more_details') }}
                                <x-lucide name="chevron-down" :size="13" class="transition group-open:rotate-180" />
                            </summary>
                            <dl class="mt-2 flex flex-col gap-2 text-gray-700">
                                @if ($quote->extra_specs)
                                    <div><dt class="text-xs text-gray-500">{{ __('ui.extra_specs') }}</dt><dd class="whitespace-pre-line">{{ $quote->extra_specs }}</dd></div>
                                @endif
                                @if ($quote->warranty_policy)
                                    <div><dt class="text-xs text-gray-500">{{ __('ui.warranty') }}</dt><dd class="whitespace-pre-line">{{ $quote->warranty_policy }}</dd></div>
                                @endif
                            </dl>
                        </details>
                    @endif

                    @if ($quote->isRejected() && $quote->rejectionReason)
                        <p class="text-xs text-danger-700">{{ __('buyer.rejected_reason', ['reason' => $quote->rejectionReason->name]) }}</p>
                    @endif

                    @can('decide', $quote)
                        <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-gray-200 pt-3">
                            <button type="button" wire:click="startSelect({{ $quote->id }})" class="btn btn-primary btn-sm">
                                {{ __('rfq.select_and_chat') }}
                            </button>
                            <button type="button" wire:click="shortlist({{ $quote->id }})"
                                    aria-pressed="{{ $quote->isShortlisted() ? 'true' : 'false' }}"
                                    @class([
                                        'btn btn-sm border',
                                        'border-warning-200 bg-warning-100 text-warning-800' => $quote->isShortlisted(),
                                        'btn-secondary' => ! $quote->isShortlisted(),
                                    ])>
                                <x-lucide name="star" :size="14" />
                                {{ $quote->isShortlisted() ? __('rfq.shortlisted') : __('rfq.shortlist') }}
                            </button>
                            <button type="button" wire:click="startReject({{ $quote->id }})" class="btn btn-danger-ghost btn-sm ms-auto">
                                {{ __('rfq.reject') }}
                            </button>
                        </div>
                    @elseif ($quote->isSelected() && $quote->conversation)
                        <div class="mt-auto border-t border-gray-200 pt-3">
                            <a href="{{ route('buyer.chats.show', $quote->conversation) }}" wire:navigate class="btn btn-success btn-sm">
                                <x-lucide name="message-square" :size="14" />
                                {{ __('buyer.open_chat') }}
                            </a>
                        </div>
                    @endcan
                </article>
            @endforeach
        </div>
    @endif

    {{-- Award confirmation: selecting a quote reveals the buyer and closes the rest. --}}
    <x-modal name="award-quote" wire:model="showAwardModal">
        @if ($this->selectingQuote)
            @php $others = $this->liveQuotes->count() - 1; @endphp
            <div class="flex flex-col gap-4">
                <div class="flex items-start gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-500">
                        <x-lucide name="award" :size="18" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-brand-700">
                            {{ __('buyer.award_title', ['name' => $this->selectingQuote->supplier->supplierProfile?->company_name]) }}
                        </h2>
                        <p class="mt-1 text-sm leading-relaxed text-gray-700">
                            {{ __('buyer.award_body') }}
                            @if ($others > 0)
                                {{ trans_choice('buyer.award_others', $others, ['count' => $others]) }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-3 text-[13px]">
                    <p class="eyebrow">{{ __('buyer.revealed_to_supplier') }}</p>
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex items-center gap-1.5 text-gray-500"><x-lucide name="building" :size="13" />{{ __('ui.company_name') }}</span>
                        <span class="min-w-0 truncate font-semibold">{{ $buyerProfile?->company_name }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex items-center gap-1.5 text-gray-500"><x-lucide name="user" :size="13" />{{ __('buyer.contact_name') }}</span>
                        <span class="min-w-0 truncate font-semibold">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex items-center gap-1.5 text-gray-500"><x-lucide name="phone" :size="13" />{{ __('ui.phone') }}</span>
                        <span dir="ltr" class="font-semibold tabular">{{ auth()->user()->phone }}</span>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelSelect" class="btn btn-secondary">{{ __('buyer.not_yet') }}</button>
                    <button type="button" wire:click="select({{ $this->selectingQuote->id }})" wire:loading.attr="disabled" wire:target="select" class="btn btn-primary">
                        <x-lucide name="check" :size="15" />
                        {{ __('rfq.select_and_chat') }}
                    </button>
                </div>
            </div>
        @endif
    </x-modal>

    {{-- Rejection with a reason the supplier will see. --}}
    <x-modal name="reject-quote" wire:model="showRejectModal">
        @if ($this->rejectingQuote)
            <div class="flex flex-col gap-4" x-data="{ reason: null }">
                <div class="flex items-start gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-danger-100 text-danger-600">
                        <x-lucide name="x" :size="18" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-brand-700">
                            {{ __('buyer.reject_title', ['name' => $this->rejectingQuote->supplier->supplierProfile?->company_name]) }}
                        </h2>
                        <p class="mt-1 text-[13px] text-gray-500">{{ __('buyer.reject_body') }}</p>
                    </div>
                </div>

                <fieldset class="flex flex-col gap-1.5">
                    <legend class="sr-only">{{ __('rfq.reject_why') }}</legend>
                    @foreach ($this->reasons as $reason)
                        <label wire:key="reason-{{ $reason->id }}"
                               class="flex min-h-10 cursor-pointer items-center gap-2.5 rounded-lg border border-gray-200 px-3 py-2 text-sm transition has-checked:border-brand-500 has-checked:bg-brand-100">
                            <input type="radio" name="reject_reason" value="{{ $reason->id }}" x-model.number="reason"
                                   class="size-4 accent-brand-500">
                            {{ $reason->name }}
                        </label>
                    @endforeach
                </fieldset>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelReject" class="btn btn-secondary">{{ __('ui.cancel') }}</button>
                    <button type="button" x-on:click="reason && $wire.reject(reason)" x-bind:disabled="! reason"
                            class="btn btn-danger disabled:opacity-50">
                        {{ __('buyer.reject_quote') }}
                    </button>
                </div>
            </div>
        @endif
    </x-modal>
</div>
