@php
    $campo = 'w-full rounded-xl border-gray-200 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
    $textos = [
        ['en_que_consiste', '¿En qué consiste?', 'Explicación clara del procedimiento, en palabras que el paciente entienda.', 3],
        ['como_se_realiza', '¿Cómo se realiza?', null, 3],
        ['beneficios', 'Beneficios del procedimiento', null, 2],
        ['riesgos_frecuentes', 'Riesgos frecuentes (poco graves)', null, 2],
        ['riesgos_graves', 'Riesgos poco frecuentes (graves)', null, 2],
        ['riesgos_especificos', 'Riesgos específicos relacionados con el paciente', 'Edad, estado de salud, alergias, enfermedades, creencias, valores. Se precarga con lo registrado en la ficha: revíselo.', 3],
        ['alternativas', 'Alternativas al procedimiento', null, 2],
        ['manejo_posterior', 'Descripción del manejo posterior al procedimiento', null, 2],
        ['consecuencias_no_realizar', 'Consecuencias posibles si no se realiza el procedimiento', null, 2],
    ];
@endphp
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <div>
        <a href="{{ route('pacientes.ver', $paciente) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            {{ $paciente->nombre_completo }}
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $consentimientoId ? 'Corregir consentimiento informado' : 'Consentimiento informado' }}</h1>
        <p class="text-sm text-gray-500 mt-1">Formulario SNS-MSP/HCU-form.024 · C.I. {{ $paciente->cedula }}@if ($paciente->edad !== null) · {{ $paciente->edad }} años @endif</p>
    </div>

    @if ($menorDeEdad)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            <p class="font-medium">Paciente menor de edad</p>
            <p>El consentimiento debe ser suscrito por el padre, la madre o el representante legal. Registre sus datos al momento de la firma.</p>
        </div>
    @endif

    <details class="rounded-2xl border border-gray-200 bg-white px-5 py-4 text-sm text-gray-600">
        <summary class="cursor-pointer font-medium text-gray-800">¿Cuándo no se necesita consentimiento informado?</summary>
        <ul class="mt-3 space-y-1.5 list-disc pl-5">
            <li><span class="font-medium">Emergencia y urgencia</span>, cuando no es posible informar al paciente ni contactar a sus familiares. Debe quedar fundamentado por escrito en la historia clínica.</li>
            <li><span class="font-medium">Privilegio terapéutico</span>: cuando, a juicio del profesional, la información puede perjudicar el curso de la enfermedad.</li>
            <li><span class="font-medium">Tratamientos exigidos por ley</span>, ante un peligro para la salud pública.</li>
            <li><span class="font-medium">Corregir una alteración</span> inesperada surgida durante otra intervención.</li>
            <li><span class="font-medium">Intervenciones de riesgo menor</span> (no mayor que un examen físico de rutina): análisis de orina, extracción de sangre venosa, electroencefalograma, pruebas de alergia, pruebas psicológicas estándar.</li>
        </ul>
        <p class="mt-3 text-xs text-gray-500">Fuente: instructivo del formulario SNS-MSP/HCU-form.024 (AM 00115-2021). El formulario no se usa en el servicio de emergencia.</p>
    </details>

    <form wire:submit="guardar" class="space-y-5">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1">
                    <label class="{{ $etiqueta }}">Usar plantilla</label>
                    <select wire:model.live="plantillaId" class="{{ $campo }}">
                        <option value="">— Escribir desde cero —</option>
                        @foreach ($plantillas as $pl)
                            <option value="{{ $pl->id }}">{{ $pl->procedimiento }}</option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('consentimientos.plantillas') }}" wire:navigate class="text-sm font-medium text-blue-700 hover:underline sm:pb-2.5">Administrar plantillas</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="{{ $etiqueta }}">Nombre del procedimiento recomendado *</label>
                    <input wire:model="procedimiento" type="text" class="{{ $campo }}" placeholder="Ej.: Colocación de dispositivo intrauterino">
                    @error('procedimiento') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Servicio *</label>
                    <input wire:model="servicio" type="text" class="{{ $campo }}">
                    @error('servicio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Tipo de atención *</label>
                    <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 text-sm w-full">
                        @foreach (['ambulatorio' => 'Ambulatorio', 'hospitalizacion' => 'Hospitalización'] as $valor => $texto)
                            <button type="button" wire:click="$set('tipo_atencion', '{{ $valor }}')"
                                    class="flex-1 px-3 py-1.5 rounded-lg transition {{ $tipo_atencion === $valor ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">{{ $texto }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="sm:col-span-3">
                        <label class="{{ $etiqueta }}">Diagnóstico *</label>
                        <input wire:model="diagnostico" type="text" class="{{ $campo }}">
                        @error('diagnostico') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $etiqueta }}">CIE-10</label>
                        <input wire:model="codigo_cie10" type="text" class="{{ $campo }} font-mono uppercase">
                    </div>
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Fecha en que se informa *</label>
                    <input wire:model="fecha_informacion" type="date" class="{{ $campo }}">
                    @error('fecha_informacion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Hora</label>
                    <input wire:model="hora_informacion" type="time" class="{{ $campo }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $etiqueta }}">Duración estimada de la intervención *</label>
                    <input wire:model="duracion_estimada" type="text" class="{{ $campo }}" placeholder="Ej.: 20 minutos">
                    @error('duracion_estimada') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-900">Información para el paciente</h2>
            @foreach ($textos as [$nombre, $titulo, $ayuda, $filas])
                <div>
                    <label class="{{ $etiqueta }}">{{ $titulo }}{{ $nombre === 'riesgos_especificos' ? '' : ' *' }}</label>
                    @if ($ayuda)<p class="text-xs text-gray-400 mb-1">{{ $ayuda }}</p>@endif
                    <textarea wire:model="{{ $nombre }}" rows="{{ $filas }}" class="{{ $campo }}"></textarea>
                    @error($nombre) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div>
                <label class="{{ $etiqueta }}">Gráfico de la intervención (opcional)</label>
                <p class="text-xs text-gray-400 mb-1">Una imagen que ayude al paciente a entender cómo se realiza. PNG o JPG, máximo 2 MB.</p>
                <input wire:model="grafico" type="file" accept="image/png,image/jpeg" class="block w-full text-sm text-gray-600 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700">
                @error('grafico') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @if ($graficoActual && !$grafico)
                    <label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" wire:model="quitarGrafico" class="rounded border-gray-300"> Quitar el gráfico actual
                    </label>
                @endif
            </div>
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
            <button type="button" wire:click="guardarComoPlantilla" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                Guardar textos como plantilla
            </button>
            <button type="submit" wire:loading.attr="disabled" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm">
                {{ $consentimientoId ? 'Guardar cambios' : 'Guardar y preparar para firma' }}
            </button>
        </div>
    </form>
</div>
