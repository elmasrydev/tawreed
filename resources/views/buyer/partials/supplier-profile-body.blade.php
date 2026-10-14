<div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
    <div class="flex min-w-0 flex-col gap-4">
        <section class="card flex flex-col gap-3 p-4 sm:p-5">
            <h2 class="card-title">
                <x-lucide name="building" :size="16" class="text-brand-500" />
                {{ __('buyer.about') }}
            </h2>
            @if ($profile->activity_description)
                <p class="text-sm leading-relaxed whitespace-pre-line text-gray-700">{{ $profile->activity_description }}</p>
            @endif

            @if ($profile->categories->isNotEmpty())
                <div class="flex flex-col gap-1.5">
                    <p class="eyebrow">{{ __('ui.categories') }}</p>
                    <ul class="flex flex-wrap gap-1.5">
                        @foreach ($profile->categories as $category)
                            <li class="rounded-md border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $category->name }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($profile->governorates->isNotEmpty())
                <div class="flex flex-col gap-1.5">
                    <p class="eyebrow">{{ __('ui.coverage_areas') }}</p>
                    <ul class="flex flex-wrap gap-1.5">
                        @foreach ($profile->governorates as $governorate)
                            <li class="rounded-md bg-brand-100 px-2.5 py-1 text-xs font-medium text-brand-700">{{ $governorate->name }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        @if ($profile->portfolioItems->isNotEmpty())
            <section class="card flex flex-col gap-3.5 p-4 sm:p-5">
                <h2 class="card-title">
                    <x-lucide name="image" :size="16" class="text-brand-500" />
                    {{ __('buyer.previous_work') }}
                </h2>
                @foreach ($profile->portfolioItems as $item)
                    @php($images = $item->getMedia('images'))
                    <article class="flex flex-col gap-2.5 border-t border-gray-100 pt-3.5 first-of-type:border-t-0 first-of-type:pt-0">
                        <div>
                            <h3 class="text-sm font-semibold break-words text-gray-900">{{ $item->title }}</h3>
                            @if ($item->client_type || $item->year)
                                <p class="text-xs text-gray-500">{{ collect([$item->client_type, $item->year])->filter()->implode(' · ') }}</p>
                            @endif
                        </div>
                        @if ($item->description)
                            <p class="text-[13px] leading-relaxed whitespace-pre-line text-gray-700">{{ $item->description }}</p>
                        @endif
                        @if ($images->isNotEmpty())
                            <ul class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                                @foreach ($images as $media)
                                    <li>
                                        <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" class="block aspect-[4/3] overflow-hidden rounded-lg bg-gray-100">
                                            <img src="{{ $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl() }}"
                                                 alt="{{ $item->title }}" class="size-full object-cover" loading="lazy">
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif

        @if ($deals->isNotEmpty())
            <section class="card flex flex-col gap-3.5 p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="card-title">
                        <x-lucide name="award" :size="16" class="text-success-500" />
                        {{ __('buyer.successful_deals') }}
                    </h2>
                    <span class="text-[13px] text-gray-500">{{ trans_choice('buyer.deals_count', $profile->completed_deals_count, ['count' => number_format($profile->completed_deals_count)]) }}</span>
                </div>

                @foreach ($deals as $deal)
                    @php($review = $dealReviews->get($deal->id))
                    <article class="flex flex-col gap-1.5 border-t border-gray-100 pt-3.5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                @if ($deal->categoryPath())
                                    <p class="eyebrow">{{ $deal->categoryPath() }}</p>
                                @endif
                                <h3 class="text-sm font-semibold break-words text-gray-900">{{ $deal->title }}</h3>
                            </div>
                            <x-badge tone="success" size="sm">{{ __('buyer.deal_awarded') }}</x-badge>
                        </div>
                        <p class="flex flex-wrap items-center gap-x-1.5 text-xs text-gray-500">
                            @if ($deal->formattedQuantity())
                                <span class="tabular">{{ $deal->formattedQuantity() }} {{ $deal->unit?->name }}</span>
                                <span aria-hidden="true">·</span>
                            @endif
                            <span>{{ $deal->anonymousBuyerLabel() }}</span>
                            @if ($deal->awarded_at)
                                <span aria-hidden="true">·</span>
                                <span class="tabular">{{ $deal->awarded_at->translatedFormat('M Y') }}</span>
                            @endif
                        </p>
                        @if ($review)
                            <div class="flex flex-col gap-1 rounded-md bg-gray-50 px-3 py-2">
                                <x-buyer.stars :rating="$review->rating" :size="13" />
                                @if ($review->body)
                                    <p class="text-[13px] leading-relaxed text-gray-700">{{ $review->body }}</p>
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif

        <section class="card flex flex-col gap-3.5 p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="card-title">
                    <x-lucide name="star" :size="16" class="text-accent-500" />
                    {{ __('buyer.reviews_from_buyers') }}
                </h2>
                @if ($profile->reviews_count > 0)
                    <div class="flex items-center gap-2">
                        <x-buyer.stars :rating="$profile->rating_avg" :size="16" />
                        <span class="text-base font-bold tabular">{{ number_format((float) $profile->rating_avg, 1) }}</span>
                        <span class="text-[13px] text-gray-500">· {{ trans_choice('buyer.reviews_count', $profile->reviews_count, ['count' => $profile->reviews_count]) }}</span>
                    </div>
                @endif
            </div>

            @forelse ($reviews as $review)
                <article class="flex flex-col gap-1.5 border-t border-gray-100 pt-3.5">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[13px]">
                        <span class="font-semibold text-gray-900">{{ $review->anonymousAuthorLabel() }}</span>
                        <span class="text-gray-400 tabular">{{ $review->created_at->translatedFormat('j M Y') }}</span>
                    </div>
                    <x-buyer.stars :rating="$review->rating" :size="13" />
                    @if ($review->body)
                        <p class="text-[13px] leading-relaxed text-gray-700">{{ $review->body }}</p>
                    @endif
                </article>
            @empty
                <p class="text-[13px] text-gray-500">{{ __('buyer.no_reviews') }}</p>
            @endforelse
        </section>
    </div>

    <aside class="flex flex-col gap-4">
        <section class="card grid grid-cols-2 gap-4 p-4 sm:p-5">
            @foreach ([
                ['icon' => 'award', 'tone' => 'text-success-500', 'label' => __('buyer.stat_completed_deals'), 'value' => number_format($profile->completed_deals_count)],
                ['icon' => 'star', 'tone' => 'text-accent-500', 'label' => __('buyer.stat_rating'), 'value' => $profile->reviews_count > 0 ? number_format((float) $profile->rating_avg, 1) : '—'],
                ['icon' => 'users', 'tone' => 'text-brand-500', 'label' => __('buyer.stat_reviews'), 'value' => number_format($profile->reviews_count)],
                ['icon' => 'calendar', 'tone' => 'text-brand-500', 'label' => $profile->years_active ? __('buyer.stat_years_active') : __('buyer.stat_member_since'), 'value' => $profile->years_active ?: $profile->created_at->year],
            ] as $stat)
                <div class="min-w-0">
                    <p class="flex items-center gap-1.5 text-[11px] font-semibold tracking-[0.5px] text-gray-500 uppercase">
                        <x-lucide :name="$stat['icon']" :size="13" :class="$stat['tone']" />
                        {{ $stat['label'] }}
                    </p>
                    <p class="mt-1 text-2xl font-bold text-brand-700 tabular">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </section>

        @if ($profile->payment_method)
            <section class="card flex flex-col gap-2.5 p-4 sm:p-5">
                <h2 class="eyebrow flex items-center gap-1.5 text-xs">
                    <x-lucide name="credit-card" :size="14" />
                    {{ __('ui.payment_method') }}
                </h2>
                <p class="self-start rounded-md border border-gray-300 px-2.5 py-1 text-[13px] text-gray-800">{{ $profile->payment_method }}</p>
            </section>
        @endif

        <div class="flex gap-2.5 rounded-card bg-gray-100 px-4 py-3.5 text-[13px] leading-relaxed text-gray-600">
            <x-lucide name="lock" :size="16" class="mt-0.5" />
            <span>{{ __('buyer.profile_privacy_note') }}</span>
        </div>
    </aside>
</div>
