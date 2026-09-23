<x-layouts.supplier current="feed" :title="$item->title">
    @php($ownQuote = $item->ownQuote)

    <div class="shell-page">
        <a href="{{ route('supplier.feed') }}" wire:navigate
           class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-gray-500 hover:text-brand-700">
            <x-lucide name="arrow-left" :size="14" />
            {{ __('ui.rfq_feed') }}
        </a>

        <div class="-mt-1 flex flex-col gap-4 md:flex-row md:items-start md:justify-between md:gap-5">
            <div class="flex min-w-0 flex-col gap-2">
                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                    <h1 class="min-w-0 text-xl font-bold tracking-[-0.3px] break-words text-brand-700 sm:text-2xl">{{ $item->title }}</h1>
                    @if ($item->status)
                        <x-status-badge :status="$item->status" />
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-2 text-[13px] text-gray-500">
                    <span class="inline-flex items-center gap-1.5 font-semibold text-gray-600">
                        <x-lucide name="lock" :size="13" />
                        {{ $item->buyerLabel() !== '' ? $item->buyerLabel() : __('supplier.anonymous_buyer') }}
                    </span>
                    <span aria-hidden="true">·</span>
                    <span dir="ltr" class="tabular">{{ $item->reference }}</span>
                    @if ($item->deadlineAt)
                        <span aria-hidden="true">·</span>
                        <x-countdown-chip :until="$item->deadlineAt" />
                    @endif
                    <span class="font-semibold text-gray-900 tabular">
                        {{ trans_choice('supplier.quotes_so_far', $item->quotesCount, ['count' => $item->quotesCount]) }}
                    </span>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @if ($canQuote)
                    <button type="button" class="btn btn-accent w-full md:w-auto max-md:h-11" x-data
                            x-on:click="$dispatch('open-drawer', 'submit-quote')">
                        <x-lucide name="tag" :size="16" />
                        {{ __('ui.submit_quote') }}
                    </button>
                @elseif ($ownQuote)
                    <x-badge tone="success" icon="check">{{ __('ui.quote_submitted_badge') }}</x-badge>
                @else
                    <button type="button" class="btn w-full md:w-auto max-md:h-11" disabled>
                        <x-lucide name="lock" :size="16" />
                        {{ __('ui.submit_quote') }}
                    </button>
                @endif
            </div>
        </div>

        @if ($gate)
            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 rounded-lg bg-gray-900 px-3.5 py-2.5 text-[13px] text-white md:self-end">
                <x-lucide name="lock" :size="14" class="shrink-0" />
                <span class="min-w-0 flex-1">{{ $gate['message'] }}</span>
                @if ($gate['url'])
                    <a href="{{ $gate['url'] }}" wire:navigate class="inline-flex items-center gap-1 font-semibold text-accent-500 hover:text-accent-200">
                        {{ $gate['action'] }}
                        <x-lucide name="arrow-right" :size="14" />
                    </a>
                @endif
            </div>
        @endif

        @if ($ownQuote)
            <div class="card flex flex-wrap items-center gap-x-5 gap-y-3 border-success-200 bg-success-25 px-5 py-4">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-600">
                    <x-lucide name="check-circle" :size="20" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="flex flex-wrap items-center gap-2 text-[15px] font-semibold text-gray-900">
                        {{ __('supplier.your_quote') }}
                        <x-status-badge :status="$ownQuote->status" :quote="$ownQuote" size="sm" />
                    </p>
                    <p class="mt-0.5 text-[13px] text-gray-500">
                        {{ __('ui.submitted_on', ['date' => $ownQuote->created_at->translatedFormat('j M Y')]) }}
                    </p>
                </div>
                <div class="text-end">
                    <p class="eyebrow">{{ __('quote.grand_total') }}</p>
                    <p class="text-lg font-bold text-brand-700 tabular">{{ number_format($ownQuote->grandTotal(), 2) }} {{ __('common.egp') }}</p>
                </div>
                <a href="{{ route('supplier.quotes.index') }}" wire:navigate class="btn btn-secondary btn-sm max-md:h-10 max-md:w-full">{{ __('supplier.view_my_quotes') }}</a>
            </div>
        @elseif ($canQuote && ! $hasSubscription)
            <x-alert tone="info" icon="info">
                {{ __('supplier.quote_without_plan') }}
                <x-slot:action>
                    <a href="{{ route('supplier.subscription') }}" wire:navigate class="link text-[13px] whitespace-nowrap">{{ __('subscription.view_plans') }}</a>
                </x-slot:action>
            </x-alert>
        @endif

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="card flex min-w-0 flex-col gap-5 p-5">
                <dl class="grid grid-cols-2 gap-x-3 gap-y-4 sm:grid-cols-4">
                    @foreach ([
                        ['package', __('ui.quantity'), $item->quantityWithUnit()],
                        ['truck', __('supplier.deliver_by'), $item->deliveryDate],
                        ['repeat', __('ui.supply_type'), $item->supplyType],
                        ['map-pin', __('ui.delivery_governorate'), $item->governorate],
                    ] as [$icon, $label, $value])
                        <div class="min-w-0">
                            <dt class="flex items-center gap-1 text-[11px] text-gray-500">
                                <x-lucide :name="$icon" :size="12" />
                                {{ $label }}
                            </dt>
                            <dd class="mt-0.5 text-base font-semibold break-words text-gray-900 tabular">{{ $value !== '' ? $value : '—' }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($item->isRecurring && $item->recurrenceNote)
                    <p class="-mt-2 flex items-start gap-2 rounded-lg bg-gray-50 px-3 py-2 text-[13px] text-gray-700">
                        <x-lucide name="repeat" :size="14" class="mt-0.5 shrink-0 text-gray-500" />
                        {{ $item->recurrenceNote }}
                    </p>
                @endif

                <div class="text-[13px] text-gray-500">
                    {{ __('supplier.category_label') }}: <span class="font-medium text-gray-700">{{ $item->categoryPath() }}</span>
                </div>

                <section>
                    <h2 class="eyebrow mb-1.5 text-xs">{{ __('supplier.specifications') }}</h2>
                    <p class="text-sm leading-[1.65] whitespace-pre-line text-gray-700">{{ $item->specs }}</p>
                </section>

                @if ($item->notes)
                    <section>
                        <h2 class="eyebrow mb-1.5 text-xs">{{ __('supplier.buyer_conditions') }}</h2>
                        <p class="text-sm leading-[1.65] whitespace-pre-line text-gray-700">{{ $item->notes }}</p>
                    </section>
                @endif

                @if ($attachments->isNotEmpty())
                    <section>
                        <h2 class="eyebrow mb-2 text-xs">{{ __('ui.attachments') }}</h2>
                        <ul class="flex flex-wrap gap-2">
                            @foreach ($attachments as $media)
                                <li class="min-w-0 max-w-full">
                                    <a href="{{ route('supplier.rfqs.attachment', [$item->id, $media]) }}"
                                       class="flex min-h-11 items-center gap-2.5 rounded-lg border border-gray-200 px-2.5 py-1.5 text-[13px] transition hover:border-brand-500">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-brand-100 text-[10px] font-bold text-brand-700 uppercase">
                                            {{ Illuminate\Support\Str::limit($media->extension, 4, '') }}
                                        </span>
                                        <span class="min-w-0 truncate font-medium text-gray-900">{{ $media->file_name }}</span>
                                        <span class="shrink-0 text-xs text-gray-500 tabular" dir="ltr">{{ $media->human_readable_size }}</span>
                                        <x-lucide name="download" :size="14" class="shrink-0 text-brand-500" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="card flex flex-col gap-2.5 p-4">
                <p class="eyebrow flex items-center gap-1.5 text-xs">
                    <x-lucide name="lock" :size="13" />
                    {{ __('supplier.anonymous_buyer') }}
                </p>
                <dl class="flex flex-col gap-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">{{ __('supplier.buyer_type') }}</dt>
                        <dd class="text-end font-semibold text-gray-900">{{ $item->buyerType() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">{{ __('supplier.area') }}</dt>
                        <dd class="text-end font-semibold text-gray-900">{{ $item->governorate !== '' ? $item->governorate : '—' }}</dd>
                    </div>
                    @foreach ([__('supplier.company'), __('supplier.contact')] as $lockedLabel)
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ $lockedLabel }}</dt>
                            <dd class="inline-flex items-center gap-1.5 rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-400">
                                <x-lucide name="lock" :size="11" />
                                {{ __('supplier.after_selection') }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
                <p class="border-t border-gray-100 pt-2.5 text-xs leading-relaxed text-gray-500">{{ __('supplier.anonymity_note') }}</p>
            </aside>
        </div>
    </div>

    @if ($canQuote)
        <x-drawer name="submit-quote" :show="request()->boolean('quote')">
            <livewire:supplier.submit-quote :rfq="$rfq" :embedded="true" />
        </x-drawer>
    @endif
</x-layouts.supplier>
