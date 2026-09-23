<x-layouts.buyer current="home" :title="__('common.nav_dashboard')">
    <div class="shell-page">
        <div>
            <h1 class="text-xl font-bold tracking-[-0.3px] text-brand-700 sm:text-2xl">
                {{ __($greetingKey, ['name' => $firstName]) }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $today }}
                <span aria-hidden="true" class="mx-1 text-gray-300">·</span>
                @if ($attentionCount > 0)
                    {{ trans_choice('buyer.needs_attention_count', $attentionCount, ['count' => $attentionCount]) }}
                @else
                    {{ __('buyer.all_caught_up') }}
                @endif
            </p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <x-kpi-card :label="__('buyer.kpi_open_rfqs')" :value="$kpis['open']" icon="file-text" icon-tone="info"
                        :href="route('buyer.rfqs.index', ['status' => 'open'])"
                        :delta="$kpis['closingSoon'] > 0 ? trans_choice('buyer.kpi_closing_soon', $kpis['closingSoon'], ['count' => $kpis['closingSoon']]) : null"
                        delta-tone="danger" />
            <x-kpi-card :label="__('buyer.kpi_new_quotes')" :value="$kpis['pending']" icon="tag" icon-tone="accent"
                        :href="route('buyer.quotes.index', ['status' => 'pending'])"
                        :delta="$kpis['pendingSinceYesterday'] > 0 ? __('buyer.kpi_since_yesterday', ['count' => $kpis['pendingSinceYesterday']]) : null"
                        delta-tone="success" />
            <x-kpi-card :label="__('buyer.kpi_unread_messages')" :value="$kpis['unread']" icon="message-square" icon-tone="neutral"
                        :href="route('buyer.chats.index')" />
            <x-kpi-card :label="__('buyer.kpi_awarded_month')" :value="$kpis['awardedThisMonth']" icon="award" icon-tone="success"
                        :href="route('buyer.rfqs.index', ['status' => 'awarded'])"
                        :delta="$kpis['awardedValue'] > 0 ? __('buyer.kpi_awarded_value', ['amount' => number_format($kpis['awardedValue']).' '.__('ui.egp')]) : null" />
        </div>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3.5 sm:px-[18px]">
                    <h2 class="card-title">
                        <x-lucide name="tag" :size="16" class="text-brand-500" />
                        {{ __('buyer.recent_quotes') }}
                    </h2>
                    @if ($recentQuotes->isNotEmpty())
                        <a href="{{ route('buyer.quotes.index') }}" wire:navigate class="link text-[13px]">{{ __('common.view_all') }}</a>
                    @endif
                </div>

                @if ($recentQuotes->isEmpty())
                    <div class="flex flex-col items-center gap-2 px-6 py-12 text-center">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                            <x-lucide name="tag" :size="22" />
                        </span>
                        <p class="text-sm font-semibold text-gray-900">{{ __('rfq.no_quotes_yet') }}</p>
                        <p class="max-w-xs text-[13px] text-gray-500">{{ __('buyer.recent_quotes_empty') }}</p>
                    </div>
                @else
                    <div class="table-head md:grid-cols-[minmax(0,2fr)_minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,0.9fr)_minmax(0,0.9fr)] md:gap-3.5">
                        <span>{{ __('buyer.col_rfq') }}</span>
                        <span>{{ __('buyer.col_supplier') }}</span>
                        <span class="text-end">{{ __('buyer.col_total') }}</span>
                        <span>{{ __('buyer.col_delivery') }}</span>
                        <span>{{ __('ui.status') }}</span>
                    </div>

                    @foreach ($recentQuotes as $quote)
                        <div class="data-row text-[13px] md:grid-cols-[minmax(0,2fr)_minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,0.9fr)_minmax(0,0.9fr)] md:gap-3.5">
                            <a href="{{ route('buyer.rfqs.quotes', $quote->rfq) }}" wire:navigate
                               class="min-w-0 truncate font-medium text-gray-900 hover:text-brand-500">
                                {{ $quote->rfq->title }}
                                @if ($quote->rfq->formattedQuantity())
                                    <span class="text-gray-500">· <span dir="ltr">{{ $quote->rfq->formattedQuantity() }}</span> {{ $quote->rfq->unit?->name }}</span>
                                @endif
                            </a>
                            <x-buyer.supplier-link :profile="$quote->supplier->supplierProfile" :seal="14" class="text-gray-700" />
                            <div class="flex flex-wrap items-center justify-between gap-2 md:contents">
                                <x-buyer.money :amount="$quote->grandTotal()" class="font-semibold text-gray-900 md:text-end" />
                                <span class="text-gray-700 tabular">
                                    <x-lucide name="truck" :size="13" class="inline text-gray-400 md:hidden" />
                                    {{ $quote->expected_delivery_date->translatedFormat('j M') }}
                                </span>
                                <span><x-status-badge :status="$quote->status" :quote="$quote" size="sm" /></span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </section>

            <section class="flex flex-col gap-3">
                <h2 class="card-title">
                    <x-lucide name="zap" :size="16" class="text-danger-600" />
                    {{ __('buyer.needs_attention') }}
                    @if ($attentionCount > 0)
                        <span class="rounded-full bg-danger-100 px-2 py-0.5 text-[11px] font-bold text-danger-700 tabular">{{ $attentionCount }}</span>
                    @endif
                </h2>

                @forelse ($attention as $item)
                    @php $rfq = $item['rfq']; @endphp
                    <article class="card flex flex-col gap-2 px-4 py-3.5">
                        <div class="flex items-start justify-between gap-2">
                            <a href="{{ route('buyer.rfqs.show', $rfq) }}" wire:navigate
                               class="flex min-w-0 items-start gap-2 text-sm leading-snug font-semibold text-gray-900 hover:text-brand-500">
                                <x-lucide :name="$item['reason'] === 'closing' ? 'clock' : 'file-text'" :size="16"
                                          @class(['mt-0.5', 'text-danger-700' => $item['reason'] === 'closing', 'text-brand-500' => $item['reason'] !== 'closing']) />
                                <span class="min-w-0 break-words">{{ $rfq->title }}</span>
                            </a>
                            <x-countdown-chip :until="$rfq->quote_deadline" size="sm" />
                        </div>

                        <p class="text-[13px] text-gray-500">
                            @if ($item['quotesCount'] > 0)
                                {{ trans_choice('rfq.quotes_count', $item['quotesCount'], ['count' => $item['quotesCount']]) }}
                                @if ($item['lowestTotal'] !== null)
                                    · {{ __('buyer.lowest') }} <x-buyer.money :amount="$item['lowestTotal']" :decimals="0" />
                                @endif
                            @else
                                {{ $rfq->supply_type === App\Enums\SupplyType::Recurring ? $rfq->supply_type->label().' · ' : '' }}{{ __('buyer.zero_quotes_yet') }}
                            @endif
                        </p>

                        @if ($item['quotesCount'] > 0)
                            <a href="{{ route('buyer.rfqs.quotes', $rfq) }}" wire:navigate class="link self-start text-[13px]">{{ __('buyer.compare_and_select') }}</a>
                        @else
                            <a href="{{ route('buyer.rfqs.show', $rfq) }}" wire:navigate class="link self-start text-[13px]">{{ __('buyer.view_request') }}</a>
                        @endif
                    </article>
                @empty
                    <div class="card flex items-center gap-3 px-4 py-4 text-[13px] text-gray-600">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-600">
                            <x-lucide name="check-circle" :size="18" />
                        </span>
                        {{ __('buyer.attention_empty') }}
                    </div>
                @endforelse
            </section>
        </div>
    </div>
</x-layouts.buyer>
