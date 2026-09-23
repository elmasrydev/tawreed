@props(['steps', 'current' => 1])

{{--
    Numbered progress. Completed steps are teal with a check, the current step
    is navy with a halo. Labels hide on phones, where a caption shows the
    current step instead.
--}}
<div {{ $attributes->class('flex flex-col gap-2') }}>
    <ol class="flex items-center">
        @foreach ($steps as $index => $label)
            @php($number = $index + 1)
            <li @class(['flex items-center', 'flex-1' => ! $loop->last])>
                <span class="flex items-center gap-2">
                    <span @class([
                        'flex size-[26px] shrink-0 items-center justify-center rounded-full text-xs font-bold',
                        'bg-success-500 text-white' => $number < $current,
                        'bg-brand-700 text-white shadow-[0_0_0_4px_#e3f0fa]' => $number === $current,
                        'border-2 border-gray-300 bg-white text-gray-400' => $number > $current,
                    ])>
                        @if ($number < $current)
                            <x-lucide name="check" :size="13" :stroke="3" />
                        @else
                            {{ $number }}
                        @endif
                    </span>
                    <span @class([
                        'hidden text-[13px] whitespace-nowrap md:inline',
                        'font-semibold text-brand-700' => $number === $current,
                        'font-medium text-gray-700' => $number < $current,
                        'font-medium text-gray-400' => $number > $current,
                    ])>{{ $label }}</span>
                </span>
                @unless ($loop->last)
                    <span @class(['mx-2.5 h-0.5 flex-1 rounded', 'bg-success-500' => $number < $current, 'bg-gray-200' => $number >= $current])></span>
                @endunless
            </li>
        @endforeach
    </ol>
    <p class="text-xs font-semibold text-brand-700 md:hidden">{{ $steps[$current - 1] ?? '' }}</p>
</div>
