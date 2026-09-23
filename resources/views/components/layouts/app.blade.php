@props(['title' => null, 'bodyClass' => ''])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ App\Http\Middleware\SetLocale::isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B3D5C">
    <title>{{ $title ? $title.' · '.__('ui.app_name') : __('ui.app_name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/mark-full-color.png') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh {{ $bodyClass }}">
    {{ $slot }}

    <x-toast />
    @livewireScripts
</body>
</html>
