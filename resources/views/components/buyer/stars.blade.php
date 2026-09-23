@props(['rating', 'size' => 14])

{{-- Five read-only stars filled to the nearest whole rating. --}}
@php $filled = (int) round((float) $rating); @endphp

<span {{ $attributes->class('inline-flex items-center gap-0.5') }} role="img"
      aria-label="{{ __('buyer.rating_out_of_five', ['rating' => number_format((float) $rating, 1)]) }}">
    @foreach (range(1, 5) as $star)
        <svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" aria-hidden="true"
             @class(['shrink-0', 'text-accent-500' => $star <= $filled, 'text-gray-300' => $star > $filled])>
            <polygon fill="currentColor" points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
        </svg>
    @endforeach
</span>
