@props(['icon', 'label'])

{{-- A small labelled fact with an icon, used in request summaries. --}}
<div {{ $attributes->class('min-w-0') }}>
    <dt class="flex items-center gap-1 text-[11px] text-gray-500">
        <x-lucide :name="$icon" :size="12" />
        {{ $label }}
    </dt>
    <dd class="mt-0.5 text-sm font-semibold break-words text-gray-900">{{ $slot }}</dd>
</div>
