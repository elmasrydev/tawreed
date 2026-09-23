@props(['variant' => 'color', 'mark' => false])

{{-- The TawreedHub logo. It never mirrors in right-to-left layouts. --}}
@php
    $file = ($mark ? 'mark' : 'logo').'-'.($variant === 'white' ? 'white' : 'full-color').'.png';
@endphp

<img src="{{ asset('images/brand/'.$file) }}" alt="{{ __('ui.app_name') }}" {{ $attributes->class('w-auto') }}>
