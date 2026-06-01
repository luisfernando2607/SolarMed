<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title>{{ config('app.name', 'Clínica Santa Martha') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <livewire:formulario-turno />
        <script>
            window.turnoWizard = () => ({
                paso: 1,
                init() {},
                irA(n) { this.paso = n; }
            });
        </script>
        @livewireScripts
    </body>
</html>
