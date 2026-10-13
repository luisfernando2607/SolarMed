<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agendar cita — {{ $marca->nombre() }}</title>
    <meta name="description" content="Reserve su cita en {{ $marca->nombre() }}: elija especialidad, médico, día y hora.">
    <meta name="theme-color" content="{{ $marca->color() }}">
    <link rel="icon" href="{{ $marca->logoUrl() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <x-estilos-marca />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-800">
    <header class="bg-white/90 backdrop-blur-sm border-b sticky top-0 z-20">
        <div class="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 min-w-0">
                <img src="{{ $marca->logoUrl() }}" alt="" class="h-9 w-auto max-w-[110px] object-contain">
                <span class="font-bold text-lg truncate text-blue-700">{{ $marca->nombre() }}</span>
            </a>
            <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gray-800 shrink-0">Volver al inicio</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-6 sm:py-10">
        <livewire:reserva-cita />
    </main>

    <footer class="max-w-3xl mx-auto px-4 pb-8 text-center text-xs text-gray-400">
        Sus datos se usan solo para gestionar su atención en {{ $marca->nombre() }}.
    </footer>
    @livewireScripts
</body>
</html>
