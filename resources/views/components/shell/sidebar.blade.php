@props(['nav', 'current', 'primaryAction' => null])

{{-- Navy navigation rail shared by the desktop sidebar and the mobile drawer. --}}
<div class="flex h-full flex-col gap-1 bg-brand-700 px-3 py-4 text-white">
    <a href="{{ route(auth()->user()->role->homeRoute()) }}" wire:navigate class="mb-4 flex h-10 items-center px-2">
        <x-brand-logo variant="white" class="h-9" />
    </a>

    <nav class="flex flex-col gap-1" aria-label="{{ __('common.main_navigation') }}">
        @foreach ($nav as $item)
            @php($active = in_array($current, (array) ($item['matches'] ?? $item['key']), true))
            <a href="{{ $item['url'] }}" wire:navigate
               @if ($active) aria-current="page" @endif
               @class([
                   'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors hover:bg-white/15',
                   'bg-white/15 font-semibold text-white' => $active,
                   'font-medium text-white/75 hover:text-white' => ! $active,
               ])>
                <x-lucide :name="$item['icon']" :size="18" />
                <span class="flex-1 truncate">{{ $item['label'] }}</span>
                @if (! empty($item['badge']))
                    <span @class([
                        'rounded-full px-[7px] py-px text-xs font-bold tabular',
                        'bg-accent-500 text-white' => ($item['badgeTone'] ?? 'accent') === 'accent',
                        'bg-white/15 text-white' => ($item['badgeTone'] ?? 'accent') === 'muted',
                    ])>{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="mt-auto flex flex-col gap-3 pt-4">
        {{ $slot }}

        @if ($primaryAction)
            <a href="{{ $primaryAction['url'] }}" wire:navigate class="btn btn-accent h-10 w-full">
                <x-lucide name="plus" :size="16" />
                {{ $primaryAction['label'] }}
            </a>
        @endif

        <div class="flex items-center gap-2.5 rounded-lg bg-white/8 p-2.5">
            <x-avatar :name="auth()->user()->name" :size="32" shape="round" tone="light" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-[13px] font-semibold">{{ auth()->user()->name }}</p>
                <p class="truncate text-[11px] text-white/70">{{ $subtitle ?? auth()->user()->role->label() }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex size-8 cursor-pointer items-center justify-center rounded-md text-white/70 transition hover:bg-white/10 hover:text-white"
                        title="{{ __('common.log_out') }}" aria-label="{{ __('common.log_out') }}">
                    <x-lucide name="log-out" :size="16" />
                </button>
            </form>
        </div>
    </div>
</div>
