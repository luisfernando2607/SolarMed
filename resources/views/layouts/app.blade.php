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
        <x-estilos-marca />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        {{-- menuAbierto: menú en celular · colapsado: barra lateral solo con íconos (se recuerda en el navegador) --}}
        <div class="min-h-screen bg-[#f7f7f5]"
             x-data="{ menuAbierto: false, colapsado: $persist(false).as('sidebar-colapsado') }"
             x-on:livewire:navigated.window="menuAbierto = false">
            <livewire:layout.navigation />

            {{-- Avisos de toda la app: $this->dispatch('notify', message: …, type: success|error|warning|info)
                 o session()->flash('notify', [...]) antes de redirigir --}}
            <div x-data x-on:notify.window="window.notificar($event.detail)"></div>

            <div class="transition-all duration-200 lg:pl-64" :class="colapsado ? 'lg:!pl-[72px]' : 'lg:pl-64'">
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
        <script>
            window.notificar = function ({ message = '', type = 'success' } = {}) {
                if (!window.Swal) return;
                const titulos = { success: 'Listo', error: 'No se pudo completar', warning: 'Atención', info: 'Información' };
                if (type === 'warning') {
                    // Avisos que requieren lectura (p. ej. alertas clínicas): cuadro central con confirmación
                    Swal.fire({ icon: 'warning', title: titulos.warning, text: message, confirmButtonText: 'Entendido', confirmButtonColor: @js($marca->color()) });
                    return;
                }
                Swal.fire({
                    toast: true, position: 'top-end', icon: type, title: titulos[type] ?? '', text: message,
                    timer: type === 'error' ? 6000 : 3500, timerProgressBar: true, showConfirmButton: false,
                    didOpen: t => { t.addEventListener('mouseenter', Swal.stopTimer); t.addEventListener('mouseleave', Swal.resumeTimer); },
                });
            };
        </script>
        @if (session('notify'))
            <script>
                (function () {
                    const aviso = @js(session('notify'));
                    window.Swal ? window.notificar(aviso) : document.addEventListener('DOMContentLoaded', () => window.notificar(aviso), { once: true });
                })();
            </script>
        @endif
    </body>
</html>
