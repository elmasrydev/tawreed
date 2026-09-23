<x-layouts.supplier current="feed" :title="__('ui.rfq_feed')">
    @php
        $activeFilters = collect([request('q'), request('category'), request('governorate')])->filter()->count();
        $blockerHint = match ($quoteBlocker) {
            'phone' => __('supplier.gate_phone'),
            'verification' => __('supplier.gate_review'),
            default => null,
        };
    @endphp

    <div class="shell-page">
        <div class="grid items-start gap-4 lg:grid-cols-[220px_minmax(0,1fr)] lg:grid-rows-[auto_1fr] lg:gap-x-8" x-data="{ filtersOpen: false }">
            <div class="flex flex-wrap items-end justify-between gap-3 lg:col-start-2 lg:row-start-1">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold tracking-[-0.3px] text-brand-700 sm:text-2xl">{{ __('ui.rfq_feed') }}</h1>
                    <p class="mt-0.5 text-[13px] text-gray-500">
                        {{ trans_choice('supplier.feed_count', $rfqs->total(), ['count' => number_format($rfqs->total())]) }}
                    </p>
                </div>

                <button type="button" class="btn btn-secondary lg:hidden" x-on:click="filtersOpen = ! filtersOpen"
                        x-bind:aria-expanded="filtersOpen" aria-controls="feed-filters">
                    <x-lucide name="filter" :size="16" />
                    {{ __('supplier.filters') }}
                    @if ($activeFilters > 0)
                        <span class="rounded-full bg-brand-500 px-1.5 text-[11px] leading-[18px] text-white tabular">{{ $activeFilters }}</span>
                    @endif
                </button>
            </div>

            {{-- Filters: a disclosure on phones and tablets, a fixed column on desktop --}}
            <aside class="lg:col-start-1 lg:row-span-2 lg:row-start-1">
                <form method="GET" action="{{ route('supplier.feed') }}" id="feed-filters"
                      class="hidden flex-col gap-5 rounded-card border border-gray-200 bg-white p-4 lg:flex lg:border-0 lg:bg-transparent lg:p-0 lg:pt-1.5"
                      x-bind:class="{ 'max-lg:flex': filtersOpen }">
                    <div class="flex items-center justify-between">
                        <span class="text-[15px] font-semibold text-gray-900">{{ __('supplier.filters') }}</span>
                        @if ($activeFilters > 0)
                            <a href="{{ route('supplier.feed') }}" wire:navigate class="link text-xs">{{ __('supplier.reset') }}</a>
                        @endif
                    </div>

                    <div>
                        <label for="feed-q" class="mb-2 block text-xs font-semibold text-gray-700">{{ __('supplier.search') }}</label>
                        <div class="relative">
                            <x-lucide name="search" :size="16" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input id="feed-q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('ui.search_rfqs') }}"
                                   class="input ps-9">
                        </div>
                    </div>

                    <fieldset>
                        <legend class="mb-2 text-xs font-semibold text-gray-700">{{ __('ui.categories') }}</legend>
                        <div class="flex flex-col gap-0.5 text-[13px]">
                            @foreach ([null => __('ui.all_categories')] + $categories->pluck('name', 'id')->all() as $id => $name)
                                @php($checked = (string) request('category') === (string) $id)
                                <label class="flex min-h-9 cursor-pointer items-center gap-2 rounded-md px-1.5 hover:bg-gray-100 lg:min-h-8">
                                    <input type="radio" name="category" value="{{ $id }}" @checked($checked)
                                           x-on:change="$el.form.requestSubmit()"
                                           class="size-4 shrink-0 accent-brand-500">
                                    <span @class(['min-w-0 flex-1 truncate', 'font-semibold text-brand-700' => $checked, 'text-gray-700' => ! $checked])>{{ $name }}</span>
                                    @if ($id)
                                        <span class="text-gray-400 tabular">{{ $categoryCounts[$id] ?? 0 }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div>
                        <label for="feed-governorate" class="mb-2 block text-xs font-semibold text-gray-700">{{ __('ui.governorate') }}</label>
                        <select id="feed-governorate" name="governorate" class="input" x-on:change="$el.form.requestSubmit()">
                            <option value="">{{ __('ui.all_governorates') }}</option>
                            @foreach ($governorates as $governorate)
                                <option value="{{ $governorate->id }}" @selected((string) request('governorate') === (string) $governorate->id)>
                                    {{ $governorate->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">{{ __('supplier.apply_filters') }}</button>
                </form>
            </aside>

            {{-- Results --}}
            <section class="flex min-w-0 flex-col gap-4 lg:col-start-2 lg:row-start-2">
                @if ($quoteBlocker === 'phone')
                    <x-alert tone="neutral" icon="lock">
                        {{ $blockerHint }}
                        <x-slot:action>
                            <a href="{{ route('phone.verify') }}" class="link text-[13px] whitespace-nowrap">{{ __('supplier.gate_phone_action') }}</a>
                        </x-slot:action>
                    </x-alert>
                @endif

                @if ($items->isEmpty())
                    <x-empty-state icon="search" :title="__('ui.no_rfqs')" :body="__('ui.no_rfqs_body')">
                        @if ($activeFilters > 0)
                            <a href="{{ route('supplier.feed') }}" wire:navigate class="btn btn-secondary btn-sm">{{ __('supplier.reset_filters') }}</a>
                        @endif
                    </x-empty-state>
                @else
                    <div class="flex flex-col gap-2.5">
                        @foreach ($items as $item)
                            <x-supplier.feed-row :item="$item" :blocker-hint="$blockerHint" />
                        @endforeach
                    </div>

                    {{ $rfqs->links() }}
                @endif
            </section>
        </div>
    </div>
</x-layouts.supplier>
