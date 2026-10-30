<x-landing.public-page :title="__('landing.categories_page_title')" :categories="$menuCategories">
    <div class="flex flex-col gap-2">
        <h1 class="text-[28px] leading-tight font-bold tracking-[-0.6px] text-brand-700 sm:text-[34px]">{{ __('landing.categories_page_title') }}</h1>
        <p class="text-[15px] text-gray-600">{{ __('landing.categories_page_body') }}</p>
    </div>

    @if ($categories->isEmpty())
        <x-empty-state icon="folder" :title="__('landing.categories_empty')" />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $category)
                <article class="flex flex-col overflow-hidden rounded-[14px] border border-gray-200 bg-white" data-testid="category-card">
                    <a href="{{ route('rfqs.index', ['category' => $category->slug]) }}" class="group flex flex-col">
                        <div class="relative aspect-video bg-gray-100">
                            @if ($category->image_url)
                                <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy" class="absolute inset-0 size-full object-cover">
                            @else
                                <div class="placeholder-stripes absolute inset-0"></div>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-2 px-4 pt-3.5">
                            <div class="min-w-0">
                                <h2 class="truncate text-[15px] font-semibold text-gray-900 group-hover:text-brand-700">{{ $category->name }}</h2>
                                <p class="text-xs text-gray-500 tabular">{{ trans_choice('landing.category_open_rfqs', $category->open_rfqs_count) }}</p>
                            </div>
                            <x-lucide name="chevron-right" :size="16" class="text-gray-400 transition group-hover:text-brand-500" />
                        </div>
                    </a>

                    @if ($category->children->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5 px-4 pt-3 pb-4">
                            @foreach ($category->children as $child)
                                <a href="{{ route('rfqs.index', ['category' => $child->slug]) }}"
                                   class="rounded-full border border-gray-200 px-2.5 py-1 text-xs text-gray-600 hover:border-brand-500 hover:text-brand-700">
                                    {{ $child->name }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="pb-4"></div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</x-landing.public-page>
