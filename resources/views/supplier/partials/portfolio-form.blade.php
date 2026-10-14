{{--
    Add or edit one portfolio entry. $item is null when adding. Field ids are
    suffixed so several edit dialogs can sit on the same page.
--}}
@php
    $suffix = $item?->id ?? 'new';
    $images = $item?->getMedia('images') ?? collect();
    $isOldForm = old('portfolio_form') === (string) $suffix;
    $value = fn (string $field) => $isOldForm ? old($field) : $item?->{$field};
@endphp

<form method="POST" enctype="multipart/form-data"
      action="{{ $item ? route('supplier.portfolio.update', $item) : route('supplier.portfolio.store') }}"
      class="flex flex-col gap-4">
    @csrf
    @if ($item)
        @method('PUT')
    @endif
    <input type="hidden" name="portfolio_form" value="{{ $suffix }}">

    <h2 class="text-lg font-bold text-brand-700">{{ $item ? __('supplier.portfolio_edit') : __('supplier.portfolio_add') }}</h2>

    <x-field name="title" id="portfolio-title-{{ $suffix }}" :label="__('supplier.portfolio_title')" :value="$value('title')" required maxlength="150" />

    <div>
        <label for="portfolio-description-{{ $suffix }}" class="label">
            {{ __('supplier.portfolio_description') }}
            <span class="font-normal text-gray-400">{{ __('ui.optional') }}</span>
        </label>
        <textarea id="portfolio-description-{{ $suffix }}" name="description" rows="3" maxlength="1000"
                  @class(['input', 'input-error' => $isOldForm && $errors->has('description')])>{{ $value('description') }}</textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="client_type" id="portfolio-client-{{ $suffix }}" :label="__('supplier.portfolio_client_type')" :value="$value('client_type')"
                 :placeholder="__('supplier.portfolio_client_type_hint')" maxlength="120" />
        <x-field name="year" id="portfolio-year-{{ $suffix }}" type="number" :label="__('supplier.portfolio_year')" :value="$value('year')"
                 min="1950" max="{{ now()->year }}" inputmode="numeric" />
    </div>

    @if ($images->isNotEmpty())
        <fieldset class="flex flex-col gap-2">
            <legend class="label">{{ __('supplier.portfolio_current_images') }}</legend>
            <ul class="grid grid-cols-3 gap-2">
                @foreach ($images as $media)
                    <li>
                        <label class="group relative block aspect-[4/3] cursor-pointer overflow-hidden rounded-lg bg-gray-100">
                            <img src="{{ $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl() }}" alt="" class="size-full object-cover">
                            <span class="absolute inset-x-0 bottom-0 flex items-center gap-1.5 bg-gray-900/60 px-2 py-1 text-[11px] font-medium text-white">
                                <input type="checkbox" name="remove_images[]" value="{{ $media->id }}" class="accent-danger-500">
                                {{ __('supplier.portfolio_remove_image') }}
                            </span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </fieldset>
    @endif

    <div>
        <p class="label">
            {{ __('supplier.portfolio_images') }}
            @if ($item)
                <span class="font-normal text-gray-400">{{ __('ui.optional') }}</span>
            @endif
        </p>
        <x-file-drop name="images[]" id="portfolio-images-{{ $suffix }}" multiple accept="image/jpeg,image/png,image/webp"
                     :hint="__('supplier.portfolio_images_hint', ['count' => App\Models\PortfolioItem::MAX_IMAGES])" />
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <button type="button" x-on:click="$dispatch('close-modal', 'portfolio-{{ $suffix }}')" class="btn btn-secondary">{{ __('ui.cancel') }}</button>
        <button type="submit" class="btn btn-primary">
            <x-lucide name="save" :size="15" />
            {{ __('ui.save') }}
        </button>
    </div>
</form>
