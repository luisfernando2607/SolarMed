@php
    $r = $referencia;
    $entrante = $r->direccion === 'entrante';
    $campo = 'w-full rounded-xl border-gray-200 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
    $error = fn ($c) => $errors->has($c) ? '<p class="text-red-600 text-xs mt-1">' . e($errors->first($c)) . '</p>' : '';
@endphp
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <a href="{{ route('referencias', ['paciente' => $r->paciente_id]) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Referencias
    </a>

    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $entrante ? 'Emitir contrarreferencia' : 'Registrar contrarreferencia recibida' }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $r->numero() }} · {{ $r->paciente->nombre_completo }} · {{ \App\Models\Referencia::TIPOS[$r->tipo] }} a {{ $r->especialidad }}{{ $r->establecimiento_destino ? ' (' . $r->establecimiento_destino . ')' : '' }}</p>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
        <p class="font-medium text-gray-800 mb-1">Resumen de la referencia</p>
        <p class="whitespace-pre-line">{{ \Illuminate\Support\Str::limit($r->resumen_cuadro_clinico, 400) }}</p>
    </div>

    <form wire:submit="guardar" class="space-y-5">
        <section class="bg-white rounded-2xl border border-gray-200/80 p-5 space-y-4">
            <div class="flex flex-wrap gap-5 text-sm">
                @foreach (\App\Models\Referencia::TIPOS_CONTRA as $valor => $texto)
                    <label class="inline-flex items-center gap-2"><input type="radio" wire:model="contra_tipo" value="{{ $valor }}" class="text-blue-600 focus:ring-blue-500"> {{ $texto }}</label>
                @endforeach
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label class="{{ $etiqueta }}">A. Establecimiento que contrarrefiere *</label>
                    <input wire:model="contra_establecimiento" type="text" class="{{ $campo }}" @if ($entrante) readonly @endif>
                    {!! $error('contra_establecimiento') !!}
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Institución</label>
                    <select wire:model="contra_institucion" class="{{ $campo }}">
                        <option value="">—</option>
                        @foreach (\App\Models\Referencia::INSTITUCIONES as $inst)<option value="{{ $inst }}">{{ $inst }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Fecha *</label>
                    <input wire:model="contra_fecha" type="date" class="{{ $campo }}">
                    {!! $error('contra_fecha') !!}
                </div>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-gray-200/80 p-5 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">C. Resumen del cuadro clínico *</label>
                <textarea wire:model="contra_resumen" rows="4" class="{{ $campo }}"></textarea>
                {!! $error('contra_resumen') !!}
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">D. Hallazgos relevantes de exámenes y procedimientos diagnósticos</label>
                <textarea wire:model="contra_hallazgos" rows="3" class="{{ $campo }}"></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">E. Tratamientos y procedimientos terapéuticos realizados</label>
                <textarea wire:model="contra_tratamientos_realizados" rows="3" class="{{ $campo }}"></textarea>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-gray-200/80 p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-900">F. Diagnóstico <span class="font-normal text-gray-400">(máximo {{ \App\Models\Referencia::MAX_DIAGNOSTICOS_CONTRA }})</span></h2>
                @if (count($contra_diagnosticos) < \App\Models\Referencia::MAX_DIAGNOSTICOS_CONTRA)
                    <button type="button" wire:click="agregarDiagnostico" class="text-sm font-medium text-blue-700 hover:underline">+ Agregar</button>
                @endif
            </div>
            <div class="space-y-3">
                @foreach ($contra_diagnosticos as $i => $d)
                    <div class="grid grid-cols-12 gap-2 items-start" wire:key="cdx-{{ $i }}">
                        <div class="col-span-12 sm:col-span-7">
                            <input wire:model="contra_diagnosticos.{{ $i }}.descripcion" type="text" placeholder="Diagnóstico" class="{{ $campo }}">
                            {!! $error("contra_diagnosticos.{$i}.descripcion") !!}
                        </div>
                        <div class="col-span-5 sm:col-span-2">
                            <input wire:model="contra_diagnosticos.{{ $i }}.cie10" type="text" placeholder="CIE-10" class="{{ $campo }} font-mono uppercase">
                            {!! $error("contra_diagnosticos.{$i}.cie10") !!}
                        </div>
                        <select wire:model="contra_diagnosticos.{{ $i }}.tipo" class="col-span-5 sm:col-span-2 {{ $campo }}">
                            <option value="pre">Presuntivo</option>
                            <option value="def">Definitivo</option>
                        </select>
                        <button type="button" wire:click="quitarDiagnostico({{ $i }})" class="col-span-2 sm:col-span-1 p-2 text-gray-400 hover:text-red-600" aria-label="Quitar diagnóstico">✕</button>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-gray-200/80 p-5 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">G. Tratamiento recomendado a seguir en el establecimiento al que se contrarrefiere *</label>
                <textarea wire:model="contra_tratamiento_recomendado" rows="3" class="{{ $campo }}"></textarea>
                {!! $error('contra_tratamiento_recomendado') !!}
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $etiqueta }}">H. Profesional responsable *</label>
                    <input wire:model="contra_profesional" type="text" class="{{ $campo }}" @if ($entrante) readonly @endif>
                    {!! $error('contra_profesional') !!}
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Cédula del profesional</label>
                    <input wire:model="contra_profesional_cedula" type="text" class="{{ $campo }}" @if ($entrante) readonly @endif>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-gray-200/80 p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-900">Evaluación de la referencia / derivación (bloque G)</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                @foreach (['referencia_justificada' => 'Referencia justificada', 'derivacion_justificada' => 'Derivación justificada'] as $campoEval => $textoEval)
                    <div class="flex items-center gap-4">
                        <span class="text-gray-700 w-44">{{ $textoEval }}</span>
                        <label class="inline-flex items-center gap-1.5"><input type="radio" wire:model="{{ $campoEval }}" value="si" class="text-blue-600 focus:ring-blue-500"> Sí</label>
                        <label class="inline-flex items-center gap-1.5"><input type="radio" wire:model="{{ $campoEval }}" value="no" class="text-blue-600 focus:ring-blue-500"> No</label>
                    </div>
                @endforeach
            </div>
            @unless ($entrante)
                <div>
                    <label class="{{ $etiqueta }}">Documento de contrarreferencia recibido (PDF, JPG o PNG)</label>
                    <input wire:model="documento" type="file" accept="application/pdf,image/jpeg,image/png" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium">
                    {!! $error('documento') !!}
                </div>
            @endunless
        </section>

        <div class="flex justify-end gap-2">
            <a href="{{ route('referencias', ['paciente' => $r->paciente_id]) }}" wire:navigate class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</a>
            <button type="submit" wire:loading.attr="disabled" class="px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm">Guardar {{ $contra_tipo === 'referencia_inversa' ? 'referencia inversa' : 'contrarreferencia' }}</button>
        </div>
    </form>
</div>
