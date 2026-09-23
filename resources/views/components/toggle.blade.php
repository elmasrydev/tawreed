@props(['checked' => false, 'name' => null, 'label' => null])

{{-- Switch control (34×20). Works as a form checkbox or with wire:model. --}}
<label class="relative inline-flex shrink-0 cursor-pointer items-center gap-2.5">
    <input type="checkbox" @if ($name) name="{{ $name }}" @endif value="1" @checked($checked)
           {{ $attributes->class('peer sr-only') }}>
    <span class="h-5 w-[34px] rounded-full bg-gray-300 transition-colors peer-checked:bg-success-500 peer-focus-visible:shadow-focus"></span>
    <span class="absolute start-0.5 top-0.5 size-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-3.5 rtl:peer-checked:-translate-x-3.5"></span>
    @if ($label)
        <span class="text-sm text-gray-700">{{ $label }}</span>
    @endif
</label>
