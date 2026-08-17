<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión · CCTV Manager</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="flex min-h-screen">
        {{-- Panel izquierdo: imagen de la cámara --}}
        <div class="relative hidden w-1/2 overflow-hidden lg:block">
            <img
                src="{{ asset('images/login-camera.png') }}"
                alt="Cámara de seguridad CCTV"
                class="absolute inset-0 h-full w-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-tr from-slate-900/70 via-slate-800/30 to-blue-900/20"></div>

            <div class="absolute bottom-10 left-10 text-white">
                <div class="flex items-center gap-2 text-sm font-semibold tracking-wide">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-sky-400"></span>
                    </span>
                    SYSTEM STATUS: OPTIMAL
                </div>
                <div class="mt-3 space-y-1 font-mono text-xs text-slate-300/80">
                    <p>Network Latency: 12ms</p>
                    <p>Active Nodes: 1,248</p>
                </div>
            </div>
        </div>

        {{-- Panel derecho: formulario --}}
        <div class="flex w-full items-center justify-center bg-stone-50 px-6 py-12 lg:w-1/2">
            <div class="w-full max-w-md">
                {{-- Logo --}}
                <div class="mb-8 flex items-center gap-2 text-slate-900">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h7.5a2.25 2.25 0 002.25-2.25V7.5A2.25 2.25 0 0012 5.25H4.5A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    <span class="text-lg font-bold tracking-tight">CCTV Manager</span>
                </div>

                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Bienvenido de nuevo</h1>
                <p class="mt-2 text-sm text-slate-500">Ingresa tus credenciales para acceder al portal técnico</p>

                {{-- Errores de validación --}}
                @if ($errors->any())
                    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                    @csrf

                    {{-- Correo --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Correo Electrónico</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </span>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                placeholder="nombre@empresa.com"
                                class="block w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 focus:outline-none"
                            >
                        </div>
                    </div>

                    {{-- Contraseña --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium text-slate-700">Contraseña</label>
                            <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-500">Olvidé mi contraseña</a>
                        </div>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </span>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="block w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 focus:outline-none"
                            >
                            <button
                                type="button"
                                onclick="const i = document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password';"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                                aria-label="Mostrar u ocultar contraseña"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Recordarme --}}
                    <div class="flex items-center">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/40"
                        >
                        <label for="remember" class="ml-2 text-sm text-slate-600">Recordarme</label>
                    </div>

                    {{-- Botón --}}
                    <button
                        type="submit"
                        class="group flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                    >
                        Iniciar Sesión
                        <svg class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </form>

                {{-- Footer --}}
                <div class="mt-10 flex flex-col gap-2 border-t border-slate-200 pt-5 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <span>&copy; {{ date('Y') }} CCTV Inventory Manager</span>
                    <span class="flex items-center gap-2">
                        <a href="#" class="hover:text-slate-600">Asistencia técnica</a>
                        <span>&middot;</span>
                        <a href="#" class="hover:text-slate-600">Términos</a>
                    </span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
