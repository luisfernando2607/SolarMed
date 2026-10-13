<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $marca->nombre() }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <x-estilos-marca />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-blue-50">
            <div class="mb-4">
                <a href="/" wire:navigate>
                    <x-application-logo class="h-20 w-auto max-w-[240px] object-contain" />
                </a>
            </div>
            <div class="w-full sm:max-w-md mt-2 px-6 py-6 bg-white shadow-md rounded-lg">
                {{ $slot }}
            </div>
            <p class="mt-6 text-xs text-gray-400">&copy; {{ date('Y') }} {{ $marca->nombre() }}@if ($marca->subtitulo()) &mdash; {{ $marca->subtitulo() }}@endif · <a href="{{ route('privacidad') }}" class="underline hover:text-gray-600">Aviso de privacidad</a></p>
        </div>
    </body>
</html>
