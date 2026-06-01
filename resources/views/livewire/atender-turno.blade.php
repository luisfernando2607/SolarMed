<div x-data x-on:notify.window="Swal.fire({
    icon: $event.detail.type,
    title: $event.detail.type === 'success' ? 'Guardado' : 'Error',
    text: $event.detail.message,
    timer: 3000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
})">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <a href="{{ route('sala-espera') }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">&larr; Sala de Espera</a>
                <h1 class="text-2xl font-bold text-gray-900 mt-1">Atendiendo turno #{{ $turno->codigo }}</h1>
                <p class="text-sm text-gray-500">{{ $turno->especialidad->nombre }} &middot; {{ $turno->fecha->format('d/m/Y') }}</p>
            </div>
            @can('turnos.gestionar')
                <button wire:click="marcarCompletado"
                    class="px-5 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition shadow-sm">
                    Finalizar atención
                </button>
            @endcan
        </div>

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">{{ session('error') }}</div>
        @endif

        {{-- ====== DATOS DEL PACIENTE ====== --}}
        <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Datos del paciente</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">Nombre:</span> <span class="font-medium">{{ $turno->paciente?->nombre_completo ?? $turno->nombre_temporal }}</span></div>
                <div><span class="text-gray-500">Cédula:</span> <span>{{ $turno->cedula }}</span></div>
                <div><span class="text-gray-500">Teléfono:</span> <span>{{ $turno->paciente?->telefono ?? $turno->telefono }}</span></div>
                <div><span class="text-gray-500">Edad:</span> <span>{{ $turno->paciente?->edad ?? '--' }} años</span></div>
                <div><span class="text-gray-500">Sexo:</span> <span class="capitalize">{{ $turno->paciente?->sexo ?? '--' }}</span></div>
                <div><span class="text-gray-500">Email:</span> <span>{{ $turno->paciente?->email ?? '--' }}</span></div>
                <div><span class="text-gray-500">Dirección:</span> <span>{{ $turno->paciente?->direccion ?? '--' }}</span></div>
                <div><span class="text-gray-500">Ciudad:</span> <span>{{ $turno->paciente?->ciudad ?? '--' }}</span></div>
                <div><span class="text-gray-500">Ocupación:</span> <span>{{ $turno->paciente?->ocupacion ?? '--' }}</span></div>
                @if ($turno->paciente?->referido_por)
                    <div><span class="text-gray-500">Referido por:</span> <span>{{ $turno->paciente->referido_por }}</span></div>
                @endif
            </div>
        </div>

        {{-- ====== INFORMACIÓN MÉDICA ====== --}}
        @if ($turno->paciente)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Datos físicos</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Peso:</span> <span>{{ $turno->paciente->peso ? $turno->paciente->peso . ' kg' : '--' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Altura:</span> <span>{{ $turno->paciente->altura ? $turno->paciente->altura . ' m' : '--' }}</span></div>
                    @if ($turno->paciente->peso && $turno->paciente->altura)
                        @php $imc = round($turno->paciente->peso / ($turno->paciente->altura * $turno->paciente->altura), 1); @endphp
                        <div class="flex justify-between"><span class="text-gray-500">IMC:</span> <span class="font-medium">{{ $imc }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-gray-500">Grupo sanguíneo:</span> <span>{{ $turno->paciente->grupo_sanguineo ?? '--' }}</span></div>
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Antecedentes</h3>
                <dl class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500">Medicamentos:</span>
                        <p class="mt-0.5">{{ $turno->paciente->medicamentos ?: 'Ninguno' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Cirugías:</span>
                        <p class="mt-0.5">{{ $turno->paciente->cirugias ?: 'Ninguna' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Alergias:</span>
                        <p class="mt-0.5">{{ $turno->paciente->alergias ?: 'Ninguna' }}</p>
                    </div>
                </dl>
            </div>

            <div class="md:col-span-2 bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Enfermedades</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Personales:</span>
                        <p class="mt-0.5">{{ $turno->paciente->antecedentes ?: 'Ninguna' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Familiares:</span>
                        <p class="mt-0.5">{{ $turno->paciente->enfermedades_familiares ?: 'Ninguna' }}</p>
                    </div>
                </div>
            </div>

            @if ($turno->paciente->sexo === 'femenino' && ($turno->paciente->fum || $turno->paciente->gestas))
            <div class="md:col-span-2 bg-pink-50 rounded-xl shadow-sm border border-pink-200 p-6">
                <h3 class="font-semibold text-pink-800 mb-3">Datos gineco-obstétricos</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div><span class="text-pink-600">FUM:</span> <span class="font-medium">{{ $turno->paciente->fum?->format('d/m/Y') ?? '--' }}</span></div>
                    <div><span class="text-pink-600">Gestas:</span> <span>{{ $turno->paciente->gestas ?? '--' }}</span></div>
                    <div><span class="text-pink-600">Partos:</span> <span>{{ $turno->paciente->partos ?? '--' }}</span></div>
                    <div><span class="text-pink-600">Cesáreas:</span> <span>{{ $turno->paciente->cesareas ?? '--' }}</span></div>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- ====== CONSULTAS ANTERIORES ====== --}}
        @if ($consultasAnteriores->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Expediente — Consultas</h2>
                <div class="space-y-4">
                    @foreach ($consultasAnteriores as $consulta)
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <div class="flex justify-between items-start">
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium">{{ $consulta->fecha->format('d/m/Y H:i') }}</span>
                                        <span class="text-gray-500">{{ $consulta->medico?->nombre_completo ?? '--' }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><strong>Motivo:</strong> {{ $consulta->motivo_consulta }}</p>
                                    <p class="text-sm text-gray-600"><strong>Diagnóstico:</strong> {{ $consulta->diagnostico }}</p>
                                    @if ($consulta->codigo_cie10)
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-gray-100 text-xs text-gray-600 rounded">CIE-10: {{ $consulta->codigo_cie10 }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 ml-3 shrink-0">
                                    <a href="{{ route('consultas.editar', $consulta->id) }}" wire:navigate
                                       class="px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                                        Editar
                                    </a>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2 pt-2 border-t border-gray-100">
                                @can('ecografias.crear')
                                    <a href="{{ route('ecografias.crear', $turno->id) }}" wire:navigate
                                       class="px-3 py-1.5 text-xs font-medium text-purple-600 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                                        + Ecografía
                                    </a>
                                @endcan
                                <a href="{{ route('recetas.crear', $turno->id) }}" wire:navigate
                                   class="px-3 py-1.5 text-xs font-medium text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition">
                                    + Receta
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ====== FORMULARIO DE NUEVA CONSULTA (INLINE) ====== --}}
        @if ($turno->paciente)
            <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Nueva consulta</h2>
                    <button wire:click="toggleForm" type="button"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        {{ $showForm ? 'Cancelar' : '+ Nueva consulta' }}
                    </button>
                </div>

                @if ($showForm)
                    <form wire:submit="guardarConsulta" class="space-y-6">
                        {{-- Tipo de consulta --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Tipo de consulta</h3>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="tipo_consulta" value="general" class="text-blue-600">
                                    <span class="text-sm font-medium">Medicina General</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="tipo_consulta" value="ginecologica" class="text-blue-600">
                                    <span class="text-sm font-medium">Ginecológica</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="tipo_consulta" value="control_prenatal" class="text-blue-600">
                                    <span class="text-sm font-medium">Control Prenatal</span>
                                </label>
                            </div>
                            @error('tipo_consulta') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Motivo y anamnesis --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Motivo y antecedentes</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Motivo de consulta *</label>
                                    <textarea wire:model="motivo_consulta" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                    @error('motivo_consulta') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Anamnesis</label>
                                    <textarea wire:model="anamnesis" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Examen físico --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Examen físico</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">PA (mmHg)</label>
                                    <input wire:model="examen_fisico_pa" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">FC (lpm)</label>
                                    <input wire:model="examen_fisico_fc" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">FR (rpm)</label>
                                    <input wire:model="examen_fisico_fr" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Temp (°C)</label>
                                    <input wire:model="examen_fisico_temp" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Peso (kg)</label>
                                    <input wire:model="examen_fisico_peso" type="number" step="0.1" wire:change="calcularImc" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Talla (m)</label>
                                    <input wire:model="examen_fisico_talla" type="number" step="0.01" wire:change="calcularImc" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">IMC</label>
                                    <input wire:model="examen_fisico_imc" type="text" readonly class="w-full rounded-lg bg-gray-50 border-gray-300 text-sm">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Notas del examen físico</label>
                                <textarea wire:model="examen_fisico_notas" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                            </div>
                        </div>

                        {{-- Diagnóstico y tratamiento --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Diagnóstico y tratamiento</h3>
                            <div class="space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                    <div class="sm:col-span-3">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Diagnóstico *</label>
                                        <textarea wire:model="diagnostico" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                        @error('diagnostico') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="relative" x-data="{ open: false }">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">CIE-10</label>
                                        <input wire:model.live.debounce.200ms="cie10Search" type="text"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                            placeholder="Buscar código o descripción..." autocomplete="off"
                                            x-on:focus="open = true" x-on:click.away="open = false">
                                        @if ($cie10Results)
                                        <div x-show="open"
                                            class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                            @foreach ($cie10Results as $r)
                                            <button type="button" wire:click="selectCie10('{{ $r['codigo'] }}')" x-on:click="open = false"
                                                class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm border-b border-gray-100 last:border-b-0">
                                                <span class="font-medium text-gray-800">{{ $r['codigo'] }}</span>
                                                <span class="text-gray-500 ml-2">{{ $r['descripcion'] }}</span>
                                            </button>
                                            @endforeach
                                        </div>
                                        @endif
                                        @if ($cie10Selected)
                                        <div class="mt-1 flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $cie10Selected['codigo'] }}</span>
                                            <span class="text-xs text-gray-500 truncate">{{ $cie10Selected['descripcion'] }}</span>
                                            <button type="button" wire:click="clearCie10" class="text-xs text-red-500 hover:text-red-700">&times;</button>
                                        </div>
                                        @endif
                                        <input type="hidden" wire:model="codigo_cie10">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tratamiento</label>
                                    <textarea wire:model="tratamiento" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Indicaciones</label>
                                    <textarea wire:model="indicaciones" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Derivación --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Derivación</h3>
                            <label class="flex items-center gap-2 mb-3">
                                <input type="checkbox" wire:model="requiere_derivacion" class="rounded border-gray-300 text-blue-600">
                                <span class="text-sm">Requiere derivación a otra especialidad</span>
                            </label>
                            @if ($requiere_derivacion)
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Especialidad de derivación</label>
                                    <input wire:model="derivacion_especialidad" type="text" class="w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @error('derivacion_especialidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>

                        {{-- Botones --}}
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="toggleForm"
                                class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition text-sm">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm text-sm">
                                Guardar consulta
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
