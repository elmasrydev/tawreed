<section class="card grid gap-x-6 gap-y-5 p-4 sm:grid-cols-2 sm:p-7">
    <div>
        <label for="quantity" class="label">{{ __('ui.quantity') }}</label>
        <div @class(['flex h-10 overflow-hidden rounded-lg border bg-white focus-within:border-brand-500 focus-within:shadow-focus', 'border-danger-600' => $errors->hasAny(['quantity', 'unit_id']), 'border-gray-300' => ! $errors->hasAny(['quantity', 'unit_id'])])>
            <input id="quantity" type="number" step="any" min="0" wire:model="quantity" dir="ltr"
                   class="min-w-0 flex-1 border-0 bg-transparent px-3 text-sm tabular outline-none">
            <label for="unit_id" class="sr-only">{{ __('ui.unit') }}</label>
            <select id="unit_id" wire:model="unit_id"
                    class="max-w-[48%] border-0 border-s border-gray-200 bg-gray-50 ps-3 pe-8 text-sm outline-none">
                <option value="">{{ __('ui.unit') }}</option>
                @foreach ($this->units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>
        @error('quantity') <p class="error-text">{{ $message }}</p> @enderror
        @error('unit_id') <p class="error-text">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="delivery_date" class="label">{{ __('ui.delivery_date') }}</label>
        <input id="delivery_date" type="date" wire:model="delivery_date" min="{{ now()->addDay()->toDateString() }}"
               @class(['input tabular', 'input-error' => $errors->has('delivery_date')])>
        @error('delivery_date') <p class="error-text">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="governorate_id" class="label">{{ __('ui.delivery_governorate') }}</label>
        <select id="governorate_id" wire:model="governorate_id" @class(['input sm:max-w-[calc(50%-12px)]', 'input-error' => $errors->has('governorate_id')])>
            <option value="">{{ __('buyer.choose') }}</option>
            @foreach ($this->governorates as $governorate)
                <option value="{{ $governorate->id }}">{{ $governorate->name }}</option>
            @endforeach
        </select>
        @error('governorate_id') <p class="error-text">{{ $message }}</p> @enderror
        <x-alert tone="info" icon="lock" class="mt-2">{{ __('buyer.governorate_privacy') }}</x-alert>
    </div>

    <fieldset class="sm:col-span-2">
        <legend class="label">{{ __('ui.supply_type') }}</legend>
        <div class="flex flex-wrap gap-2">
            @foreach (App\Enums\SupplyType::cases() as $type)
                <label class="choice">
                    <input type="radio" wire:model.live="supply_type" value="{{ $type->value }}" class="sr-only">
                    @if ($type === App\Enums\SupplyType::Recurring)
                        <x-lucide name="repeat" :size="14" />
                    @endif
                    {{ $type->label() }}
                </label>
            @endforeach
        </div>
    </fieldset>

    @if ($supply_type === App\Enums\SupplyType::Recurring->value)
        <div class="sm:col-span-2">
            <label for="recurrence_note" class="label">{{ __('ui.recurrence_note') }}</label>
            <input id="recurrence_note" type="text" wire:model="recurrence_note" @class(['input', 'input-error' => $errors->has('recurrence_note')])
                   placeholder="{{ __('rfq.sample_recurrence') }}">
            @error('recurrence_note') <p class="error-text">{{ $message }}</p> @enderror
        </div>
    @endif
</section>
