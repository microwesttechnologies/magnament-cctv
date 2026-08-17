<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso técnicos · Management CCTV</title>
    <link rel="manifest" href="{{ route('technician.manifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192.png') }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <x-pwa-config />
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pwa.js'])
</head>
<body class="min-h-dvh bg-background text-foreground" x-data x-init="$store.shell.init()">
    <div class="mx-auto flex min-h-dvh max-w-md flex-col justify-center px-5 py-10">
        <p class="text-xs font-semibold uppercase tracking-wide text-accent">PWA técnicos</p>
        <h1 class="mt-2 text-2xl font-bold">Ingresar</h1>
        <p class="mt-1 text-sm text-foreground-muted">Usa tu correo y número de cédula. No compartas estos datos.</p>

        @if ($errors->any())
            <x-ui.alert variant="error" class="mt-6">{{ $errors->first() }}</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('technician.login.store') }}" class="mt-6 space-y-4">
            @csrf
            <x-ui.form-field label="Correo electrónico" for="email" required>
                <x-ui.input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="min-h-11" />
            </x-ui.form-field>
            <x-ui.form-field label="Número de cédula" for="document_number" required hint="Se compara con tu ficha de personal. No es una contraseña almacenada aparte.">
                <x-ui.input id="document_number" type="text" name="document_number" required autocomplete="off" inputmode="numeric" class="min-h-11" />
            </x-ui.form-field>
            <x-ui.button type="submit" class="min-h-11 w-full justify-center">Entrar</x-ui.button>
        </form>
        <p class="mt-6 text-center text-xs text-foreground-muted">
            ¿Eres supervisor? <a class="text-accent underline" href="{{ route('login') }}">Acceso de oficina</a>
        </p>
    </div>
    <x-pwa-install-banner />
    <x-ui.toast-container />
</body>
</html>
