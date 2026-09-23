@props(['name' => null, 'multiple' => false, 'accept' => null, 'hint' => null, 'id' => null])

{{--
    Dashed drop zone around a real file input, so it works in plain forms and
    with wire:model. Selected file names are listed underneath.
--}}
@php($id ??= 'file-'.Str::random(6))

<div x-data="{ files: [] }">
    <label for="{{ $id }}"
           x-on:dragover.prevent="$el.classList.add('border-brand-500')"
           x-on:dragleave.prevent="$el.classList.remove('border-brand-500')"
           x-on:drop.prevent="$el.classList.remove('border-brand-500'); $refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change', { bubbles: true }))"
           class="flex cursor-pointer flex-col items-center gap-1 rounded-lg border-[1.5px] border-dashed border-gray-400 bg-gray-50 px-4 py-5 text-center transition hover:border-brand-500">
        <x-lucide name="upload" :size="20" class="text-gray-500" />
        <span class="text-sm font-semibold text-brand-700">{{ __('common.drop_files') }}</span>
        @if ($hint)
            <span class="text-xs text-gray-500">{{ $hint }}</span>
        @endif
        <input id="{{ $id }}" x-ref="input" type="file" class="sr-only"
               @if ($name) name="{{ $name }}" @endif
               @if ($multiple) multiple @endif
               @if ($accept) accept="{{ $accept }}" @endif
               x-on:change="files = Array.from($event.target.files).map(f => f.name)"
               {{ $attributes }}>
    </label>

    <ul x-show="files.length" x-cloak class="mt-2 flex flex-wrap gap-2">
        <template x-for="file in files" :key="file">
            <li class="flex max-w-full items-center gap-2 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-700">
                <x-lucide name="file" :size="14" class="text-brand-500" />
                <span class="truncate" x-text="file"></span>
            </li>
        </template>
    </ul>
</div>
