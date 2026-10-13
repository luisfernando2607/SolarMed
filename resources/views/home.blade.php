<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $marca->nombre() }}@if ($marca->subtitulo()) — {{ $marca->subtitulo() }}@endif</title>
    <meta name="description" content="{{ $marca->nombre() }}. {{ $marca->subtitulo() }}. Turnos, especialidades, horario y contacto.">
    <meta name="theme-color" content="{{ $marca->color() }}">
    <link rel="icon" href="{{ $marca->logoUrl() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <x-estilos-marca />
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --marca: {{ $marca->color() }};
            --marca-oscuro: color-mix(in srgb, var(--marca) 70%, black);
            --marca-suave: color-mix(in srgb, var(--marca) 10%, white);
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-800">

    {{-- Barra superior --}}
    <header class="bg-white/90 backdrop-blur border-b sticky top-0 z-20">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 min-w-0">
                <img src="{{ $marca->logoUrl() }}" alt="" class="h-9 w-auto max-w-[120px] object-contain">
                <span class="font-bold text-lg truncate" style="color: var(--marca)">{{ $marca->nombre() }}</span>
            </a>
            <nav class="flex items-center gap-2 text-sm">
                <a href="#especialidades" class="hidden md:inline px-3 py-2 text-gray-600 hover:text-gray-900">Especialidades</a>
                <a href="#horario" class="hidden md:inline px-3 py-2 text-gray-600 hover:text-gray-900">Horario y contacto</a>
                <a href="{{ route('login') }}" class="px-3 py-2 text-gray-600 hover:text-gray-900">Ingresar</a>
                @if ($reservaOnline)
                    <a href="{{ route('agendar') }}" class="px-4 py-2 rounded-lg text-white font-medium" style="background: var(--marca)">Agendar cita</a>
                @else
                    <a href="{{ route('turno.form') }}" class="px-4 py-2 rounded-lg text-white font-medium" style="background: var(--marca)">Tomar turno</a>
                @endif
            </nav>
        </div>
    </header>

    {{-- Portada --}}
    <section class="text-white" style="background: linear-gradient(135deg, var(--marca-oscuro), var(--marca));">
        <div class="max-w-6xl mx-auto px-4 py-16 md:py-24 grid md:grid-cols-2 gap-10 items-center">
            <div>
                <h1 class="text-3xl md:text-5xl font-bold leading-tight">{{ $marca->nombre() }}</h1>
                @if ($marca->subtitulo())
                    <p class="mt-3 text-lg md:text-xl text-white/85">{{ $marca->subtitulo() }}</p>
                @endif
                <p class="mt-5 text-white/80 max-w-md">{{ $reservaOnline ? 'Agende su cita en línea eligiendo médico, día y hora, o registre su turno de hoy desde el celular antes de llegar.' : 'Registre su turno desde su celular antes de llegar y espere su llamado en la sala de espera.' }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($reservaOnline)
                        <a href="{{ route('agendar') }}" class="px-6 py-3 rounded-xl bg-white font-semibold shadow-lg" style="color: var(--marca-oscuro)">Agendar cita</a>
                        <a href="{{ route('turno.form') }}" class="px-6 py-3 rounded-xl border border-white/50 font-semibold hover:bg-white/10">Turno para hoy</a>
                    @else
                        <a href="{{ route('turno.form') }}" class="px-6 py-3 rounded-xl bg-white font-semibold shadow-lg" style="color: var(--marca-oscuro)">Tomar turno ahora</a>
                    @endif
                    @if ($marca->telefono())
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $marca->telefono()) }}" class="px-6 py-3 rounded-xl border border-white/50 font-semibold hover:bg-white/10">Llamar {{ $marca->telefono() }}</a>
                    @endif
                </div>
            </div>
            <div class="hidden md:flex justify-center">
                <div class="bg-white rounded-3xl p-8 shadow-2xl">
                    <img src="{{ $marca->logoUrl() }}" alt="{{ $marca->nombre() }}" class="w-64 h-64 object-contain">
                </div>
            </div>
        </div>
    </section>

    {{-- Especialidades --}}
    @if ($especialidades->isNotEmpty())
        <section id="especialidades" class="max-w-6xl mx-auto px-4 py-16">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Especialidades</h2>
            <p class="text-gray-500 mt-1">Atención con profesionales de cada área.</p>
            <div class="mt-8 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($especialidades as $e)
                    <article class="bg-white rounded-2xl border shadow-sm p-6 border-t-4" style="border-top-color: {{ $e->color_agenda ?: $marca->color() }}">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $e->nombre }}</h3>
                        @if ($e->medicos->isNotEmpty())
                            <p class="text-sm text-gray-500 mt-1">{{ $e->medicos->map(fn ($m) => $m->nombre_completo)->implode(' · ') }}</p>
                        @endif
                        @php $motivos = collect($e->motivos ?? [])->reject(fn ($m) => mb_strtolower($m) === 'otro')->take(6); @endphp
                        @if ($motivos->isNotEmpty())
                            <ul class="mt-4 space-y-1.5 text-sm text-gray-700">
                                @foreach ($motivos as $m)
                                    <li class="flex gap-2"><span style="color: {{ $e->color_agenda ?: $marca->color() }}">●</span>{{ $m }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Horario, contacto y verificación --}}
    <section id="horario" style="background: var(--marca-suave)">
        <div class="max-w-6xl mx-auto px-4 py-16 grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl border p-6">
                <h2 class="text-lg font-semibold text-gray-900">Horario de atención</h2>
                <p class="mt-3 text-gray-700">{{ $diasTexto }}</p>
                <p class="text-2xl font-bold mt-1" style="color: var(--marca)">{{ $horario }}</p>
                <a href="{{ route($reservaOnline ? 'agendar' : 'turno.form') }}" class="inline-block mt-4 text-sm font-medium" style="color: var(--marca)">{{ $reservaOnline ? 'Agendar cita →' : 'Registrar turno →' }}</a>
            </div>
            <div class="bg-white rounded-2xl border p-6">
                <h2 class="text-lg font-semibold text-gray-900">Contacto</h2>
                <dl class="mt-3 space-y-2 text-sm text-gray-700">
                    @if ($marca->direccion())<div><dt class="text-gray-400 text-xs">Dirección</dt><dd>{{ $marca->direccion() }}</dd></div>@endif
                    @if ($marca->telefono())<div><dt class="text-gray-400 text-xs">Teléfono</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $marca->telefono()) }}" class="hover:underline">{{ $marca->telefono() }}</a></dd></div>@endif
                    @if ($marca->email())<div><dt class="text-gray-400 text-xs">Correo</dt><dd><a href="mailto:{{ $marca->email() }}" class="hover:underline">{{ $marca->email() }}</a></dd></div>@endif
                    @if (!$marca->direccion() && !$marca->telefono() && !$marca->email())
                        <p class="text-gray-400">Configure la dirección, teléfono y correo en Configuración &gt; Clínica.</p>
                    @endif
                </dl>
            </div>
            <div class="bg-white rounded-2xl border p-6">
                <h2 class="text-lg font-semibold text-gray-900">Verificar un certificado</h2>
                <p class="mt-2 text-sm text-gray-500">Ingrese el código impreso al pie del certificado médico.</p>
                <form action="{{ route('certificados.buscar') }}" method="GET" class="mt-4 flex gap-2">
                    <input name="codigo" required maxlength="16" placeholder="Ej.: ABCD234XYZ" class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm uppercase">
                    <button class="px-4 py-2 rounded-lg text-white text-sm font-medium" style="background: var(--marca)">Verificar</button>
                </form>
            </div>
        </div>
    </section>

    <footer class="bg-white border-t">
        <div class="max-w-6xl mx-auto px-4 py-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-gray-500">
            <span>&copy; {{ date('Y') }} {{ $marca->nombre() }}</span>
            <span>{{ $marca->contacto() }}</span>
        </div>
    </footer>
</body>
</html>
