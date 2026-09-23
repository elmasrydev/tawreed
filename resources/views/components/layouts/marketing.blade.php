@props(['title' => null])

<x-layouts.app :title="$title" body-class="bg-white">
    {{ $slot }}
</x-layouts.app>
