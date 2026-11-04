<x-layouts.buyer current="requests" :title="__('common.nav_my_rfqs')">
    <div class="shell-page">
        <x-page-header :title="__('common.nav_my_rfqs')" icon="file-text">
            <x-slot:actions>
                <a href="{{ route('buyer.rfqs.create') }}" wire:navigate class="btn btn-accent">
                    <x-lucide name="plus" :size="16" />
                    {{ __('common.new_rfq') }}
                </a>
            </x-slot:actions>
        </x-page-header>

        <x-tabs :items="$tabs" :current="$currentTab" />

        @if ($rfqs->isEmpty())
            <x-empty-state icon="file-text"
                           :title="$currentTab === 'all' ? __('rfq.no_requests') : __('buyer.no_requests_in_tab')"
                           :body="$currentTab === 'all' ? __('rfq.no_requests_body') : null">
                @if ($currentTab === 'all')
                    <a href="{{ route('buyer.rfqs.create') }}" wire:navigate class="btn btn-accent">
                        <x-lucide name="plus" :size="16" />
                        {{ __('common.new_rfq') }}
                    </a>
                @endif
            </x-empty-state>
        @else
            {{-- Table from xl up (stacked rows below): every column has a floor wide
                 enough for its values, and all cells start-align with their
                 headers so LTR and RTL line up the same way. --}}
            @php $columns = 'xl:grid-cols-[minmax(10rem,2.4fr)_minmax(7rem,1.4fr)_minmax(6.5rem,0.9fr)_minmax(9rem,1.1fr)_minmax(4.5rem,0.6fr)_minmax(6.5rem,0.9fr)_minmax(7rem,1fr)]'; @endphp

            <div class="card overflow-hidden">
                <div class="table-head md:hidden xl:grid xl:gap-3.5 {{ $columns }}">
                    <span>{{ __('buyer.col_title') }}</span>
                    <span>{{ __('buyer.col_category') }}</span>
                    <span>{{ __('buyer.col_qty') }}</span>
                    <span>{{ __('buyer.col_deadline') }}</span>
                    <span>{{ __('buyer.col_quotes') }}</span>
                    <span>{{ __('ui.status') }}</span>
                    <span></span>
                </div>

                @foreach ($rfqs as $rfq)
                    @php
                        $url = $rfq->isDraft() ? route('buyer.rfqs.edit', $rfq) : route('buyer.rfqs.show', $rfq);
                        $quantity = $rfq->formattedQuantity();
                    @endphp

                    <div class="data-row xl:gap-3.5 {{ $columns }}">
                        <div class="flex min-w-0 items-start justify-between gap-3">
                            <a href="{{ $url }}" wire:navigate class="min-w-0 font-medium text-gray-900 hover:text-brand-500 xl:truncate">
                                {{ $rfq->title }}
                            </a>
                            <x-status-badge :status="$rfq->status" size="sm" class="xl:hidden" />
                        </div>

                        <span class="min-w-0 truncate text-[13px] text-gray-500" title="{{ $rfq->categoryPath() }}">{{ $rfq->categoryPath() ?? '—' }}</span>

                        <span class="hidden min-w-0 whitespace-nowrap tabular xl:block">
                            @if ($quantity)
                                <span dir="ltr">{{ $quantity }}</span> {{ $rfq->unit?->name }}
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </span>
                        <span class="hidden min-w-0 xl:block"><x-buyer.rfq-deadline :rfq="$rfq" /></span>
                        <span @class(["hidden font-semibold tabular xl:block", "text-gray-400" => $rfq->isDraft()])>{{ $rfq->isDraft() ? "—" : $rfq->quotes_count }}</span>
                        <span class="hidden xl:block"><x-status-badge :status="$rfq->status" size="sm" /></span>
                        <span class="hidden justify-end xl:flex">
                            <a href="{{ $url }}" wire:navigate class="link px-2 py-1 text-[13px] whitespace-nowrap">
                                {{ $rfq->isDraft() ? __('buyer.continue_editing') : __('common.view') }}
                            </a>
                        </span>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-[13px] text-gray-600 xl:hidden">
                            @if ($quantity)
                                <span class="flex items-center gap-1 tabular">
                                    <x-lucide name="package" :size="13" class="text-gray-400" />
                                    <span dir="ltr">{{ $quantity }}</span> {{ $rfq->unit?->name }}
                                </span>
                            @endif
                            @unless ($rfq->isDraft())
                                <span class="flex items-center gap-1 tabular">
                                    <x-lucide name="tag" :size="13" class="text-gray-400" />
                                    {{ trans_choice('rfq.quotes_count', $rfq->quotes_count, ['count' => $rfq->quotes_count]) }}
                                </span>
                            @endunless
                            <x-buyer.rfq-deadline :rfq="$rfq" />
                            <a href="{{ $url }}" wire:navigate class="link ms-auto inline-flex min-h-9 items-center">
                                {{ $rfq->isDraft() ? __('buyer.continue_editing') : __('common.view') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{ $rfqs->links() }}
        @endif
    </div>
</x-layouts.buyer>
