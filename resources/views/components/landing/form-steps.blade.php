@props(['steps'])

{{--
    Client-side progress for the registration forms. The shared stepper is
    rendered once per step and Alpine shows the one matching `step`, so the
    markup stays identical to the design-system component.
--}}
<div {{ $attributes }}>
    @foreach ($steps as $index => $label)
        <div x-show="step === {{ $index + 1 }}" @if ($index > 0) style="display: none" @endif>
            <x-stepper :steps="$steps" :current="$index + 1" />
        </div>
    @endforeach
</div>
