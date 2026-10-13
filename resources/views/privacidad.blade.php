<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aviso de privacidad — {{ $marca->nombre() }}</title>
    <meta name="theme-color" content="{{ $marca->color() }}">
    <link rel="icon" href="{{ $marca->logoUrl() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <x-estilos-marca />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $contacto = collect([$marca->email(), $marca->telefono()])->filter()->implode(' · ');
    $titulo = 'text-base font-semibold text-gray-900 mt-8 mb-2';
@endphp
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

    <main class="max-w-3xl mx-auto px-4 py-8 sm:py-12">
        <article class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 text-sm leading-relaxed text-gray-700">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Versión {{ \App\Models\ConsentimientoDatos::VERSION_AVISO }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Aviso de privacidad</h1>
            <p class="mt-3">Este aviso explica cómo {{ $marca->nombre() }} trata los datos personales que usted nos entrega al solicitar un turno, reservar una cita o atenderse, conforme a la Ley Orgánica de Protección de Datos Personales del Ecuador y al Reglamento de Información Confidencial en el Sistema Nacional de Salud.</p>

            <h2 class="{{ $titulo }}">Responsable del tratamiento</h2>
            <p>{{ $marca->nombre() }}@if ($marca->direccion()), {{ $marca->direccion() }}@endif.@if ($contacto) Contacto: {{ $contacto }}.@endif</p>

            <h2 class="{{ $titulo }}">Qué datos recogemos</h2>
            <ul class="list-disc pl-5 space-y-1">
                <li>Datos de identificación y contacto: nombres, apellidos, cédula, fecha de nacimiento, sexo, teléfono, correo y dirección.</li>
                <li>Datos de salud que usted decida indicar (motivo de consulta, alergias, medicación, antecedentes) y los que se registren durante su atención en la historia clínica.</li>
                <li>Datos técnicos mínimos para proteger el servicio, como la dirección IP desde la que se envía el formulario.</li>
            </ul>

            <h2 class="{{ $titulo }}">Para qué los usamos</h2>
            <ul class="list-disc pl-5 space-y-1">
                <li>Asignar su turno o cita y atenderle.</li>
                <li>Llevar su historia clínica, que es obligatoria según la normativa sanitaria.</li>
                <li>Enviarle recordatorios de citas e indicaciones médicas, y la encuesta de satisfacción.</li>
                <li>Emitir los comprobantes electrónicos exigidos por el SRI cuando corresponda.</li>
            </ul>
            <p class="mt-2">Los datos de salud son datos sensibles: solo los usa el personal que interviene en su atención, bajo deber de confidencialidad, y cada acceso a su historia clínica queda registrado.</p>

            <h2 class="{{ $titulo }}">Con quién se comparten</h2>
            <p>No vendemos ni cedemos sus datos. Solo se comunican cuando la ley lo exige o usted lo autoriza: autoridad sanitaria o judicial, el SRI para la facturación, u otro establecimiento de salud al que usted sea referido.</p>

            <h2 class="{{ $titulo }}">Cuánto tiempo los conservamos</h2>
            <p>La historia clínica se conserva el tiempo que establece la normativa sanitaria. Los datos de turnos o citas que no lleguen a una atención se conservan solo lo necesario para gestionarlos.</p>

            <h2 class="{{ $titulo }}">Sus derechos</h2>
            <p>Usted puede solicitar el acceso, la rectificación, la actualización, la eliminación, la oposición o la portabilidad de sus datos, y retirar su consentimiento cuando lo desee. Presente su solicitud en recepción{{ $contacto ? ' o por ' . $contacto : '' }}. Responderemos en un plazo máximo de 15 días. Si no está conforme con la respuesta, puede acudir a la Superintendencia de Protección de Datos Personales.</p>

            <h2 class="{{ $titulo }}">Seguridad</h2>
            <p>Aplicamos medidas técnicas y organizativas para proteger sus datos: acceso con clave personal, registro inalterable de accesos, respaldos cifrados y cifrado de la conexión cuando el servicio se ofrece por internet.</p>
        </article>
    </main>
</body>
</html>
