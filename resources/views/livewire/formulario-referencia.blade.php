@php
    $campo = 'w-full rounded-xl border-gray-200 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
    $error = fn ($c) => $errors->has($c) ? '<p class="text-red-600 text-xs mt-1">' . e($errors->first($c)) . '</p>' : '';
@endphp
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <a href="{{ route('pacientes.ver', $paciente) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        {{ $paciente->nombre_visible }}
    </a>

    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $referenciaId ? 'Editar referencia' : 'Nueva referencia o derivación' }}</h1>
        <p class="text-sm text-gray-500 mt-1">Formulario SNS-MSP/HCU-form.053/2021 · {{ $paciente->nombre_completo }} · C.I. {{ $paciente->cedula }}</p>
    </div>

    <form wire:submit="guardar" class="space-y-5">
        {{-- Sentido y tipo --}}
        <section class="bg-white rounded-2xl border border-gray-200/80 p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="{{ $etiqueta }}">El paciente…</span>
                    <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 text-sm">
                        @foreach (['saliente' => 'Se refiere desde esta clínica', 'entrante' => 'Llega referido a esta clínica'] as $valor => $texto)
                            <button type="button" wire:click="$set('direccion', '{{ $valor }}')"
                                    class="px-3 py-1.5 rounded-lg transition {{ $direccion === $valor ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">{{ $texto }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <span class="{{ $etiqueta }}">Tipo</span>
                    <div class="flex gap-4 text-sm pt-2">
                        @foreach (\App\Models\Referencia::TIPOS as $valor => $texto)
                            <label class="inline-flex items-center gap-2"><input type="radio" wire:model="tipo" value="{{ $valor }}" class="text-blue-600 focus:ring-blue-500"> {{ $texto }}</label>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">Referencia: dentro del mismo subsistema. Derivación: a otro subsistema (p. ej. de la red privada al IESS).</p>
                </div>
            </div>

            @if ($direccion === 'entrante')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 rounded-xl bg-gray-50 p-4">
                    <div>
                        <label class="{{ $etiqueta }}">Establecimiento que refiere *</label>
                        <input wire:model="establecimiento_origen" type="text" class="{{ $campo }}">
                        {!! $error('establecimiento_origen') !!}
                    </div>
                    <div>
                        <label class="{{ $etiqueta }}">Institución</label>
                        <select wire:model="institucion_origen" class="{{ $campo }}">
                            <option value="">—</option>
                            @foreach (\App\Models\Referencia::INSTITUCIONES as $inst)<option value="{{ $inst }}">{{ $inst }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $etiqueta }}">Profesional que refiere</label>
                        <input wire:model="profesional_origen" type="text" class="{{ $campo }}">
                    </div>
                </div>
            @endif

            <div>
                <span class="{{ $etiqueta }}">Motivo *</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
                    @foreach (\App\Models\Referencia::MOTIVOS as $n => $texto)
                        <label class="inline-flex items-start gap-2"><input type="radio" wire:model="motivo" value="{{ $n }}" class="mt-0.5 text-blue-600 focus:ring-blue-500"> <span><span class="text-gray-400 tabular-nums">{{ $n }}.</span> {{ $texto }}</span></label>
                    @endforeach
                </div>
                {!! $error('motivo') !!}
            </div>
        </section>

        {{-- B. Destino --}}
        <section class="bg-white rounded-2xl border border-gray-200/80 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">B. Establecimiento al que se refiere / deriva</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="{{ $etiqueta }}">Institución del sistema</label>
                    <select wire:model="institucion_destino" class="{{ $campo }}">
                        @foreach (\App\Models\Referencia::INSTITUCIONES as $inst)<option value="{{ $inst }}">{{ $inst }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Establecimiento de salud{{ $direccion === 'saliente' ? ' *' : '' }}</label>
                    <input wire:model="establecimiento_destino" type="text" placeholder="{{ $direccion === 'entrante' ? $marca->nombre() : '' }}" class="{{ $campo }}">
                    {!! $error('establecimiento_destino') !!}
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Servicio</label>
                    <input wire:model="servicio" type="text" class="{{ $campo }}">
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Especialidad *</label>
                    <input wire:model="especialidad" type="text" class="{{ $campo }}">
                    {!! $error('especialidad') !!}
                </div>
            </div>
        </section>

        {{-- C, D --}}
        <section class="bg-white rounded-2xl border border-gray-200/80 p-5 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">C. Resumen del cuadro clínico *</label>
                <textarea wire:model="resumen_cuadro_clinico" rows="5" class="{{ $campo }}"></textarea>
                {!! $error('resumen_cuadro_clinico') !!}
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">D. Hallazgos relevantes de exámenes y procedimientos diagnósticos</label>
                <textarea wire:model="hallazgos" rows="3" class="{{ $campo }}"></textarea>
            </div>
        </section>

        {{-- E. Diagnósticos --}}
        <section class="bg-white rounded-2xl border border-gray-200/80 p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-900">E. Diagnóstico <span class="font-normal text-gray-400">(máximo {{ \App\Models\Referencia::MAX_DIAGNOSTICOS }})</span></h2>
                @if (count($diagnosticos) < \App\Models\Referencia::MAX_DIAGNOSTICOS)
                    <button type="button" wire:click="agregarDiagnostico" class="text-sm font-medium text-blue-700 hover:underline">+ Agregar</button>
                @endif
            </div>
            <div class="space-y-3">
                @foreach ($diagnosticos as $i => $d)
                    <div class="grid grid-cols-12 gap-2 items-start" wire:key="dx-{{ $i }}">
                        <span class="col-span-1 pt-2 text-sm text-gray-400 tabular-nums">{{ $i + 1 }}.</span>
                        <div class="col-span-11 sm:col-span-6">
                            <input wire:model="diagnosticos.{{ $i }}.descripcion" type="text" placeholder="Diagnóstico" class="{{ $campo }}">
                            {!! $error("diagnosticos.{$i}.descripcion") !!}
                        </div>
                        <div class="col-span-5 col-start-2 sm:col-start-auto sm:col-span-2">
                            <input wire:model="diagnosticos.{{ $i }}.cie10" type="text" placeholder="CIE-10" class="{{ $campo }} font-mono uppercase">
                            {!! $error("diagnosticos.{$i}.cie10") !!}
                        </div>
                        <select wire:model="diagnosticos.{{ $i }}.tipo" class="col-span-5 sm:col-span-2 {{ $campo }}">
                            <option value="pre">Presuntivo</option>
                            <option value="def">Definitivo</option>
                        </select>
                        <button type="button" wire:click="quitarDiagnostico({{ $i }})" class="col-span-1 p-2 text-gray-400 hover:text-red-600" aria-label="Quitar diagnóstico">✕</button>
                    </div>
                @endforeach
            </div>
            {!! $error('diagnosticos') !!}
        </section>

        <div class="flex justify-end gap-2">
            <a href="{{ route('pacientes.ver', $paciente) }}" wire:navigate class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm">Guardar referencia</button>
        </div>
    </form>
</div>
