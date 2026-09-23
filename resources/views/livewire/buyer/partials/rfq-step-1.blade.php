<section class="card grid gap-x-6 gap-y-5 p-4 sm:grid-cols-2 sm:p-7">
    <div class="sm:col-span-2">
        <label for="title" class="label">{{ __('ui.product_name') }}</label>
        <input id="title" type="text" wire:model="title" @class(['input', 'input-error' => $errors->has('title')])
               placeholder="{{ __('rfq.sample_product') }}">
        <p class="helper">{{ __('buyer.product_name_hint') }}</p>
        @error('title') <p class="error-text">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="category_id" class="label">{{ __('ui.main_category') }}</label>
        <select id="category_id" wire:model.live="category_id" @class(['input', 'input-error' => $errors->has('category_id')])>
            <option value="">{{ __('buyer.choose') }}</option>
            @foreach ($this->mainCategories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <p class="error-text">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="subcategory_id" class="label">
            {{ __('ui.sub_category') }} <span class="font-normal text-gray-400">{{ __('ui.optional') }}</span>
        </label>
        <select id="subcategory_id" wire:model="subcategory_id" @disabled($this->subcategories->isEmpty())
                @class(['input disabled:bg-gray-50 disabled:text-gray-400', 'input-error' => $errors->has('subcategory_id')])>
            <option value="">—</option>
            @foreach ($this->subcategories as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
            @endforeach
        </select>
        @error('subcategory_id') <p class="error-text">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="specs" class="label">{{ __('ui.specs') }}</label>
        <textarea id="specs" wire:model="specs" rows="6" @class(['input min-h-32 py-2.5 leading-relaxed', 'input-error' => $errors->has('specs')])
                  placeholder="{{ __('rfq.sample_specs') }}"></textarea>
        @error('specs') <p class="error-text">{{ $message }}</p> @enderror
    </div>
</section>
