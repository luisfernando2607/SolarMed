<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $marca->nombre() }}</title>
        <link rel="icon" href="{{ $marca->logoUrl() }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        {{-- menuAbierto: menú en celular · colapsado: barra lateral solo con íconos (se recuerda en el navegador) --}}
        <div class="min-h-screen bg-gray-100"
             x-data="{ menuAbierto: false, colapsado: $persist(false).as('sidebar-colapsado') }"
             x-on:livewire:navigated.window="menuAbierto = false">
            <livewire:layout.navigation />

            <div class="transition-all duration-200 lg:pl-64" :class="colapsado ? 'lg:!pl-16' : 'lg:pl-64'">
                @if (isset($header))
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
