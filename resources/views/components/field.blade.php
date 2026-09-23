@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
    'errorKey' => null,
])

{{-- Labelled input with helper and validation message, bound to old() input. --}}
@php($errorKey ??= str_replace(['[', ']'], ['.', ''], $name))

<div>
    <label for="{{ $attributes->get('id', $name) }}" class="label">
        {{ $label }}
        @unless ($required)
            <span class="font-normal text-gray-400">{{ __('ui.optional') }}</span>
        @endunless
    </label>

    <input id="{{ $attributes->get('id', $name) }}"
           name="{{ $name }}"
           type="{{ $type }}"
           @if ($type !== 'password') value="{{ old($errorKey, $value) }}" @endif
           placeholder="{{ $placeholder }}"
           @required($required)
           @error($errorKey) aria-invalid="true" @enderror
           {{ $attributes->except('id')->class(['input', 'input-error' => $errors->has($errorKey)]) }}>

    @if ($hint)
        <p class="helper">{{ $hint }}</p>
    @endif

    @error($errorKey)
        <p class="error-text">{{ $message }}</p>
    @enderror
</div>
