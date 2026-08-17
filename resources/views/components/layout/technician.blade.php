@props([
    'title' => 'Órdenes · Management CCTV',
    'active' => 'home',
])

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="CCTV Técnicos">
    <title>{{ $title }}</title>
    <link rel="manifest" href="{{ route('technician.manifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192.png') }}">
    <x-pwa-config />
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pwa.js'])
</head>
<body class="min-h-dvh bg-background text-foreground antialiased" x-data x-init="$store.shell.init()">
    <a href="#tech-main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-accent focus:px-4 focus:py-2">Saltar al contenido</a>

    @if (session('success') || session('status'))
        <span data-flash-success="{{ session('success') ?? session('status') }}" class="hidden"></span>
    @endif
    @if (session('error'))
        <span data-flash-error="{{ session('error') }}" class="hidden"></span>
    @endif

    <div class="mx-auto flex min-h-dvh max-w-lg flex-col pb-24">
        <main id="tech-main" class="flex-1 px-4 pt-5 motion-page-enter">
            {{ $slot }}
        </main>
    </div>

    <x-pwa-install-banner />

    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-surface/95 pb-[env(safe-area-inset-bottom)] backdrop-blur" aria-label="Navegación técnico">
        <div class="mx-auto grid max-w-lg grid-cols-4 gap-1 px-2 py-2">
            @foreach ([
                ['home', 'Inicio', route('technician.home'), 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12'],
                ['orders', 'Órdenes', route('technician.orders.index'), 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['notifications', 'Avisos', route('technician.notifications'), 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0'],
                ['profile', 'Perfil', route('technician.profile'), 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0'],
            ] as [$key, $label, $href, $icon])
                <a href="{{ $href }}" class="flex min-h-11 flex-col items-center justify-center rounded-md px-2 py-2 text-xs font-medium transition duration-fast motion-reduce:transition-none {{ $active === $key ? 'bg-accent/10 text-accent' : 'text-foreground-muted' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </nav>
    <x-ui.toast-container />
</body>
</html>
