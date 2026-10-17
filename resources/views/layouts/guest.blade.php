<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>(function(){try{if(localStorage.getItem('tema')==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
        <title>{{ $marca->nombre() }}</title>
        <link rel="icon" href="{{ $marca->logoUrl() }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <x-estilos-marca />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-[1.05fr_1fr]">

            {{-- Panel de marca (color de la clínica) --}}
            <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-blue-800 text-white p-12 xl:p-16">
                {{-- Degradado y adornos --}}
                <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-br from-blue-700 via-blue-800 to-blue-950"></div>
                <div aria-hidden="true" class="absolute inset-0 opacity-[0.12]"
                     style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 26px 26px;"></div>
                <div aria-hidden="true" class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
                <div aria-hidden="true" class="absolute -bottom-32 -left-24 w-96 h-96 rounded-full bg-black/20 blur-3xl"></div>

                <div class="relative">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-white/95 shadow-lg shrink-0 overflow-hidden">
                            <x-application-logo class="w-11 h-11 object-contain" />
                        </span>
                        <div>
                            <p class="text-2xl font-bold leading-none">{{ $marca->nombre() }}</p>
                            @if ($marca->subtitulo())
                                <p class="text-sm text-white/70 mt-1">{{ $marca->subtitulo() }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="relative max-w-md">
                    <h2 class="text-3xl xl:text-4xl font-bold leading-tight">Toda la clínica, en un solo lugar.</h2>
                    <p class="mt-4 text-white/80 leading-relaxed">
                        Turnos, historia clínica, control prenatal, ecografías y facturación electrónica. Funciona en red local, sin perder expedientes ni tiempo en la sala de espera.
                    </p>

                    <ul class="mt-8 space-y-4">
                        @foreach ([
                            ['Agenda y sala de espera', 'Organiza los turnos del día y el orden de atención.'],
                            ['Historia clínica digital', 'Expedientes completos, seguros y siempre a mano.'],
                            ['Facturación electrónica SRI', 'Factura al instante y sin riesgo de multas.'],
                        ] as [$titulo, $detalle])
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex-none w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </span>
                                <div>
                                    <p class="font-semibold">{{ $titulo }}</p>
                                    <p class="text-sm text-white/70">{{ $detalle }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="relative text-xs text-white/50">
                    &copy; {{ date('Y') }} {{ $marca->nombre() }}@if ($marca->subtitulo()) &mdash; {{ $marca->subtitulo() }}@endif
                </div>
            </aside>

            {{-- Columna del formulario --}}
            <main class="flex flex-col min-h-screen lg:min-h-0 bg-[#f7f7f5]">
                <div class="flex-1 flex flex-col justify-center items-center px-4 py-10 sm:px-6">
                    {{-- Marca compacta (solo en celular) --}}
                    <div class="lg:hidden flex items-center gap-3 mb-8">
                        <span class="flex items-center justify-center w-12 h-12 rounded-2xl bg-white ring-1 ring-gray-200 shadow-sm overflow-hidden">
                            <x-application-logo class="w-9 h-9 object-contain" />
                        </span>
                        <div>
                            <p class="text-xl font-bold leading-none" style="color: {{ $marca->color() }}">{{ $marca->nombre() }}</p>
                            @if ($marca->subtitulo())
                                <p class="text-xs text-gray-400 mt-1">{{ $marca->subtitulo() }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="w-full max-w-md">
                        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-200/70 p-7 sm:p-9">
                            {{ $slot }}
                        </div>
                    </div>
                </div>

                <p class="lg:hidden text-center text-xs text-gray-400 pb-6 px-4">
                    &copy; {{ date('Y') }} {{ $marca->nombre() }} · <a href="{{ route('privacidad') }}" class="underline hover:text-gray-600">Aviso de privacidad</a>
                </p>
                <p class="hidden lg:block text-right text-xs text-gray-400 pb-5 px-8">
                    <a href="{{ route('privacidad') }}" class="underline hover:text-gray-600">Aviso de privacidad</a>
                </p>
            </main>
        </div>
    </body>
</html>
