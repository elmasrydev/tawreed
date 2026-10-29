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
    <script>
        {{-- A busy or unreachable server (502/503/504) would otherwise replace the page with its raw error screen; show a toast and let the next request retry. --}}
        document.addEventListener('livewire:init', () => {
            let lastNoticeAt = 0;

            Livewire.interceptRequest(({ onError }) => {
                onError(({ response, preventDefault }) => {
                    if (! [502, 503, 504].includes(response.status)) {
                        return;
                    }

                    preventDefault();

                    if (Date.now() - lastNoticeAt > 30000) {
                        lastNoticeAt = Date.now();
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: @js(__('common.connection_retrying')), error: true } }));
                    }
                });
            });
        });
    </script>
</body>
</html>
