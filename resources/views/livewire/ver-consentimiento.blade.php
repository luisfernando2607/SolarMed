@php
    $p = $c->paciente;
    $campo = 'w-full rounded-xl border-gray-200 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
    $boton = 'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition';
    $bloques = [
        ['¿En qué consiste?', $c->en_que_consiste],
        ['¿Cómo se realiza?', $c->como_se_realiza],
        ['Duración estimada', $c->duracion_estimada],
        ['Beneficios', $c->beneficios],
        ['Riesgos frecuentes (poco graves)', $c->riesgos_frecuentes],
        ['Riesgos poco frecuentes (graves)', $c->riesgos_graves],
        ['Riesgos específicos del paciente', $c->riesgos_especificos],
        ['Alternativas', $c->alternativas],
        ['Manejo posterior', $c->manejo_posterior],
        ['Consecuencias si no se realiza', $c->consecuencias_no_realizar],
    ];
@endphp
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5" x-data="{ accion: null }">

    <div>
        <a href="{{ route('pacientes.ver', $p) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            {{ $p->nombre_completo }}
        </a>
    </div>

    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">{{ $c->procedimiento }}</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $c->estadoTono() }}">{{ $c->estadoTexto() }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Consentimiento informado {{ $c->numero() }} · informado el {{ $c->fecha_informacion->format('d/m/Y') }}{{ $c->hora_informacion ? ' ' . $c->hora_informacion : '' }} · {{ $c->medico?->nombre_completo }}</p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <a href="{{ route('consentimientos.pdf', $c) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
                Imprimir formulario 024
            </a>
            @if ($c->documento_firmado_ruta)
                <a href="{{ route('consentimientos.documento', $c) }}" target="_blank" class="{{ $boton }}">Ver formulario firmado</a>
            @endif
            @can('expediente.crear')
                @if ($c->editable() && $esAutor)
                    <a href="{{ route('consentimientos.editar', $c) }}" wire:navigate class="{{ $boton }}">Corregir</a>
                @endif
            @endcan
        </div>
    </div>

    {{-- Estado y firmas --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-900">Firmas</h2>
        @error('accion') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        @if ($c->estado === 'pendiente_firma')
            <p class="text-sm text-gray-600">Imprima el formulario, explíquelo al paciente, pídale que diga con sus palabras lo que entendió y, si está de acuerdo, que firme el bloque C con esferográfico azul. Usted firma, sella y anota su código. Luego registre aquí el resultado.</p>
        @else
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                @if ($c->fecha_aceptacion)
                    <div><dt class="text-xs text-gray-400">Aceptado (bloque C)</dt><dd class="text-gray-800">{{ $c->fecha_aceptacion->format('d/m/Y H:i') }}</dd></div>
                @endif
                @if ($c->fecha_negativa)
                    <div><dt class="text-xs text-gray-400">Negativa (bloque D)</dt><dd class="text-gray-800">{{ $c->fecha_negativa->format('d/m/Y H:i') }}@if ($c->negativa_sin_firma) · firmó el testigo {{ $c->testigo_nombre }} (C.I. {{ $c->testigo_cedula }})@endif</dd></div>
                @endif
                @if ($c->fecha_revocatoria)
                    <div><dt class="text-xs text-gray-400">Revocado (bloque E)</dt><dd class="text-gray-800">{{ $c->fecha_revocatoria->format('d/m/Y H:i') }}@if ($c->motivo_revocatoria) · {{ $c->motivo_revocatoria }}@endif</dd></div>
                @endif
                @if ($c->representante_nombre)
                    <div><dt class="text-xs text-gray-400">Firmó como representante legal</dt><dd class="text-gray-800">{{ $c->representante_nombre }} · C.I. {{ $c->representante_cedula }} · {{ $c->representante_parentesco }}</dd></div>
                @endif
                <div>
                    <dt class="text-xs text-gray-400">Formulario firmado</dt>
                    <dd class="{{ $c->documento_firmado_ruta ? 'text-gray-800' : 'text-amber-700' }}">{{ $c->documento_firmado_ruta ? 'Adjuntado el ' . $c->documento_firmado_at->format('d/m/Y H:i') : 'Aún no se adjunta la copia firmada' }}</dd>
                </div>
            </dl>
        @endif

        @can('expediente.crear')
            <div class="flex flex-wrap gap-2 pt-1">
                @if ($c->estado === 'pendiente_firma')
                    <button type="button" @click="accion = accion === 'aceptar' ? null : 'aceptar'" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Registrar aceptación</button>
                    <button type="button" @click="accion = accion === 'negar' ? null : 'negar'" class="px-4 py-2 rounded-xl border border-red-200 bg-white text-sm font-medium text-red-700 hover:bg-red-50">Registrar negativa</button>
                    @if ($esAutor)
                        <button type="button" x-on:click="Swal.fire({ icon: 'warning', title: '¿Descartar este borrador?', text: 'Aún no está firmado. Esta acción no se puede deshacer.', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Descartar', cancelButtonText: 'Cancelar' }).then(r => { if (r.isConfirmed) $wire.descartar() })"
                                class="px-4 py-2 rounded-xl text-sm text-gray-500 hover:text-red-700">Descartar borrador</button>
                    @endif
                @elseif ($c->estado === 'aceptado')
                    <button type="button" @click="accion = accion === 'revocar' ? null : 'revocar'" class="px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">Registrar revocatoria</button>
                @endif
                @if ($c->estado !== 'pendiente_firma')
                    <button type="button" @click="accion = accion === 'documento' ? null : 'documento'" class="px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">{{ $c->documento_firmado_ruta ? 'Reemplazar copia firmada' : 'Adjuntar formulario firmado' }}</button>
                @endif
            </div>

            @if (in_array($c->estado, ['pendiente_firma', 'aceptado'], true))
                {{-- Representante legal: común a aceptación, negativa y revocatoria --}}
                <div x-show="['aceptar', 'negar', 'revocar'].includes(accion)" x-cloak class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3">
                    <label class="flex items-center gap-2 text-sm text-gray-800">
                        <input type="checkbox" wire:model.live="firmaRepresentante" @disabled($c->requiereRepresentante()) class="rounded-sm border-gray-300 text-blue-600">
                        Firma el representante legal (paciente menor de edad o sin capacidad para firmar)
                    </label>
                    @if ($firmaRepresentante)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="{{ $etiqueta }}">Nombre del representante *</label>
                                <input wire:model="representante_nombre" type="text" class="{{ $campo }}">
                                @error('representante_nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $etiqueta }}">Cédula *</label>
                                <input wire:model="representante_cedula" type="text" class="{{ $campo }}">
                                @error('representante_cedula') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $etiqueta }}">Parentesco *</label>
                                <input wire:model="representante_parentesco" type="text" class="{{ $campo }}" placeholder="Madre, padre, tutor…">
                                @error('representante_parentesco') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif

                    <div x-show="accion === 'aceptar'" class="flex justify-end">
                        <button type="button" wire:click="aceptar" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Confirmar: el formulario fue firmado</button>
                    </div>

                    <div x-show="accion === 'negar'" class="space-y-3">
                        <p class="text-sm text-gray-600">El paciente entendió el procedimiento y sus consecuencias, y no lo autoriza (bloque D).</p>
                        <label class="flex items-center gap-2 text-sm text-gray-800">
                            <input type="checkbox" wire:model.live="negativaSinFirma" class="rounded-sm border-gray-300 text-blue-600">
                            El paciente tampoco acepta firmar la negativa (firma un testigo)
                        </label>
                        @if ($negativaSinFirma)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $etiqueta }}">Nombre del testigo *</label>
                                    <input wire:model="testigo_nombre" type="text" class="{{ $campo }}">
                                    @error('testigo_nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $etiqueta }}">Cédula del testigo *</label>
                                    <input wire:model="testigo_cedula" type="text" class="{{ $campo }}">
                                    @error('testigo_cedula') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif
                        <div class="flex justify-end">
                            <button type="button" wire:click="negar" class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-medium hover:bg-red-700">Registrar negativa</button>
                        </div>
                    </div>

                    <div x-show="accion === 'revocar'" class="space-y-3">
                        <p class="text-sm text-gray-600">El paciente retira su consentimiento de forma libre y voluntaria (bloque E). No afecta la atención que recibe.</p>
                        <div>
                            <label class="{{ $etiqueta }}">Observación (opcional)</label>
                            <input wire:model="motivo_revocatoria" type="text" class="{{ $campo }}">
                        </div>
                        <div class="flex justify-end">
                            <button type="button" wire:click="revocar" class="px-4 py-2 rounded-xl bg-gray-800 text-white text-sm font-medium hover:bg-gray-900">Registrar revocatoria</button>
                        </div>
                    </div>
                </div>
            @endif

            @if ($c->estado !== 'pendiente_firma')
                <div x-show="accion === 'documento'" x-cloak class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3">
                    <p class="text-sm text-gray-600">Escanee o fotografíe el formulario con las firmas. PDF, JPG o PNG.</p>
                    <input wire:model="documento" type="file" accept="application/pdf,image/png,image/jpeg" class="block w-full text-sm text-gray-600 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-white file:text-gray-700">
                    @error('documento') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    <div class="flex justify-end">
                        <button type="button" wire:click="subirDocumento" wire:loading.attr="disabled" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Adjuntar</button>
                    </div>
                </div>
            @endif
        @endcan
    </div>

    {{-- Bloque B --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mb-5 pb-5 border-b border-gray-100">
            <div><dt class="text-xs text-gray-400">Servicio</dt><dd class="text-gray-800">{{ $c->servicio }} · {{ $c->tipo_atencion === 'hospitalizacion' ? 'Hospitalización' : 'Ambulatorio' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-gray-400">Diagnóstico</dt><dd class="text-gray-800">{{ $c->diagnostico }}@if ($c->codigo_cie10) <span class="ml-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-mono text-xs">{{ $c->codigo_cie10 }}</span>@endif</dd></div>
        </dl>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            @foreach ($bloques as [$titulo, $texto])
                <div><dt class="text-xs text-gray-400">{{ $titulo }}</dt><dd class="text-gray-800 whitespace-pre-line">{{ filled($texto) ? $texto : 'NA' }}</dd></div>
            @endforeach
        </dl>
        @if ($c->grafico_ruta)
            <div class="mt-5 pt-5 border-t border-gray-100">
                <p class="text-xs text-gray-400 mb-2">Gráfico de la intervención</p>
                <img src="{{ route('consentimientos.grafico', $c) }}" alt="Gráfico de la intervención" class="max-h-72 rounded-xl border border-gray-200">
            </div>
        @endif
    </div>
</div>
