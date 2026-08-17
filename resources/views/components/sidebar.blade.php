@props([
    'active' => '',
])

@php
    $sections = [
        [
            'label' => 'Operaciones',
            'items' => [
                ['key' => 'home', 'label' => 'Inicio', 'route' => 'dashboard', 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
                ['key' => 'proyectos', 'label' => 'Proyectos', 'route' => 'projects', 'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
                ['key' => 'cotizaciones', 'label' => 'Cotizaciones', 'route' => 'cotizaciones', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
                ['key' => 'ordenes', 'label' => 'Órdenes', 'route' => 'service-orders.index', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['key' => 'trazabilidad', 'label' => 'Trazabilidad', 'route' => 'trazabilidad', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ],
        ],
        [
            'label' => 'Recursos',
            'items' => [
                ['key' => 'personal', 'label' => 'Personal', 'route' => 'staff.index', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ],
        ],
    ];
@endphp

<aside
    class="app-sidebar fixed inset-y-0 left-0 z-[50] flex flex-col border-r border-border bg-surface shadow-sm lg:translate-x-0"
    :class="{
        'translate-x-0': $store.shell.mobileDrawerOpen,
        '-translate-x-full': ! $store.shell.mobileDrawerOpen,
        'lg:!translate-x-0': true,
        'items-center': $store.shell.sidebarCollapsed
    }"
    :style="window.innerWidth < 1024 ? 'width: 16rem' : 'width: var(--sidebar-width)'"
>
    {{-- Logo block navy --}}
    <div
        class="flex h-16 shrink-0 items-center gap-3 border-b border-border bg-primary px-4 text-on-primary"
        :class="$store.shell.sidebarCollapsed && window.innerWidth >= 1024 ? 'justify-center px-2' : 'px-5'"
    >
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-accent text-on-accent">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h7.5a2.25 2.25 0 002.25-2.25V7.5A2.25 2.25 0 0012 5.25H4.5A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>
        <div
            class="min-w-0 leading-tight"
            x-show="! $store.shell.sidebarCollapsed || window.innerWidth < 1024"
            x-cloak
            x-transition:enter="transition ease-out duration-fast motion-reduce:transition-none"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
        >
            <p class="truncate text-sm font-bold">CCTV Manager</p>
            <p class="truncate text-xs text-on-primary/70">Portal técnico</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-2 py-4" :class="$store.shell.sidebarCollapsed && window.innerWidth >= 1024 ? 'px-1.5' : 'px-3'">
        @foreach ($sections as $section)
            <p
                x-show="! $store.shell.sidebarCollapsed || window.innerWidth < 1024"
                x-cloak
                class="mb-2 px-3 text-xs font-semibold uppercase tracking-wide text-foreground-muted"
            >{{ $section['label'] }}</p>
            <div class="mb-4 space-y-1">
                @foreach ($section['items'] as $item)
                    @continue(! Route::has($item['route']))
                    @php $isActive = $active === $item['key']; @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        @click="if (window.innerWidth < 1024) $store.shell.closeMobileDrawer()"
                        @class([
                            'nav-item flex min-h-11 items-center rounded-md py-2.5 text-sm font-medium transition-colors duration-fast',
                            'border-l-[3px] border-accent bg-muted-tint text-accent' => $isActive,
                            'border-l-[3px] border-transparent text-foreground-muted hover:bg-muted hover:text-foreground' => ! $isActive,
                        ])
                        :class="$store.shell.sidebarCollapsed && window.innerWidth >= 1024 ? 'justify-center px-2' : 'gap-3 px-3'"
                        :title="$store.shell.sidebarCollapsed ? '{{ $item['label'] }}' : ''"
                        @if ($isActive) aria-current="page" @endif
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        <span x-show="! $store.shell.sidebarCollapsed || window.innerWidth < 1024" x-cloak class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="space-y-1 border-t border-border px-2 py-3" :class="$store.shell.sidebarCollapsed && window.innerWidth >= 1024 ? 'px-1.5' : 'px-3'">
        <a
            href="{{ route('configuracion') }}"
            @click="if (window.innerWidth < 1024) $store.shell.closeMobileDrawer()"
            @class([
                'nav-item flex min-h-11 items-center rounded-md py-2.5 text-sm font-medium transition-colors duration-fast',
                'border-l-[3px] border-accent bg-muted-tint text-accent' => $active === 'configuracion',
                'border-l-[3px] border-transparent text-foreground-muted hover:bg-muted hover:text-foreground' => $active !== 'configuracion',
            ])
            :class="$store.shell.sidebarCollapsed && window.innerWidth >= 1024 ? 'justify-center px-2' : 'gap-3 px-3'"
            @if ($active === 'configuracion') aria-current="page" @endif
        >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span x-show="! $store.shell.sidebarCollapsed || window.innerWidth < 1024" x-cloak>Configuración</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex min-h-11 w-full items-center rounded-md border-l-[3px] border-transparent py-2.5 text-sm font-medium text-foreground-muted transition-colors duration-fast hover:bg-destructive-tint hover:text-destructive"
                :class="$store.shell.sidebarCollapsed && window.innerWidth >= 1024 ? 'justify-center px-2' : 'gap-3 px-3'"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
                <span x-show="! $store.shell.sidebarCollapsed || window.innerWidth < 1024" x-cloak>Cerrar sesión</span>
            </button>
        </form>
    </div>
</aside>
