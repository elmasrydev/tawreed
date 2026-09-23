@props(['media', 'removable' => false])

{{-- One uploaded file: type tile, name and size, downloadable when it has a URL. --}}
@php
    $extension = strtoupper(pathinfo($media->file_name, PATHINFO_EXTENSION) ?: 'FILE');
    $url = rescue(fn () => $media->getUrl(), null, false);
@endphp

<div {{ $attributes->class('flex min-w-0 items-center gap-2.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-[13px]') }}>
    <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-brand-100 text-[10px] font-bold text-brand-700">{{ mb_substr($extension, 0, 4) }}</span>
    <div class="min-w-0 flex-1">
        <p class="truncate font-medium text-gray-900" dir="auto">{{ $media->file_name }}</p>
        <p class="text-xs text-gray-500 tabular" dir="ltr">{{ $media->human_readable_size }}</p>
    </div>
    @if ($url && ! $removable)
        <a href="{{ $url }}" target="_blank" rel="noopener" download
           class="btn btn-ghost btn-icon btn-sm shrink-0" aria-label="{{ __('buyer.download_file', ['name' => $media->file_name]) }}">
            <x-lucide name="download" :size="15" />
        </a>
    @endif
    {{ $slot }}
</div>
