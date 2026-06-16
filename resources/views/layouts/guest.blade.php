<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'SolarMed Software') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-blue-50">
            <div class="mb-4">
                <a href="/" wire:navigate>
                    <x-application-logo class="w-20 h-20" />
                </a>
            </div>
            <div class="w-full sm:max-w-md mt-2 px-6 py-6 bg-white shadow-md rounded-lg">
                {{ $slot }}
            </div>
            <p class="mt-6 text-xs text-gray-400">&copy; {{ date('Y') }} SolarMed Software &mdash; Sistema de Gestión Clínica</p>
        </div>
    </body>
</html>
