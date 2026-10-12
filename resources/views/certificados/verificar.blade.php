<x-guest-layout>
    <div class="text-center">
        @if ($c)
            <div class="mx-auto w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-2xl text-green-700 mb-3">✓</div>
            <h1 class="text-xl font-bold text-gray-900">Certificado auténtico</h1>
            <p class="text-sm text-gray-500 mb-5">Emitido por {{ $marca->nombre() }}</p>
            <dl class="text-left text-sm space-y-2 bg-gray-50 rounded-lg p-4">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Número</dt><dd class="font-mono">{{ $c->numero() }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Tipo</dt><dd>{{ $c->tipo === 'reposo' ? 'Reposo médico' : 'Atención médica' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Paciente</dt><dd>{{ $pacienteOculto }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Cédula</dt><dd class="font-mono">{{ $cedulaOculta }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Fecha de atención</dt><dd>{{ $c->fecha_atencion->format('d/m/Y') }}</dd></div>
                @if ($c->tipo === 'reposo')
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Reposo</dt><dd>{{ $c->dias_reposo }} día(s): {{ $c->reposo_desde->format('d/m/Y') }} – {{ $c->reposo_hasta->format('d/m/Y') }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Médico</dt><dd>{{ $c->medico?->nombre_completo }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Emitido</dt><dd>{{ $c->fecha_emision->format('d/m/Y') }}</dd></div>
            </dl>
            <p class="text-xs text-gray-400 mt-4">Por privacidad no se muestra el diagnóstico. Compare estos datos con el documento impreso.</p>
        @else
            <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-2xl text-red-700 mb-3">✕</div>
            <h1 class="text-xl font-bold text-gray-900">Certificado no encontrado</h1>
            <p class="text-sm text-gray-500">El código no corresponde a ningún certificado emitido por {{ $marca->nombre() }}.</p>
        @endif
    </div>
</x-guest-layout>
