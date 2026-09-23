<x-layouts.buyer current="requests" :title="$rfq->title">
    <div class="shell-page">
        <x-page-header :title="$rfq->title" :back="route('buyer.rfqs.index')" :back-label="__('common.nav_my_rfqs')">
            <x-slot:meta>
                <x-status-badge :status="$rfq->status" />
                <span dir="ltr" class="tabular">{{ $rfq->reference }}</span>
                @if ($rfq->categoryPath())
                    <span aria-hidden="true" class="text-gray-300">·</span>
                    <span>{{ $rfq->categoryPath() }}</span>
                @endif
                @if ($rfq->published_at)
                    <span aria-hidden="true" class="text-gray-300">·</span>
                    <span class="tabular">{{ __('buyer.posted_on', ['date' => $rfq->published_at->translatedFormat('j M')]) }}</span>
                @endif
                @unless ($rfq->isDraft())
                    <x-buyer.rfq-deadline :rfq="$rfq" />
                    <span class="font-semibold text-gray-900 tabular">
                        {{ trans_choice('rfq.quotes_count', $rfq->quotes_count, ['count' => $rfq->quotes_count]) }}
                    </span>
                @endunless
            </x-slot:meta>

            <x-slot:actions>
                @if ($rfq->isDraft())
                    @can('deleteDraft', $rfq)
                        <form method="POST" action="{{ route('buyer.rfqs.destroy', $rfq) }}"
                              x-data x-on:submit="if (! confirm(@js(__('buyer.delete_draft_confirm')))) $event.preventDefault()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger-outline">
                                <x-lucide name="trash" :size="15" />
                                {{ __('buyer.delete_draft') }}
                            </button>
                        </form>
                    @endcan
                    <a href="{{ route('buyer.rfqs.edit', $rfq) }}" wire:navigate class="btn btn-primary">
                        <x-lucide name="edit" :size="15" />
                        {{ __('buyer.continue_editing') }}
                    </a>
                @else
                    <a href="{{ route('buyer.rfqs.quotes', $rfq) }}" wire:navigate class="btn btn-primary">
                        <x-lucide name="tag" :size="15" />
                        {{ __('buyer.compare_quotes_count', ['count' => $rfq->quotes_count]) }}
                    </a>
                @endif
            </x-slot:actions>
        </x-page-header>

        @if ($rfq->isDraft())
            <x-alert tone="neutral" icon="edit">{{ __('buyer.draft_banner') }}</x-alert>
        @endif

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="flex min-w-0 flex-col gap-4">
                <section class="card flex flex-col gap-4 p-4 sm:p-5">
                    <h2 class="eyebrow text-xs">{{ __('buyer.request_card') }}</h2>

                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <x-buyer.fact icon="package" :label="__('ui.quantity')">
                            @if ($rfq->formattedQuantity())
                                <span dir="ltr" class="tabular">{{ $rfq->formattedQuantity() }}</span> {{ $rfq->unit?->name }}
                            @else
                                —
                            @endif
                        </x-buyer.fact>
                        <x-buyer.fact icon="truck" :label="__('buyer.deliver_by')">
                            <span class="tabular">{{ $rfq->delivery_date?->translatedFormat('j M Y') ?? '—' }}</span>
                        </x-buyer.fact>
                        <x-buyer.fact icon="map-pin" :label="__('ui.delivery_governorate')">
                            {{ $rfq->governorate?->name ?? '—' }}
                        </x-buyer.fact>
                        <x-buyer.fact icon="repeat" :label="__('ui.supply_type')">
                            {{ $rfq->supply_type->label() }}@if ($rfq->recurrence_note) · {{ $rfq->recurrence_note }}@endif
                        </x-buyer.fact>
                        <x-buyer.fact icon="clock" :label="__('ui.quote_deadline')">
                            <span class="tabular">{{ $rfq->quote_deadline?->translatedFormat('j M Y') ?? '—' }}</span>
                        </x-buyer.fact>
                        @if ($rfq->published_at)
                            <x-buyer.fact icon="calendar" :label="__('buyer.published')">
                                <span class="tabular">{{ $rfq->published_at->translatedFormat('j M Y') }}</span>
                            </x-buyer.fact>
                        @endif
                    </dl>
                </section>

                <section class="card flex flex-col gap-2 p-4 sm:p-5">
                    <h2 class="card-title">{{ __('ui.specs') }}</h2>
                    @if ($rfq->specs)
                        <p class="text-sm leading-relaxed whitespace-pre-line text-gray-700">{{ $rfq->specs }}</p>
                    @else
                        <p class="text-sm text-gray-400">—</p>
                    @endif
                </section>

                @if ($rfq->notes)
                    <section class="card flex flex-col gap-2 p-4 sm:p-5">
                        <h2 class="card-title">{{ __('ui.notes') }}</h2>
                        <p class="text-sm leading-relaxed whitespace-pre-line text-gray-700">{{ $rfq->notes }}</p>
                    </section>
                @endif

                @if ($rfq->getMedia('attachments')->isNotEmpty())
                    <section class="card flex flex-col gap-3 p-4 sm:p-5">
                        <h2 class="card-title">
                            <x-lucide name="paperclip" :size="16" class="text-brand-500" />
                            {{ __('ui.attachments') }}
                        </h2>
                        <ul class="grid gap-2 sm:grid-cols-2">
                            @foreach ($rfq->getMedia('attachments') as $media)
                                <li>
                                    <x-buyer.attachment :media="$media" />
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="flex flex-col gap-4">
                @unless ($rfq->isDraft())
                    <section class="card flex flex-col gap-3 p-4 sm:p-5">
                        <h2 class="card-title">
                            <x-lucide name="tag" :size="16" class="text-brand-500" />
                            {{ __('common.nav_quotes') }}
                        </h2>

                        @if ($rfq->awardedQuote)
                            <div class="flex flex-col gap-2 rounded-lg border border-success-200 bg-success-25 p-3">
                                <p class="flex items-center gap-1.5 text-xs font-semibold text-success-700">
                                    <x-lucide name="award" :size="14" />
                                    {{ __('buyer.awarded_to') }}
                                </p>
                                <x-buyer.supplier-link :profile="$rfq->awardedQuote->supplier->supplierProfile" class="text-sm font-semibold text-gray-900" />
                                <x-buyer.money :amount="$rfq->awardedQuote->grandTotal()" class="text-sm font-bold text-brand-700" />
                                @if ($rfq->conversation)
                                    <a href="{{ route('buyer.chats.show', $rfq->conversation) }}" wire:navigate class="btn btn-success btn-sm self-start">
                                        <x-lucide name="message-square" :size="14" />
                                        {{ __('buyer.open_chat') }}
                                    </a>
                                @endif
                            </div>
                        @endif

                        <dl class="flex flex-col divide-y divide-gray-100 text-sm">
                            <div class="flex items-center justify-between gap-3 py-2">
                                <dt class="text-gray-500">{{ __('buyer.quotes_received') }}</dt>
                                <dd class="font-semibold tabular">{{ $rfq->quotes_count }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-2">
                                <dt class="text-gray-500">{{ __('buyer.lowest_total') }}</dt>
                                <dd class="font-semibold">
                                    @if ($lowestTotal !== null)
                                        <x-buyer.money :amount="$lowestTotal" />
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-2">
                                <dt class="text-gray-500">{{ __('rfq.shortlisted') }}</dt>
                                <dd class="font-semibold tabular">{{ $shortlistedCount }}</dd>
                            </div>
                        </dl>

                        <a href="{{ route('buyer.rfqs.quotes', $rfq) }}" wire:navigate class="btn btn-secondary w-full">
                            {{ __('buyer.compare_quotes_count', ['count' => $rfq->quotes_count]) }}
                        </a>
                    </section>
                @endunless

                <x-alert tone="info" icon="lock">
                    {{ __('rfq.anon_banner', ['label' => $anonymousLabel]) }}
                </x-alert>
            </aside>
        </div>
    </div>
</x-layouts.buyer>
