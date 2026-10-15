<div wire:poll.60s>
    <p class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Videoconsulta</p>
    <h1 class="mt-1 text-lg font-semibold text-gray-900">{{ $tc->fecha_hora->translatedFormat('l j \d\e F') }} a las {{ $tc->fecha_hora->format('H:i') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ $tc->medico?->nombre_completo }}{{ $tc->medico?->especialidad ? ' · ' . $tc->medico->especialidad->nombre : '' }} · {{ $marca->nombre() }}</p>

    @if ($tc->estado === 'cancelada')
        <p class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">Esta videoconsulta fue cancelada. Si necesita otra cita, comuníquese con {{ $marca->nombre() }}{{ $marca->telefono() ? ' al ' . $marca->telefono() : '' }}.</p>
    @elseif (in_array($tc->estado, ['finalizada', 'no_asistio'], true))
        <p class="mt-5 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-600">Esta videoconsulta ya terminó. Gracias.</p>
    @elseif (!$tc->tieneConsentimiento())
        <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4 max-h-80 overflow-y-auto">
            @include('partials.consentimiento-telesalud', ['medico' => $tc->medico?->nombre_completo, 'clinica' => $marca->nombre()])
        </div>
        <form wire:submit="aceptar" class="mt-4 space-y-3">
            <label class="flex items-start gap-3 text-sm text-gray-800">
                <input type="checkbox" wire:model="autoriza" class="mt-0.5 w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 shrink-0">
                <span>Autorizo la atención por videoconsulta (punto 1).</span>
            </label>
            @error('autoriza') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
            <label class="flex items-start gap-3 text-sm text-gray-800">
                <input type="checkbox" wire:model="consiente" class="mt-0.5 w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 shrink-0">
                <span>Acepto el tratamiento de mis datos para esta atención (punto 2).</span>
            </label>
            @error('consiente') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
            <input wire:model="representante" type="text" maxlength="150" placeholder="Si acepta como representante legal, escriba su nombre (opcional)" class="w-full rounded-xl border-gray-200 text-sm">
            <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-xs">Aceptar y continuar</button>
        </form>
    @else
        <p class="mt-5 text-sm text-emerald-700">Consentimiento aceptado ✓</p>
        @if ($salaUrl)
            <a href="{{ $salaUrl }}" target="_blank" rel="noopener" class="mt-4 flex items-center justify-center w-full py-3 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 shadow-xs">Entrar a la videoconsulta</a>
            <p class="mt-2 text-xs text-gray-500">Se abrirá en otra pestaña. Permita el uso de la cámara y el micrófono cuando el navegador lo pida.</p>
        @elseif (!$enHorario && now()->lt($ventana[0]))
            <p class="mt-4 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-900">El botón para entrar aparecerá aquí desde las {{ $ventana[0]->format('H:i') }} del {{ $ventana[0]->format('d/m') }}. Puede dejar esta página abierta.</p>
        @elseif (!$enHorario)
            <p class="mt-4 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-600">El horario para entrar ya pasó. Comuníquese con {{ $marca->nombre() }}{{ $marca->telefono() ? ' al ' . $marca->telefono() : '' }}.</p>
        @else
            <p class="mt-4 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-900">El enlace de la sala estará disponible en unos minutos. Puede dejar esta página abierta.</p>
        @endif
        <ul class="mt-5 list-disc pl-5 space-y-1 text-xs text-gray-500">
            <li>Conéctese desde un lugar privado y tranquilo, con buena señal de internet.</li>
            <li>Tenga a mano su cédula, sus medicamentos y exámenes recientes.</li>
        </ul>
    @endif
</div>
