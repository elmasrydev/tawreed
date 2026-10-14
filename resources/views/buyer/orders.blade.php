<x-layouts.buyer current="orders" :title="__('common.nav_orders')">
    <div class="shell-page">
        <x-page-header :title="__('common.nav_orders')" icon="package"
                       :subtitle="$ordersCount > 0 ? trans_choice('buyer.orders_count', $ordersCount, ['count' => number_format($ordersCount)]) : null" />

        @if ($orders->isEmpty())
            <x-empty-state icon="package" :title="__('buyer.no_orders')" :body="__('buyer.no_orders_body')">
                <a href="{{ route('buyer.rfqs.index') }}" wire:navigate class="btn btn-secondary">{{ __('common.nav_my_rfqs') }}</a>
            </x-empty-state>
        @else
            @php $columns = 'lg:grid-cols-[minmax(0,2.2fr)_minmax(0,1.6fr)_minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.2fr)]'; @endphp

            <div class="card overflow-hidden">
                <div class="table-head md:hidden lg:grid lg:gap-3.5 {{ $columns }}">
                    <span>{{ __('buyer.col_title') }}</span>
                    <span>{{ __('buyer.col_supplier') }}</span>
                    <span class="text-end">{{ __('buyer.col_total') }}</span>
                    <span>{{ __('buyer.col_awarded_on') }}</span>
                    <span>{{ __('buyer.col_delivery') }}</span>
                    <span></span>
                </div>

                @foreach ($orders as $order)
                    @php
                        $quote = $order->awardedQuote;
                        $quantity = $order->formattedQuantity();
                    @endphp

                    <div class="data-row lg:gap-3.5 {{ $columns }}">
                        <div class="min-w-0">
                            <a href="{{ route('buyer.rfqs.show', $order) }}" wire:navigate class="block min-w-0 font-medium text-gray-900 hover:text-brand-500 lg:truncate">
                                {{ $order->title }}
                            </a>
                            <p class="mt-0.5 truncate text-xs text-gray-500">
                                <span dir="ltr" class="tabular">{{ $order->reference }}</span>
                                @if ($quantity)
                                    · <span dir="ltr" class="tabular">{{ $quantity }}</span> {{ $order->unit?->name }}
                                @endif
                            </p>
                        </div>

                        <span class="min-w-0 text-[13px] font-medium text-gray-700">
                            <x-buyer.supplier-link :profile="$quote?->supplier?->supplierProfile" :seal="14" />
                        </span>

                        <span class="text-[13px] font-semibold text-brand-700 lg:text-end">
                            @if ($quote)
                                <x-buyer.money :amount="$quote->grandTotal()" />
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </span>

                        <span class="hidden text-[13px] text-gray-600 tabular lg:block">{{ $order->awarded_at?->translatedFormat('j M Y') ?? '—' }}</span>
                        <span class="hidden text-[13px] text-gray-600 tabular lg:block">{{ $quote?->expected_delivery_date?->translatedFormat('j M Y') ?? '—' }}</span>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-[13px] text-gray-600 lg:justify-end">
                            <span class="flex items-center gap-1 tabular lg:hidden">
                                <x-lucide name="calendar" :size="13" class="text-gray-400" />
                                {{ $order->awarded_at?->translatedFormat('j M Y') ?? '—' }}
                            </span>
                            @if ($order->conversation)
                                <a href="{{ route('buyer.chats.show', $order->conversation) }}" wire:navigate class="link inline-flex min-h-9 items-center gap-1 whitespace-nowrap lg:min-h-0">
                                    <x-lucide name="message-square" :size="13" />
                                    {{ __('buyer.message') }}
                                </a>
                            @endif
                            <a href="{{ route('buyer.rfqs.show', $order) }}" wire:navigate class="link ms-auto inline-flex min-h-9 items-center whitespace-nowrap lg:ms-0 lg:min-h-0">
                                {{ __('common.view') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{ $orders->links() }}
        @endif
    </div>
</x-layouts.buyer>
