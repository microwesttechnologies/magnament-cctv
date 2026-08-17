@props([
    'title' => 'CCTV Manager',
    'active' => '',
    'breadcrumbs' => null,
])

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('theme') || 'system';
                var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (dark) document.documentElement.classList.add('dark');
                if (localStorage.getItem('sidebarCollapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full min-h-screen bg-background text-foreground antialiased" x-data x-init="$store.shell.init()">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[90] focus:rounded-md focus:bg-accent focus:px-4 focus:py-2 focus:text-on-accent">
        Saltar al contenido
    </a>

    @if (session('success'))
        <span data-flash-success="{{ session('success') }}" class="hidden"></span>
    @endif
    @if (session('error'))
        <span data-flash-error="{{ session('error') }}" class="hidden"></span>
    @endif

    {{-- Mobile drawer overlay --}}
    <div
        x-show="$store.shell.mobileDrawerOpen"
        x-cloak
        x-transition:enter="transition-opacity duration-medium motion-reduce:transition-none"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-fast motion-reduce:transition-none"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[60] bg-primary/40 lg:hidden"
        @click="$store.shell.closeMobileDrawer()"
        aria-hidden="true"
    ></div>

    <x-sidebar :active="$active" />

    <header
        class="app-header fixed inset-x-0 top-0 z-40 flex h-16 items-center justify-between border-b border-border bg-surface px-4 shadow-xs transition-[padding-left] duration-medium ease-standard sm:px-6 lg:pl-[calc(var(--sidebar-width)+1rem)]"
    >
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <x-ui.icon-button
                variant="ghost"
                label="Menú"
                @click="$store.shell.toggleSidebar()"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </x-ui.icon-button>

            <div class="hidden min-w-0 md:block">
                @if ($breadcrumbs)
                    {{ $breadcrumbs }}
                @else
                    <span class="truncate text-sm font-semibold text-foreground">Management CCTV</span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-2">
            <x-ui.icon-button variant="ghost" label="Cambiar tema" @click="$store.shell.toggleTheme()">
                <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
                <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                </svg>
            </x-ui.icon-button>

            <a href="{{ route('configuracion') }}" class="hidden items-center gap-2 rounded-md px-2 py-1 transition-colors hover:bg-muted sm:flex">
                <x-ui.avatar :name="auth()->user()?->name ?? 'Usuario'" size="sm" />
                <span class="hidden max-w-36 truncate text-sm font-medium text-foreground lg:inline">
                    {{ auth()->user()?->name ?? 'Usuario' }}
                </span>
            </a>
        </div>
    </header>

    <main
        id="main-content"
        class="app-main px-4 py-8 pt-[calc(var(--header-height)+2rem)] transition-[margin] duration-medium ease-standard sm:px-6 lg:px-8"
    >
        @if ($breadcrumbs)
            <div class="mb-4 md:hidden">{{ $breadcrumbs }}</div>
        @endif
        {{ $slot }}
    </main>

    <x-ui.toast-container />
</body>
</html>
