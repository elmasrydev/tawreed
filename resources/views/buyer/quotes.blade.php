<x-layouts.buyer current="quotes" :title="__('common.nav_quotes')">
    <div class="shell-page">
        <x-page-header :title="__('common.nav_quotes')" :subtitle="__('buyer.quotes_inbox_sub')" icon="tag" />

        <x-tabs :items="$tabs" :current="$currentTab" />

        @if ($quotes->isEmpty())
            <x-empty-state icon="tag" :title="$currentTab === 'all' ? __('rfq.no_quotes_yet') : __('buyer.no_quotes_in_tab')"
                           :body="$currentTab === 'all' ? __('rfq.no_quotes_yet_body') : null" />
        @else
            @php $columns = 'lg:grid-cols-[minmax(0,2.1fr)_minmax(0,1.6fr)_minmax(0,0.9fr)_minmax(0,1.1fr)_minmax(0,1.2fr)_minmax(0,1.1fr)_70px]'; @endphp

            <div class="card overflow-hidden">
                <div class="table-head md:hidden lg:grid lg:gap-3.5 {{ $columns }}">
                    <span>{{ __('buyer.col_rfq') }}</span>
                    <span>{{ __('buyer.col_supplier') }}</span>
                    <span class="text-end">{{ __('buyer.col_unit_price') }}</span>
                    <span class="text-end">{{ __('buyer.col_total') }}</span>
                    <span>{{ __('buyer.col_valid') }}</span>
                    <span>{{ __('ui.status') }}</span>
                    <span></span>
                </div>

                @foreach ($quotes as $quote)
                    @php
                        $expiresAt = $quote->created_at->copy()->addDays($quote->validity_days);
                        $tracksValidity = $quote->isActionable();
                        $expiringSoon = $tracksValidity && $expiresAt->isFuture() && now()->diffInHours($expiresAt) <= 24;
                    @endphp

                    <div class="data-row lg:gap-3.5 {{ $columns }}">
                        <div class="flex min-w-0 items-start justify-between gap-3">
                            <a href="{{ route('buyer.rfqs.show', $quote->rfq) }}" wire:navigate
                               class="min-w-0 font-medium text-gray-900 hover:text-brand-500 lg:truncate">
                                {{ $quote->rfq->title }}
                                @if ($quote->rfq->formattedQuantity())
                                    <span class="font-normal text-gray-500">· <span dir="ltr">{{ $quote->rfq->formattedQuantity() }}</span> {{ $quote->rfq->unit?->name }}</span>
                                @endif
                            </a>
                            <x-status-badge :status="$quote->status" :quote="$quote" size="sm" class="lg:hidden" />
                        </div>

                        <x-buyer.supplier-link :profile="$quote->supplier->supplierProfile" :seal="14" class="text-[13px] text-gray-700" />

                        <span class="hidden text-end text-[13px] lg:block"><x-buyer.money :amount="$quote->unit_price" /></span>
                        <span class="hidden text-end font-semibold lg:block"><x-buyer.money :amount="$quote->grandTotal()" /></span>

                        <span @class([
                            'hidden text-[13px] whitespace-nowrap tabular lg:block',
                            'font-semibold text-danger-700' => $expiringSoon || ($tracksValidity && $expiresAt->isPast()),
                            'text-gray-700' => $tracksValidity && ! $expiringSoon && $expiresAt->isFuture(),
                            'text-gray-400' => ! $tracksValidity,
                        ])>
                            @if (! $tracksValidity)
                                —
                            @elseif ($expiresAt->isPast())
                                {{ __('buyer.quote_expired') }}
                            @else
                                {{ __('buyer.expires_in', ['time' => App\Support\Countdown::format($expiresAt)]) }}
                            @endif
                        </span>

                        <span class="hidden lg:block"><x-status-badge :status="$quote->status" :quote="$quote" size="sm" /></span>

                        <span class="hidden justify-end lg:flex">
                            <a href="{{ route('buyer.rfqs.quotes', $quote->rfq) }}" wire:navigate class="link px-2 py-1 text-[13px]">{{ __('buyer.compare') }}</a>
                        </span>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[13px] lg:hidden">
                            <x-buyer.money :amount="$quote->grandTotal()" class="font-semibold text-gray-900" />
                            <span class="text-gray-500"><x-buyer.money :amount="$quote->unit_price" /> / {{ $quote->rfq->unit?->name }}</span>
                            @if ($tracksValidity)
                                <span @class(['tabular', 'font-semibold text-danger-700' => $expiringSoon || $expiresAt->isPast(), 'text-gray-500' => ! $expiringSoon && $expiresAt->isFuture()])>
                                    {{ $expiresAt->isPast() ? __('buyer.quote_expired') : __('buyer.expires_in', ['time' => App\Support\Countdown::format($expiresAt)]) }}
                                </span>
                            @endif
                            <a href="{{ route('buyer.rfqs.quotes', $quote->rfq) }}" wire:navigate class="link ms-auto inline-flex min-h-9 items-center">{{ __('buyer.compare') }}</a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{ $quotes->links() }}
        @endif
    </div>
</x-layouts.buyer>
