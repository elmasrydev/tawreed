<x-layouts.supplier current="quotes" :title="__('ui.my_quotes')">
    <div class="shell-page">
        <h1 class="flex items-center gap-2.5 text-xl font-bold tracking-[-0.3px] text-brand-700 sm:text-2xl">
            <x-lucide name="tag" :size="22" class="text-accent-500" />
            {{ __('ui.my_quotes') }}
        </h1>

        @if (! $hasAnyQuotes)
            <x-empty-state icon="tag" :title="__('quote.no_quotes')" :body="__('quote.no_quotes_body')">
                <a href="{{ route('supplier.feed') }}" wire:navigate class="btn btn-primary btn-sm">{{ __('ui.rfq_feed') }}</a>
            </x-empty-state>
        @else
            <x-tabs :items="$tabs" :current="$tab" />

            @if ($rows->isEmpty())
                <x-empty-state icon="tag" :title="__('supplier.no_quotes_in_tab')" />
            @else
                <div class="card overflow-hidden">
                    <div class="table-head gap-3.5 md:hidden xl:grid xl:grid-cols-[minmax(0,2fr)_minmax(0,2fr)_minmax(0,0.9fr)_minmax(0,1fr)_minmax(0,1.1fr)_minmax(0,1.1fr)_10.5rem]">
                        <span>{{ __('supplier.col_rfq') }}</span>
                        <span>{{ __('supplier.col_buyer') }}</span>
                        <span>{{ __('supplier.col_unit') }}</span>
                        <span>{{ __('supplier.col_total') }}</span>
                        <span>{{ __('supplier.col_validity') }}</span>
                        <span>{{ __('ui.status') }}</span>
                        <span></span>
                    </div>

                    @foreach ($rows as ['quote' => $quote, 'rfq' => $rfq, 'rfqIsOpen' => $rfqIsOpen])
                        @php
                            $isLive = $quote->status->isActionable();
                            $expiresAt = $quote->created_at->copy()->addDays($quote->validity_days);
                            $daysLeft = (int) ceil(now()->floatDiffInDays($expiresAt, false));
                            [$validity, $validityClass] = match (true) {
                                $quote->isSelected() => [__('supplier.awarded_on', ['date' => ($quote->selected_at ?? $quote->updated_at)->translatedFormat('j M')]), 'text-gray-500'],
                                ! $isLive => ['—', 'text-gray-400'],
                                $daysLeft <= 0 => [__('supplier.validity_lapsed'), 'text-danger-700'],
                                $daysLeft <= 1 => [__('supplier.expires_in_day'), 'text-danger-700'],
                                default => [trans_choice('supplier.valid_days', $daysLeft, ['count' => $daysLeft]), 'text-gray-700'],
                            };
                            $conversation = $quote->conversation;
                            $buyerLabel = $rfq->buyerLabel() !== '' ? $rfq->buyerLabel() : __('supplier.anonymous_buyer');
                        @endphp

                        <div class="grid gap-2.5 border-b border-gray-100 px-4 py-3.5 text-sm transition-colors last:border-b-0 hover:bg-gray-50 xl:items-center xl:gap-3.5 xl:py-3 xl:grid-cols-[minmax(0,2fr)_minmax(0,2fr)_minmax(0,0.9fr)_minmax(0,1fr)_minmax(0,1.1fr)_minmax(0,1.1fr)_10.5rem]">
                            <div class="min-w-0">
                                <p class="font-medium break-words text-gray-900 xl:line-clamp-2" title="{{ $rfq->title }}">{{ $rfq->title }}</p>
                                <p class="text-xs text-gray-500 tabular">{{ $rfq->quantityWithUnit() }}</p>
                            </div>

                            <p class="flex min-w-0 items-start gap-1.5 text-[13px] text-gray-500" title="{{ $buyerLabel }}">
                                <x-lucide name="lock" :size="12" class="mt-1 shrink-0" />
                                <span class="break-words">{{ $buyerLabel }}</span>
                            </p>

                            <div class="grid grid-cols-3 gap-3 xl:contents">
                                <div>
                                    <p class="eyebrow xl:hidden">{{ __('supplier.col_unit') }}</p>
                                    <p class="tabular">{{ number_format((float) $quote->unit_price, 2) }}</p>
                                </div>
                                <div>
                                    <p class="eyebrow xl:hidden">{{ __('supplier.col_total') }}</p>
                                    <p class="font-semibold text-gray-900 tabular">{{ number_format($quote->grandTotal()) }}</p>
                                </div>
                                <div>
                                    <p class="eyebrow xl:hidden">{{ __('supplier.col_validity') }}</p>
                                    <p class="text-[13px] tabular {{ $validityClass }}">{{ $validity }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col items-start gap-1">
                                <x-status-badge :status="$quote->status" :quote="$quote" size="sm" />
                                @if ($quote->isRejected() && $quote->rejectionReason)
                                    <span class="text-[11px] text-gray-500">{{ __('supplier.reason', ['reason' => $quote->rejectionReason->name]) }}</span>
                                @endif
                                @if ($quote->isSelected() && ! $hasSubscription)
                                    <span class="text-[11px] leading-snug text-accent-800">{{ __('supplier.selected_locked_hint') }}</span>
                                @endif
                            </div>

                            <div class="flex xl:justify-end">
                                @if ($quote->isSelected())
                                    @if ($hasSubscription && $conversation)
                                        <a href="{{ route('supplier.chats.show', $conversation) }}" wire:navigate class="btn btn-primary btn-sm max-xl:h-10 max-xl:w-full">
                                            <x-lucide name="message-square" :size="14" />
                                            {{ __('supplier.open_chat') }}
                                        </a>
                                    @elseif (! $hasSubscription)
                                        <a href="{{ route('supplier.subscription') }}" wire:navigate class="btn btn-accent btn-sm max-xl:h-10 max-xl:w-full">
                                            <x-lucide name="lock" :size="14" />
                                            {{ __('supplier.subscribe_to_unlock') }}
                                        </a>
                                    @endif
                                @elseif ($rfqIsOpen)
                                    <a href="{{ route('supplier.rfqs.show', $rfq->id) }}" wire:navigate class="btn btn-ghost btn-sm max-xl:h-10 max-xl:w-full max-xl:border max-xl:border-gray-200">
                                        {{ __('supplier.view_rfq') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{ $quotes->links() }}
            @endif
        @endif
    </div>
</x-layouts.supplier>
