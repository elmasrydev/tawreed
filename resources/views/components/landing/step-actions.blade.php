@props(['last' => false, 'first' => false, 'submitLabel' => null, 'backUrl' => null])

{{-- Back / Continue row at the bottom of each registration step. --}}
<div class="flex gap-2.5 pt-1">
    @if ($first && $backUrl)
        <a href="{{ $backUrl }}" class="btn btn-secondary h-[46px] rounded-card px-[18px] text-gray-700">{{ __('auth_ui.back') }}</a>
    @elseif (! $first)
        <button type="button" @click="back()" class="btn btn-secondary h-[46px] rounded-card px-[18px] text-gray-700">{{ __('auth_ui.back') }}</button>
    @endif

    @if ($last)
        <button type="submit" class="btn btn-accent h-[46px] flex-1 rounded-card text-[15px]">{{ $submitLabel }}</button>
    @else
        <button type="button" @click="next()" class="btn btn-primary h-[46px] flex-1 rounded-card text-[15px]">
            {{ __('auth_ui.continue') }}
            <x-lucide name="arrow-right" :size="16" :stroke="2.5" />
        </button>
    @endif
</div>
