@php
    $p = $turno->paciente;
    $campo = 'w-full rounded-xl border-gray-200 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 tabular-nums disabled:bg-gray-50';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
    $rangos = \App\Models\Preparacion::RANGOS;
    // Resalta mientras se escribe un valor fuera del rango habitual
    $fuera = fn ($c) => $this->{$c} !== null && $this->{$c} !== '' && isset($rangos[$c]) && ((float) $this->{$c} < $rangos[$c][0] || (float) $this->{$c} > $rangos[$c][1]);
    $grupos = [
        'Constantes vitales' => [
            ['presion_sistolica', 'PA sistólica', 'mmHg', '1'], ['presion_diastolica', 'PA diastólica', 'mmHg', '1'],
            ['frecuencia_cardiaca', 'Pulso', 'lpm', '1'], ['frecuencia_respiratoria', 'Frec. respiratoria', 'rpm', '1'],
            ['temperatura', 'Temperatura', '°C', '0.1'], ['saturacion', 'Pulsioximetría', '%', '1'],
        ],
        'Antropometría y pruebas rápidas' => [
            ['peso', 'Peso', 'kg', '0.01'], ['talla', 'Talla', 'm', '0.01'], ['perimetro_abdominal', 'Perímetro abdominal', 'cm', '0.1'],
            ['glucosa_capilar', 'Glucosa capilar', 'mg/dl', '0.1'], ['hemoglobina_capilar', 'Hemoglobina capilar', 'g/dl', '0.1'],
        ],
    ];
@endphp
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">
    <a href="{{ route('sala-espera') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Sala de espera
    </a>

    <div>
        <p class="text-sm text-gray-500">Preparación · turno {{ $turno->codigo }} · {{ $turno->especialidad?->nombre }}</p>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $p?->nombre_completo ?? $turno->nombre_temporal }}</h1>
        @if ($p)
            <p class="text-sm text-gray-500 mt-1"><span class="font-mono">{{ $p->cedula }}</span>{{ $p->edad !== null ? ' · ' . $p->edad . ' años' : '' }} · <span class="capitalize">{{ $p->sexo }}</span>{{ $turno->motivo ? ' · ' . $turno->motivo : '' }}</p>
        @endif
    </div>

    @if (!$p)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            Este turno aún no está vinculado a un paciente registrado. Registre al paciente para guardar sus signos vitales en la historia clínica.
        </div>
    @elseif (!$this->editable)
        <div class="rounded-2xl border border-gray-200 bg-gray-50 px-5 py-3 text-sm text-gray-600">El turno ya fue {{ $turno->estado === 'cancelado' ? 'cancelado' : 'atendido' }}: los signos se muestran solo para consulta.</div>
    @endif

    @if ($turno->preparacion)
        <p class="text-xs text-gray-500">Registrado por {{ $turno->preparacion->user?->name ?? '—' }} a las {{ $turno->preparacion->fecha->format('H:i') }}{{ $turno->preparacion->updated_at->ne($turno->preparacion->created_at) ? ' · editado ' . $turno->preparacion->updated_at->format('H:i') : '' }}.</p>
    @endif

    <form wire:submit="guardar" class="space-y-5">
        @foreach ($grupos as $titulo => $campos)
            <section class="bg-white rounded-2xl border border-gray-200/80 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">{{ $titulo }}</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @foreach ($campos as [$c, $texto, $unidad, $paso])
                        <div>
                            <label for="{{ $c }}" class="{{ $etiqueta }}">{{ $texto }} <span class="text-gray-400 font-normal">({{ $unidad }})</span></label>
                            <input id="{{ $c }}" wire:model.live.debounce.500ms="{{ $c }}" type="number" step="{{ $paso }}" inputmode="decimal" @disabled(!$this->editable)
                                   class="{{ $campo }} {{ $fuera($c) ? '!border-amber-400 bg-amber-50' : '' }}">
                            @if ($fuera($c))
                                <p class="text-[11px] text-amber-700 mt-1">Fuera del rango habitual ({{ $rangos[$c][0] }}–{{ $rangos[$c][1] }})</p>
                            @endif
                            @error($c) <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                    @if ($titulo !== 'Constantes vitales')
                        <div>
                            <span class="{{ $etiqueta }}">IMC <span class="text-gray-400 font-normal">(kg/m²)</span></span>
                            <p class="h-[38px] flex items-center px-3 rounded-xl bg-gray-50 text-sm text-gray-700 tabular-nums">{{ $this->imc ?? '—' }}</p>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach

        <section class="bg-white rounded-2xl border border-gray-200/80 p-5">
            <label for="observaciones" class="{{ $etiqueta }}">Observaciones de enfermería</label>
            <textarea id="observaciones" wire:model="observaciones" rows="3" @disabled(!$this->editable) class="{{ $campo }}" placeholder="Ej.: refiere dolor 6/10, acude con acompañante…"></textarea>
            @error('observaciones') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </section>

        <p class="text-xs text-gray-400">Los valores resaltados solo indican que están fuera del rango habitual de un adulto; la interpretación es del médico.</p>

        @if ($this->editable)
            <div class="flex justify-end gap-2">
                <a href="{{ route('sala-espera') }}" wire:navigate class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 shadow-sm">Guardar signos vitales</button>
            </div>
        @endif
    </form>
</div>
