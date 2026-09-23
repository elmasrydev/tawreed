@php
    /**
     * Whole EGP amounts drop their decimals; fractional ones keep two.
     */
    $formatEgp = fn (float|string $amount): string => number_format((float) $amount, fmod((float) $amount, 1.0) === 0.0 ? 0 : 2);

    $navLinks = array_filter([
        '#how' => __('landing.nav_how'),
        '#buyers' => __('landing.nav_buyers'),
        '#suppliers' => __('landing.nav_suppliers'),
        '#pricing' => $plans->isNotEmpty() ? __('landing.nav_pricing') : null,
        '#faq' => __('landing.nav_faq'),
    ]);

    $steps = [
        ['icon' => 'icon-post-rfq.png', 'key' => 1],
        ['icon' => 'icon-verified-suppliers.png', 'key' => 2],
        ['icon' => 'icon-pick-quote.png', 'key' => 3],
        ['icon' => 'icon-open-chat.png', 'key' => 4],
    ];

    $planPrices = $plans
        ->map(fn ($plan): string => __('landing.faq_price_for', ['price' => $formatEgp($plan->price_egp), 'name' => $plan->name]))
        ->all();

    $faqs = [
        [__('landing.faq_1_q'), __('landing.faq_1_a')],
        [__('landing.faq_2_q'), __('landing.faq_2_a')],
        [__('landing.faq_3_q'), __('landing.faq_3_a')],
        [
            __('landing.faq_4_q'),
            $planPrices
                ? __('landing.faq_4_a_prices', ['prices' => implode(__('landing.faq_price_or'), $planPrices)])
                : __('landing.faq_4_a'),
        ],
        [__('landing.faq_5_q'), __('landing.faq_5_a')],
        [__('landing.faq_6_q'), __('landing.faq_6_a')],
    ];

    $supplierCtaHref = $plans->isNotEmpty() ? '#pricing' : route('register.supplier');
    $container = 'mx-auto w-full max-w-7xl px-4 sm:px-8 lg:px-16';
@endphp

<x-layouts.marketing :title="__('landing.page_title')">
    <x-landing.nav :links="$navLinks" />

    <main class="overflow-x-clip">
        {{-- Hero --}}
        <section class="{{ $container }} grid items-center gap-12 pt-10 pb-16 sm:pt-14 lg:grid-cols-[1.05fr_1fr] lg:gap-14 lg:pt-20 lg:pb-[72px]">
            <div class="flex flex-col gap-6 lg:gap-7">
                <span class="inline-flex min-h-[30px] items-center gap-2 self-start rounded-full bg-brand-100 px-3 py-1 text-[13px] font-semibold text-brand-700">
                    <img src="{{ asset('images/brand/icon-egypt-pin.png') }}" alt="" class="h-[18px] w-auto">
                    {{ __('landing.hero_badge') }}
                </span>

                <h1 class="text-[38px] leading-[1.1] font-extrabold tracking-[-1px] text-balance text-brand-700 sm:text-5xl lg:text-[56px] lg:leading-[1.08] lg:tracking-[-1.5px]">
                    {{ __('landing.hero_title') }}
                    <span class="text-brand-500">{{ __('landing.hero_title_highlight') }}</span>
                </h1>

                <p class="max-w-[520px] text-[17px] leading-relaxed text-pretty text-gray-600 lg:text-[19px]">
                    {{ __('landing.hero_body') }}
                </p>

                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="{{ route('register.buyer') }}" class="btn btn-accent h-[52px] rounded-card px-[26px] text-base">
                        <x-lucide name="file" :size="18" />
                        {{ __('landing.hero_cta_buyer') }}
                    </a>
                    <a href="{{ route('register.supplier') }}" class="btn h-[52px] rounded-card border-[1.5px] border-brand-700 bg-white px-[26px] text-base text-brand-700 hover:bg-brand-100">
                        <x-lucide name="package" :size="18" />
                        {{ __('landing.hero_cta_supplier') }}
                    </a>
                </div>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-[13px] text-gray-500">
                    <span class="inline-flex items-center gap-1.5">
                        <img src="{{ asset('images/brand/icon-verified-shield.png') }}" alt="" class="h-4 w-auto">
                        {{ __('landing.trust_verified') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <img src="{{ asset('images/brand/icon-zero-commission.png') }}" alt="" class="h-4 w-auto">
                        {{ __('landing.trust_zero_commission') }}
                    </span>
                    <span>{{ __('landing.trust_free_buyers') }}</span>
                </div>
            </div>

            <div class="relative">
                <img src="{{ asset('images/brand/hero-kitchen-delivery.jpg') }}" alt="{{ __('landing.hero_image_alt') }}"
                     class="block aspect-[5/4] w-full rounded-2xl object-cover object-[center_40%] shadow-hero">

                @if ($featuredRfq)
                    <div class="relative mx-3 -mt-14 flex flex-col gap-2.5 rounded-xl border border-gray-200 bg-white px-4 py-3.5 shadow-float sm:mx-6 sm:max-w-[320px] lg:absolute lg:-start-[22px] lg:-bottom-[22px] lg:m-0 lg:w-[300px]"
                         data-testid="featured-rfq">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex min-w-0 items-center gap-1.5 text-[11px] font-semibold text-gray-600">
                                <x-lucide name="lock" :size="11" />
                                <span class="truncate">{{ collect([$featuredRfq->buyerEntityType, $featuredRfq->governorate])->filter()->implode(' · ') }}</span>
                            </span>
                            <x-countdown-chip :until="$featuredRfqDeadline" size="sm" />
                        </div>
                        <p class="truncate text-sm leading-snug font-semibold text-gray-900">{{ $featuredRfq->title }}</p>
                        <div class="flex items-center justify-between gap-2 text-xs text-gray-500">
                            <span class="tabular"><b class="font-semibold text-gray-900" dir="ltr">{{ $featuredRfq->quantity }}</b> {{ $featuredRfq->unit }}</span>
                            <span class="font-semibold text-gray-900 tabular">{{ trans_choice('landing.card_quotes', $featuredRfq->quotesCount) }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- How it works --}}
        <section id="how" class="{{ $container }} flex scroll-mt-20 flex-col gap-10 py-16 lg:gap-12 lg:pt-24 lg:pb-20">
            <x-landing.section-heading :eyebrow="__('landing.how_eyebrow')" :title="__('landing.how_title')" />

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($steps as $step)
                    <div class="relative flex items-center gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 pe-12 sm:flex-col sm:px-5 sm:py-8 sm:text-center">
                        <span class="absolute end-3 top-3 flex size-7 items-center justify-center rounded-full bg-brand-700 text-[13px] font-bold text-white sm:start-4 sm:end-auto sm:top-4">{{ $step['key'] }}</span>
                        <div class="flex size-20 shrink-0 items-center justify-center sm:mt-2 sm:h-[104px] sm:w-full">
                            <img src="{{ asset('images/brand/'.$step['icon']) }}" alt="" class="h-auto max-h-16 w-auto max-w-20 sm:max-h-24 sm:max-w-[150px]">
                        </div>
                        <div class="flex flex-col gap-1.5 sm:gap-4">
                            <h3 class="text-[17px] leading-tight font-bold text-balance text-brand-700 sm:flex sm:items-center sm:justify-center sm:text-[19px] lg:min-h-12">
                                {{ __("landing.step_{$step['key']}_title") }}
                            </h3>
                            <p class="text-[13px] leading-normal text-gray-500 sm:text-sm">{{ __("landing.step_{$step['key']}_text") }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Benefits --}}
        <section id="buyers" class="{{ $container }} grid scroll-mt-20 gap-6 pb-16 lg:grid-cols-2 lg:pb-24">
            <div class="flex flex-col gap-6 rounded-[20px] bg-brand-100 p-6 sm:p-11">
                <span class="flex size-16 items-center justify-center self-start rounded-2xl bg-white">
                    <img src="{{ asset('images/brand/icon-pick-quote.png') }}" alt="" class="size-10 object-contain">
                </span>
                <div>
                    <span class="text-[13px] font-semibold tracking-[1.2px] text-brand-500 uppercase">{{ __('landing.buyers_eyebrow') }}</span>
                    <h3 class="mt-2 text-[26px] leading-tight font-bold tracking-[-0.6px] text-balance text-brand-700 sm:text-[30px]">{{ __('landing.buyers_title') }}</h3>
                </div>
                <ul class="flex flex-col gap-3.5 text-[15px] leading-normal text-gray-700">
                    @foreach ([1, 2, 3] as $point)
                        <x-landing.check-item :title="__('landing.buyers_point_'.$point.'_title')">{{ __('landing.buyers_point_'.$point.'_body') }}</x-landing.check-item>
                    @endforeach
                </ul>
                <a href="{{ route('register.buyer') }}" class="btn btn-primary mt-auto h-[46px] self-start rounded-card px-[22px] text-[15px] whitespace-normal">
                    {{ __('landing.buyers_cta') }}
                </a>
            </div>

            <div id="suppliers" class="flex scroll-mt-20 flex-col gap-6 rounded-[20px] bg-brand-700 p-6 text-white sm:p-11">
                <span class="flex size-16 items-center justify-center self-start rounded-2xl bg-white/12">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 21V10l5 3v-3l5 3V8l5-2.5V4h3v17z" fill="#fff"/>
                        <rect x="6.5" y="15" width="3" height="3" rx=".6" fill="#17A398"/>
                        <rect x="12.5" y="15" width="3" height="3" rx=".6" fill="#17A398"/>
                        <rect x="2" y="20" width="20" height="2" rx="1" fill="#17A398"/>
                    </svg>
                </span>
                <div>
                    <span class="text-[13px] font-semibold tracking-[1.2px] text-accent-500 uppercase">{{ __('landing.suppliers_eyebrow') }}</span>
                    <h3 class="mt-2 text-[26px] leading-tight font-bold tracking-[-0.6px] text-balance sm:text-[30px]">{{ __('landing.suppliers_title') }}</h3>
                </div>
                <ul class="flex flex-col gap-3.5 text-[15px] leading-normal text-white/95">
                    <x-landing.check-item tone="accent" :title="__('landing.suppliers_point_1_title')">{{ __('landing.suppliers_point_1_body') }}</x-landing.check-item>
                    <x-landing.check-item tone="accent" :title="__('landing.suppliers_point_2_title')">
                        @if ($cheapestPlan)
                            {{ __('landing.suppliers_point_2_body_from', ['price' => $formatEgp($cheapestPlan->price_egp)]) }}
                        @else
                            {{ __('landing.suppliers_point_2_body') }}
                        @endif
                    </x-landing.check-item>
                    <x-landing.check-item tone="accent" :title="__('landing.suppliers_point_3_title')">{{ __('landing.suppliers_point_3_body') }}</x-landing.check-item>
                </ul>
                <a href="{{ $supplierCtaHref }}" class="btn btn-accent mt-auto h-[46px] self-start rounded-card px-[22px] text-[15px] whitespace-normal">
                    {{ __('landing.suppliers_cta') }}
                </a>
            </div>
        </section>

        {{-- Categories --}}
        @if ($categories->isNotEmpty())
            <section class="{{ $container }} flex flex-col gap-8 pb-16 lg:pb-24" data-testid="landing-categories">
                <x-landing.section-heading :eyebrow="__('landing.categories_eyebrow')" :title="__('landing.categories_title')" align="start" size="md" />

                <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    @foreach ($categories as $category)
                        <a href="{{ route('register.buyer') }}"
                           class="group flex flex-col overflow-hidden rounded-[14px] border border-gray-200 bg-white text-gray-900 transition hover:border-brand-500 hover:shadow-card-hover">
                            <div class="relative aspect-video bg-gray-100">
                                @if ($category->image_url)
                                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy" class="absolute inset-0 size-full object-cover">
                                @else
                                    <div class="placeholder-stripes absolute inset-0"></div>
                                @endif
                            </div>
                            <div class="flex items-center justify-between gap-2 px-3 py-3 sm:px-4 sm:py-3.5">
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold sm:text-[15px]">{{ $category->name }}</h3>
                                    <p class="text-xs text-gray-500 tabular">{{ trans_choice('landing.category_open_rfqs', $category->open_rfqs_count) }}</p>
                                </div>
                                <x-lucide name="chevron-right" :size="16" class="text-gray-400 transition group-hover:text-brand-500" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Pricing --}}
        @if ($plans->isNotEmpty())
            <section id="pricing" class="scroll-mt-16 border-y border-gray-200 bg-gray-50">
                <div class="{{ $container }} flex flex-col items-center gap-10 py-16 lg:gap-11 lg:py-24">
                    <x-landing.section-heading :eyebrow="__('landing.pricing_eyebrow')" :title="__('landing.pricing_title')" class="max-w-[640px] gap-3">
                        <p class="text-base leading-normal text-gray-600 sm:text-[17px]">{{ __('landing.pricing_body') }}</p>
                    </x-landing.section-heading>

                    <div class="flex w-full flex-wrap items-stretch justify-center gap-5">
                        @foreach ($plans as $plan)
                            <div @class([
                                'relative flex w-full flex-col gap-[18px] rounded-2xl bg-white px-7 py-8 sm:w-[300px]',
                                'border-2 border-accent-500 shadow-plan' => $plan->is_best_value,
                                'border border-gray-200' => ! $plan->is_best_value,
                            ]) data-plan="{{ $plan->slug }}" @if ($plan->is_best_value) data-best-value @endif>
                                @if ($plan->is_best_value)
                                    <span class="absolute start-6 -top-[13px] rounded-[5px] bg-success-500 px-2.5 py-1 text-[11px] font-bold tracking-[0.5px] text-white uppercase">
                                        {{ __('landing.best_value') }}
                                    </span>
                                @endif

                                <div>
                                    <h3 class="text-sm font-semibold text-gray-500">{{ $plan->name }}</h3>
                                    <div class="mt-1.5 flex items-baseline gap-1.5">
                                        <span class="text-[40px] leading-none font-extrabold tracking-[-1px] text-brand-700 tabular">{{ $formatEgp($plan->price_egp) }}</span>
                                        <span class="text-[15px] text-gray-500">{{ __('common.egp') }}</span>
                                    </div>
                                    <p class="mt-1.5 text-[13px] text-gray-400 tabular">
                                        {{ __('landing.per_day', ['amount' => rtrim(rtrim(number_format((float) $plan->price_egp / max(1, $plan->days), 1), '0'), '.')]) }}
                                        · {{ trans_choice('landing.plan_days', $plan->days) }}
                                    </p>
                                    @if ($plan->description)
                                        <p class="mt-2 text-[13px] leading-snug text-gray-600">{{ $plan->description }}</p>
                                    @endif
                                </div>

                                <ul class="flex flex-col gap-2.5 text-sm text-gray-700">
                                    @foreach (['quotes', 'chat', 'details', 'commission'] as $feature)
                                        <li class="flex items-center gap-2">
                                            <x-lucide name="check" :size="14" :stroke="3" class="text-success-500" />
                                            {{ __('landing.plan_feature_'.$feature) }}
                                        </li>
                                    @endforeach
                                    @if ($plan->trial_days > 0)
                                        <li class="flex items-center gap-2 font-semibold text-success-700">
                                            <x-lucide name="zap" :size="14" />
                                            {{ __('landing.plan_trial', ['days' => $plan->trial_days]) }}
                                        </li>
                                    @endif
                                </ul>

                                <a href="{{ route('register.supplier') }}" @class([
                                    'btn mt-auto h-[46px] rounded-card text-[15px]',
                                    'btn-accent' => $plan->is_best_value,
                                    'border-[1.5px] border-brand-700 bg-white text-brand-700 hover:bg-brand-100' => ! $plan->is_best_value,
                                ])>{{ __('landing.plan_cta', ['name' => $plan->name]) }}</a>
                            </div>
                        @endforeach
                    </div>

                    <p class="flex items-center gap-2 text-center text-[13px] text-gray-500">
                        <img src="{{ asset('images/brand/icon-zero-commission.png') }}" alt="" class="h-[18px] w-auto">
                        {{ __('landing.pricing_note') }}
                    </p>
                </div>
            </section>
        @endif

        {{-- Testimonials (admin-managed; hidden until one is published) --}}
        @if ($testimonials->isNotEmpty())
            <section class="{{ $container }} flex flex-col gap-10 py-16 lg:py-24" data-testid="landing-testimonials">
                <x-landing.section-heading :eyebrow="__('landing.testimonials_eyebrow')" :title="__('landing.testimonials_title')" size="md" />

                <div @class(['grid gap-5', 'md:grid-cols-2' => $testimonials->count() === 2, 'md:grid-cols-3' => $testimonials->count() >= 3, 'mx-auto max-w-xl' => $testimonials->count() === 1])>
                    @foreach ($testimonials as $testimonial)
                        <figure class="flex flex-col gap-[18px] rounded-2xl border border-gray-200 bg-white p-6 sm:p-7">
                            @if ($testimonial->rating)
                                <span class="text-base tracking-[2px] text-accent-500" aria-label="{{ $testimonial->rating }}/5">
                                    {{ str_repeat('★', $testimonial->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $testimonial->rating) }}</span>
                                </span>
                            @endif
                            <blockquote class="flex-1 text-base leading-relaxed text-gray-900">“{{ $testimonial->quote }}”</blockquote>
                            <figcaption class="flex items-center gap-3 border-t border-gray-100 pt-4">
                                <x-avatar :name="$testimonial->author_name" :size="40" :tone="['light', 'navy', 'teal'][$loop->index % 3]" />
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900">{{ $testimonial->author_name }}</div>
                                    @php($caption = collect([$testimonial->author_role, $testimonial->location])->filter()->implode(' · '))
                                    @if ($caption)
                                        <div class="text-xs text-gray-500">{{ $caption }}</div>
                                    @endif
                                </div>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- FAQ --}}
        <section id="faq" @class([
            $container,
            'grid scroll-mt-20 items-start gap-8 pb-16 lg:grid-cols-[380px_minmax(0,1fr)] lg:gap-16 lg:pb-24',
            'pt-16 lg:pt-24' => $testimonials->isEmpty(),
        ])>
            <div class="flex flex-col gap-3.5 lg:sticky lg:top-24">
                <x-landing.section-heading :eyebrow="__('landing.faq_eyebrow')" :title="__('landing.faq_title')" align="start" size="md" />
                <p class="text-[15px] leading-normal text-gray-500">{{ __('landing.faq_body') }}</p>
            </div>

            <div x-data="{ open: 0 }" class="flex flex-col border-t border-gray-200">
                @foreach ($faqs as [$question, $answer])
                    <div class="border-b border-gray-200">
                        <button type="button" @click="open = open === {{ $loop->index }} ? -1 : {{ $loop->index }}"
                                :aria-expanded="(open === {{ $loop->index }}).toString()"
                                aria-controls="faq-{{ $loop->index }}"
                                class="flex w-full cursor-pointer items-center justify-between gap-5 py-5 text-start lg:py-[22px]">
                            <span class="text-base font-semibold text-brand-700 sm:text-[17px]">{{ $question }}</span>
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full border border-gray-300 text-brand-700 transition-transform duration-200"
                                  :class="open === {{ $loop->index }} && 'rotate-180'">
                                <x-lucide name="chevron-down" :size="14" />
                            </span>
                        </button>
                        <div id="faq-{{ $loop->index }}" x-show="open === {{ $loop->index }}" x-collapse
                             @if (! $loop->first) style="display: none" @endif>
                            <p class="pe-0 pb-[22px] text-[15px] leading-[1.65] text-gray-600 sm:pe-12">{{ $answer }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Final CTA --}}
        <section id="cta" class="{{ $container }}">
            <div class="grid items-center gap-8 rounded-3xl bg-brand-700 p-7 text-white sm:p-12 lg:grid-cols-[auto_minmax(0,1fr)_auto] lg:gap-10 lg:p-16">
                <span class="hidden size-[104px] items-center justify-center rounded-3xl bg-white/12 sm:flex">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" fill="#fff"/>
                        <rect x="7" y="8" width="10" height="1.8" rx=".9" fill="#17A398"/>
                        <rect x="7" y="12" width="6" height="1.8" rx=".9" fill="#17A398"/>
                        <circle cx="17.5" cy="16.5" r="4.5" fill="#17A398"/>
                        <path d="M15.4 16.5l1.5 1.5 2.7-2.9" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <div class="flex flex-col gap-4">
                    <h2 class="max-w-[480px] text-[28px] leading-tight font-bold tracking-[-0.8px] text-balance sm:text-[34px]">{{ __('landing.cta_title') }}</h2>
                    <p class="max-w-[460px] text-base leading-normal text-white/85">{{ __('landing.cta_body') }}</p>
                </div>
                <div class="flex flex-col gap-3">
                    <a href="{{ route('register.buyer') }}" class="btn btn-accent h-[52px] rounded-card px-7 text-base lg:min-w-[280px]">{{ __('landing.cta_buyer') }}</a>
                    <a href="{{ route('register.supplier') }}" class="btn h-[52px] rounded-card border-[1.5px] border-white/50 px-7 text-base text-white hover:bg-white/10 lg:min-w-[280px]">{{ __('landing.cta_supplier') }}</a>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="{{ $container }} flex flex-col gap-10 pt-16 pb-10">
        <div class="flex flex-col justify-between gap-10 sm:flex-row">
            <div class="flex max-w-[320px] flex-col gap-3.5">
                <x-brand-logo class="h-9 self-start" />
                <p class="text-sm leading-relaxed text-gray-500">{{ __('landing.footer_blurb') }}</p>
                <x-language-toggle class="self-start" />
            </div>
            <nav class="flex flex-col gap-2.5 text-sm sm:min-w-[180px]" aria-label="{{ __('landing.footer_product') }}">
                <span class="mb-1 font-bold text-brand-700">{{ __('landing.footer_product') }}</span>
                @foreach ($navLinks as $href => $label)
                    @continue($href === '#faq')
                    <a href="{{ $href }}" class="text-gray-600 hover:text-brand-700">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
        <div class="flex flex-col gap-3 border-t border-gray-200 pt-5 text-[13px] text-gray-400 sm:flex-row sm:items-center sm:justify-between">
            <span>{{ __('landing.footer_copyright', ['year' => now()->year]) }}</span>
            <div class="flex items-center gap-4">
                <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-400 underline-offset-2 hover:text-gray-600 hover:underline">{{ __('landing.admin_login') }}</a>
                <span class="inline-flex items-center gap-1.5">
                    <img src="{{ asset('images/brand/icon-egypt-pin.png') }}" alt="" class="h-3.5 w-auto">
                    {{ __('landing.made_in_egypt') }}
                </span>
            </div>
        </div>
    </footer>
</x-layouts.marketing>
