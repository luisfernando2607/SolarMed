{{-- Signos vitales tomados por enfermería antes de la consulta. Uso: @include('livewire.partials.resumen-preparacion', ['preparacion' => $turno->preparacion]) --}}
@if ($preparacion)
    @php
        $fuera = $preparacion->fueraDeRango();
        $valores = [
            ['PA', $preparacion->presion(), 'mmHg', in_array('presion_sistolica', $fuera) || in_array('presion_diastolica', $fuera)],
            ['Pulso', $preparacion->frecuencia_cardiaca, 'lpm', in_array('frecuencia_cardiaca', $fuera)],
            ['FR', $preparacion->frecuencia_respiratoria, 'rpm', in_array('frecuencia_respiratoria', $fuera)],
            ['Temp.', $preparacion->temperatura, '°C', in_array('temperatura', $fuera)],
            ['SatO₂', $preparacion->saturacion, '%', in_array('saturacion', $fuera)],
            ['Peso', $preparacion->peso, 'kg', false],
            ['Talla', $preparacion->talla, 'm', false],
            ['IMC', $preparacion->imc(), '', false],
            ['P. abdominal', $preparacion->perimetro_abdominal, 'cm', false],
            ['Glucosa', $preparacion->glucosa_capilar, 'mg/dl', false],
            ['Hb', $preparacion->hemoglobina_capilar, 'g/dl', false],
        ];
    @endphp
    <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 mb-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
            <p class="text-sm font-semibold text-gray-900">Preparación de enfermería</p>
            <p class="text-xs text-gray-500">{{ $preparacion->user?->name ?? '—' }} · {{ $preparacion->fecha->format('H:i') }}</p>
        </div>
        <dl class="flex flex-wrap gap-2">
            @foreach ($valores as [$t, $v, $u, $alerta])
                @if ($v !== null && $v !== '')
                    <div class="rounded-xl px-3 py-1.5 {{ $alerta ? 'bg-amber-50 ring-1 ring-amber-300' : 'bg-gray-50' }}" @if ($alerta) title="Fuera del rango habitual" @endif>
                        <dt class="text-[11px] {{ $alerta ? 'text-amber-800' : 'text-gray-500' }}">{{ $t }}</dt>
                        <dd class="text-sm font-semibold tabular-nums {{ $alerta ? 'text-amber-900' : 'text-gray-900' }}">{{ $v }} <span class="text-[11px] font-normal text-gray-400">{{ $u }}</span></dd>
                    </div>
                @endif
            @endforeach
        </dl>
        @if ($preparacion->observaciones)
            <p class="text-sm text-gray-600 mt-3"><span class="text-gray-400">Observaciones:</span> {{ $preparacion->observaciones }}</p>
        @endif
    </div>
@endif
