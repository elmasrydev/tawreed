@php
    $hasFilters = $term !== '' || $category;
    $selectedSlug = $category?->slug;
@endphp

<x-landing.public-page :title="$category?->name ?? __('landing.rfqs_title')" :categories="$menuCategories">
    <div class="flex flex-col gap-2">
        <h1 class="text-[28px] leading-tight font-bold tracking-[-0.6px] text-brand-700 sm:text-[34px]">
            {{ $category?->name ?? __('landing.rfqs_title') }}
        </h1>
        <p class="text-[15px] text-gray-600">{{ trans_choice('landing.rfqs_count', $rfqs->total(), ['count' => $rfqs->total()]) }}</p>
    </div>

    <form method="GET" action="{{ route('rfqs.index') }}" role="search"
          class="grid gap-2.5 rounded-card border border-gray-200 bg-gray-50 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,240px)_auto] sm:items-center sm:gap-3">
        <label for="rfqs-q" class="sr-only">{{ __('landing.search_label') }}</label>
        <div class="relative min-w-0">
            <x-lucide name="search" :size="16" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input id="rfqs-q" type="search" name="q" value="{{ $term }}" placeholder="{{ __('landing.search_placeholder') }}" maxlength="100"
                   class="input w-full bg-white ps-9">
        </div>

        <label for="rfqs-category" class="sr-only">{{ __('ui.categories') }}</label>
        <select id="rfqs-category" name="category" class="input bg-white" x-data x-on:change="$el.form.requestSubmit()">
            <option value="">{{ __('landing.nav_all_categories') }}</option>
            @foreach ($categories as $option)
                <option value="{{ $option->slug }}" @selected($selectedSlug === $option->slug)>{{ $option->name }} ({{ $option->open_rfqs_count }})</option>
            @endforeach
            @if ($category && ! $category->isMain())
                <option value="{{ $category->slug }}" selected>{{ $category->name }}</option>
            @endif
        </select>

        <div class="flex items-center gap-2">
            <button type="submit" class="btn btn-primary flex-1 sm:flex-none">{{ __('landing.search_button') }}</button>
            @if ($hasFilters)
                <a href="{{ route('rfqs.index') }}" class="btn btn-ghost">{{ __('landing.rfqs_clear') }}</a>
            @endif
        </div>
    </form>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="flex min-w-0 flex-col gap-3">
            @forelse ($items as $item)
                <x-landing.rfq-row :item="$item" />
            @empty
                <x-empty-state icon="search" :title="__('landing.rfqs_empty_title')" :body="__('landing.rfqs_empty_body')">
                    @if ($hasFilters)
                        <a href="{{ route('rfqs.index') }}" class="btn btn-secondary btn-sm">{{ __('landing.rfqs_clear') }}</a>
                    @endif
                </x-empty-state>
            @endforelse

            {{ $rfqs->links() }}
        </div>

        <aside class="flex flex-col gap-4 rounded-card bg-brand-700 p-5 text-white lg:sticky lg:top-24">
            <h2 class="text-lg leading-snug font-bold">{{ __('landing.rfqs_supplier_title') }}</h2>
            <p class="text-sm leading-relaxed text-white/85">{{ __('landing.rfqs_supplier_body') }}</p>
            <a href="{{ route('register.supplier') }}" class="btn btn-accent">{{ __('landing.hero_cta_supplier') }}</a>
            <a href="{{ route('login') }}" class="text-center text-sm font-semibold text-white/85 hover:text-white">{{ __('landing.log_in') }}</a>
        </aside>
    </div>
</x-landing.public-page>
