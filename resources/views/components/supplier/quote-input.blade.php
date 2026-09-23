@props([
    'name',
    'label',
    'suffix' => null,
    'type' => 'text',
    'required' => false,
    'hint' => null,
    'hintTone' => 'muted',
])

{{--
    Quote form field bound to a Livewire property of the same name. Pass
    wire:model through the attributes; an optional suffix renders as an
    add-on (EGP, days, the RFQ unit).
--}}
@php($hasError = $errors->has($name))

<div {{ $attributes->only('class') }}>
    <label for="quote-{{ $name }}" class="label">
        {{ $label }}
        @unless ($required)
            <span class="font-normal text-gray-400">{{ __('ui.optional') }}</span>
        @endunless
    </label>

    @php($inputAttributes = $attributes->except('class')->merge(['id' => "quote-{$name}", 'type' => $type]))

    @if ($type === 'textarea')
        <textarea {{ $inputAttributes->except('type')->class(['input', 'input-error' => $hasError]) }}
                  @if ($hasError) aria-invalid="true" aria-describedby="quote-{{ $name }}-error" @endif></textarea>
    @elseif ($suffix)
        <div @class([
            'flex h-10 overflow-hidden rounded-lg border bg-white transition focus-within:border-brand-500 focus-within:shadow-focus',
            'border-gray-300' => ! $hasError,
            'border-danger-600' => $hasError,
        ])>
            <input {{ $inputAttributes->class('min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-gray-900 tabular placeholder:text-gray-400 focus:outline-none') }}
                   @if ($hasError) aria-invalid="true" aria-describedby="quote-{{ $name }}-error" @endif>
            <span class="flex shrink-0 items-center border-s border-gray-200 bg-gray-50 px-3 text-[13px] whitespace-nowrap text-gray-600">{{ $suffix }}</span>
        </div>
    @else
        <input {{ $inputAttributes->class(['input', 'tabular' => in_array($type, ['number', 'date'], true), 'input-error' => $hasError]) }}
               @if ($hasError) aria-invalid="true" aria-describedby="quote-{{ $name }}-error" @endif>
    @endif

    @if ($hint && ! $hasError)
        <p @class(['helper', 'text-success-700' => $hintTone === 'success'])>{{ $hint }}</p>
    @endif

    @error($name)
        <p id="quote-{{ $name }}-error" class="error-text">{{ $message }}</p>
    @enderror
</div>
