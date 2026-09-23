@props(['name' => 'phone', 'label' => null, 'value' => null, 'required' => true, 'placeholder' => '10x xxx xxxx', 'hint' => null])

{{-- Egyptian mobile field. Always left-to-right, with the +20 country prefix. --}}
<div>
    @if ($label)
        <label for="{{ $name }}" class="label">
            {{ $label }}
            @unless ($required)
                <span class="font-normal text-gray-400">{{ __('ui.optional') }}</span>
            @endunless
        </label>
    @endif

    <div dir="ltr" @class([
        'flex h-10 overflow-hidden rounded-lg border bg-white transition focus-within:border-brand-500 focus-within:shadow-focus',
        'border-danger-600' => $errors->has($name),
        'border-gray-300' => ! $errors->has($name),
    ])>
        <span class="flex items-center gap-1.5 border-r border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 tabular">
            <img src="{{ asset('images/brand/icon-egypt-pin.png') }}" alt="" class="h-4 w-auto">
            +20
        </span>
        <input id="{{ $name }}" name="{{ $name }}" type="tel" inputmode="tel" dir="ltr"
               value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}" @required($required)
               {{ $attributes->class('min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-gray-900 tabular placeholder:text-gray-400 focus:outline-none') }}>
    </div>

    @if ($hint)
        <p class="helper">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="error-text">{{ $message }}</p>
    @enderror
</div>
