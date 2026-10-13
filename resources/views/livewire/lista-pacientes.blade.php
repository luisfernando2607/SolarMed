<div>
    <div class="max-w-7xl mx-auto py-8 px-4">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pacientes</h1>
                <p class="text-sm text-gray-500 mt-1">{{ number_format($pacientes->total()) }} {{ $pacientes->total() === 1 ? 'paciente registrado' : 'pacientes registrados' }}</p>
            </div>
            <div class="relative w-full sm:w-96">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input wire:model.live.debounce.300ms="busqueda" type="search"
                    class="w-full pl-10 rounded-xl border-gray-200 bg-white shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Buscar por nombre, cédula o teléfono…">
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 border-b border-gray-100">
                        <tr>
                            <th class="text-left font-medium px-5 py-3"><button wire:click="ordenarPor('apellidos')" class="inline-flex items-center gap-1 hover:text-gray-800">Paciente @if ($sortBy === 'apellidos')<svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>@else<svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>@endif</button></th>
                            <th class="text-left font-medium px-5 py-3 hidden md:table-cell">Teléfono</th>
                            <th class="text-left font-medium px-5 py-3"><button wire:click="ordenarPor('sexo')" class="inline-flex items-center gap-1 hover:text-gray-800">Edad y sexo @if ($sortBy === 'sexo')<svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>@else<svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>@endif</button></th>
                            <th class="text-left font-medium px-5 py-3 hidden lg:table-cell"><button wire:click="ordenarPor('created_at')" class="inline-flex items-center gap-1 hover:text-gray-800">Registro @if ($sortBy === 'created_at')<svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>@else<svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>@endif</button></th>
                            <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($pacientes as $p)
                            @php
                                $ini = mb_strtoupper(mb_substr((string) $p->nombres, 0, 1) . mb_substr((string) $p->apellidos, 0, 1));
                                $tonoSexo = ['femenino' => 'bg-pink-50 text-pink-700', 'masculino' => 'bg-sky-50 text-sky-700'][$p->sexo] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="px-5 py-3">
                                    <a href="{{ route('pacientes.ver', $p) }}" wire:navigate class="flex items-center gap-3 text-left group">
                                        <span class="flex items-center justify-center w-9 h-9 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold shrink-0">{{ $ini }}</span>
                                        <span class="min-w-0">
                                            <span class="block font-medium text-gray-900 group-hover:text-blue-700 truncate">{{ $p->nombre_completo }}</span>
                                            <span class="block text-xs text-gray-500 font-mono">{{ $p->cedula }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-gray-600 hidden md:table-cell">{{ $p->telefono ?: '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="text-gray-700">{{ $p->edad !== null ? $p->edad . ' años' : '—' }}</span>
                                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $tonoSexo }}">{{ $p->sexo }}</span>
                                </td>
                                <td class="px-5 py-3 text-gray-500 hidden lg:table-cell">{{ $p->created_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('pacientes.ver', $p) }}" wire:navigate class="p-2 rounded-lg text-gray-400 hover:text-blue-700 hover:bg-blue-50 transition" title="Ver ficha" aria-label="Ver ficha">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </a>
                                        @can('pacientes.editar')
                                            <button wire:click="editar({{ $p->id }})" class="p-2 rounded-lg text-gray-400 hover:text-gray-800 hover:bg-gray-100 transition" title="Editar" aria-label="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                                            </button>
                                        @endcan
                                        @can('pacientes.eliminar')
                                            <button x-data
                                                x-on:click="Swal.fire({
                                                    icon: 'warning',
                                                    title: '¿Eliminar paciente?',
                                                    text: {{ \Illuminate\Support\Js::from($p->nombre_completo . '. Esta acción no se puede deshacer.') }},
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#dc2626',
                                                    cancelButtonColor: '#6b7280',
                                                    confirmButtonText: 'Sí, eliminar',
                                                    cancelButtonText: 'Cancelar'
                                                }).then((result) => { if (result.isConfirmed) $wire.eliminarPaciente({{ $p->id }}) })"
                                                class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Eliminar" aria-label="Eliminar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <p class="text-sm font-medium text-gray-700">{{ $busqueda ? 'Ningún paciente coincide con la búsqueda' : 'Aún no hay pacientes' }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $busqueda ? 'Pruebe con otro nombre, cédula o teléfono.' : 'Se registran al tomar un turno o al agendar una cita.' }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($pacientes->hasPages())
                <div class="p-4 border-t">{{ $pacientes->links() }}</div>
            @endif
        </div>

        {{-- Modal de edición (la ficha completa está en pacientes.ver) --}}
        @if ($selected && $editMode)
            <div class="fixed inset-0 bg-black/40 z-50 flex items-start justify-center p-4 pt-12 overflow-y-auto"
                 wire:click.self="cancelarEdicion">
                <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full {{ $editMode ? 'max-w-3xl' : '' }}">

                    @if ($editMode)
                        {{-- ====== FORMULARIO DE EDICIÓN — WIZARD ====== --}}
                        <div x-data="{ step: 1 }">

                            {{-- Header --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 font-semibold text-sm shrink-0">
                                        {{ strtoupper(substr($nombres ?? $selected->nombres, 0, 1)) }}{{ strtoupper(substr($apellidos ?? $selected->apellidos, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-sm">Editar paciente</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $nombres ?? $selected->nombres }} {{ $apellidos ?? $selected->apellidos }} · {{ $cedula ?? $selected->cedula }}</p>
                                    </div>
                                </div>
                                <button wire:click="cancelarEdicion" class="text-gray-400 hover:text-gray-600 text-xl leading-none font-light transition">&times;</button>
                            </div>

                            {{-- Steps nav --}}
                            <div class="flex border-b border-gray-100 px-6">
                                <button type="button" @click="step = 1"
                                    :class="step === 1 ? 'border-blue-600 text-blue-600 font-semibold' : (step > 1 ? 'border-transparent text-green-600' : 'border-transparent text-gray-400')"
                                    class="px-4 py-3 text-xs border-b-2 -mb-px transition whitespace-nowrap flex items-center gap-2">
                                    <span :class="step === 1 ? 'bg-blue-600 text-white' : (step > 1 ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500')"
                                        class="w-5 h-5 rounded-full text-[10px] flex items-center justify-center font-semibold">1</span>
                                    Datos personales
                                </button>
                                <button type="button" @click="step = 2"
                                    :class="step === 2 ? 'border-blue-600 text-blue-600 font-semibold' : (step > 2 ? 'border-transparent text-green-600' : 'border-transparent text-gray-400')"
                                    class="px-4 py-3 text-xs border-b-2 -mb-px transition whitespace-nowrap flex items-center gap-2">
                                    <span :class="step === 2 ? 'bg-blue-600 text-white' : (step > 2 ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500')"
                                        class="w-5 h-5 rounded-full text-[10px] flex items-center justify-center font-semibold">2</span>
                                    Datos clínicos
                                </button>
                                <button type="button" @click="step = 3"
                                    :class="step === 3 ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-400'"
                                    class="px-4 py-3 text-xs border-b-2 -mb-px transition whitespace-nowrap flex items-center gap-2">
                                    <span :class="step === 3 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500'"
                                        class="w-5 h-5 rounded-full text-[10px] flex items-center justify-center font-semibold">3</span>
                                    Gineco-obstétrico
                                </button>
                            </div>

                            <form wire:submit="guardarPaciente">
                                <div class="px-6 py-4 max-h-[420px] overflow-y-auto">

                                    {{-- PASO 1: DATOS PERSONALES --}}
                                    <div x-show="step === 1"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 translate-y-2"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         x-cloak>
                                        <div class="mb-6">
                                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3 pb-2 border-b border-gray-100">Identificación</p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Nombres <span class="text-red-500">*</span></label>
                                                    <input wire:model="nombres" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                    @error('nombres') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Apellidos <span class="text-red-500">*</span></label>
                                                    <input wire:model="apellidos" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                    @error('apellidos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Cédula <span class="text-red-500">*</span></label>
                                                    <input wire:model="cedula" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                    @error('cedula') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Fecha de nacimiento</label>
                                                    <input wire:model="fecha_nacimiento" type="date" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Sexo <span class="text-red-500">*</span></label>
                                                    <select wire:model="sexo" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                        <option value="">Seleccione...</option>
                                                        <option value="masculino">Masculino</option>
                                                        <option value="femenino">Femenino</option>
                                                    </select>
                                                    @error('sexo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Ocupación</label>
                                                    <input wire:model="ocupacion" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3 pb-2 border-b border-gray-100">Contacto</p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Teléfono</label>
                                                    <input wire:model="telefono" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Teléfono secundario</label>
                                                    <input wire:model="telefono_secundario" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                                                    <input wire:model="email" type="email" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Ciudad</label>
                                                    <input wire:model="ciudad" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                                {{-- Lugar de residencia actual (formulario 053, bloque A) --}}
                                                <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                    @foreach (['provincia' => 'Provincia', 'canton' => 'Cantón', 'parroquia' => 'Parroquia'] as $campoRes => $etiquetaRes)
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $etiquetaRes }}</label>
                                                            <input wire:model="{{ $campoRes }}" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                            @error($campoRes) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Dirección</label>
                                                    <input wire:model="direccion" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Referido por</label>
                                                    <input wire:model="referido_por" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- PASO 2: CLÍNICO --}}
                                    <div x-show="step === 2"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 translate-y-2"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         x-cloak>
                                        <div class="mb-6">
                                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3 pb-2 border-b border-gray-100">Signos y medidas</p>
                                            <div class="grid grid-cols-3 gap-4">
                                                <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-200">
                                                    <label class="block text-xs font-medium text-gray-500 mb-2">Grupo sanguíneo</label>
                                                    <input wire:model="grupo_sanguineo" type="text" placeholder="O+" class="w-20 mx-auto bg-transparent border-0 border-b-2 border-gray-300 focus:border-blue-500 focus:ring-0 text-center text-sm font-semibold text-gray-800 py-1">
                                                </div>
                                                <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-200">
                                                    <label class="block text-xs font-medium text-gray-500 mb-2">Peso (kg)</label>
                                                    <input wire:model="peso" type="number" step="0.1" class="w-20 mx-auto bg-transparent border-0 border-b-2 border-gray-300 focus:border-blue-500 focus:ring-0 text-center text-sm font-semibold text-gray-800 py-1">
                                                </div>
                                                <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-200">
                                                    <label class="block text-xs font-medium text-gray-500 mb-2">Altura (m)</label>
                                                    <input wire:model="altura" type="number" step="0.01" class="w-20 mx-auto bg-transparent border-0 border-b-2 border-gray-300 focus:border-blue-500 focus:ring-0 text-center text-sm font-semibold text-gray-800 py-1">
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3 pb-2 border-b border-gray-100">Antecedentes</p>
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Medicamentos actuales</label>
                                                    <textarea wire:model="medicamentos" rows="2" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2" placeholder="Ej: Metformina 500mg c/12h"></textarea>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Cirugías previas</label>
                                                    <textarea wire:model="cirugias" rows="2" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2"></textarea>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Alergias</label>
                                                    <textarea wire:model="alergias" rows="2" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2" placeholder="Medicamentos, alimentos, sustancias..."></textarea>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Antecedentes personales</label>
                                                    <textarea wire:model="antecedentes" rows="2" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2"></textarea>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Antecedentes familiares</label>
                                                    <textarea wire:model="enfermedades_familiares" rows="2" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2"></textarea>
                                                </div>
                                            </div>
                                            <label class="flex items-start gap-3 mt-4 p-3 rounded-xl border border-gray-200 bg-gray-50/70 cursor-pointer">
                                                <input type="checkbox" wire:model="identidad_reservada" class="mt-0.5 rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm">
                                                    <span class="block font-medium text-gray-800">Identidad reservada</span>
                                                    <span class="block text-xs text-gray-500 mt-0.5">Para pacientes con VIH, tuberculosis activa, cáncer u otras enfermedades raras o catastróficas (AM 5216-A, Art. 13). En documentos, sala de espera, agenda y recordatorios se muestra la cédula en lugar del nombre.</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    {{-- PASO 3: GINECO-OBSTÉTRICO --}}
                                    <div x-show="step === 3"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 translate-y-2"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         x-cloak>
                                        <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-4 py-2.5 text-xs text-blue-700 mb-6">
                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Esta sección aplica únicamente para pacientes de sexo femenino.
                                        </div>

                                        <div class="mb-6">
                                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3 pb-2 border-b border-gray-100">Ciclo menstrual</p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">FUM</label>
                                                    <input wire:model="fum" type="date" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Método anticonceptivo</label>
                                                    <input wire:model="metodo_anticonceptivo" type="text" placeholder="Ej: DIU, pastillas, ninguno" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3 pb-2 border-b border-gray-100">Historia obstétrica</p>
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-5 gap-y-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Gestas</label>
                                                    <input wire:model="gestas" type="number" min="0" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Partos</label>
                                                    <input wire:model="partos" type="number" min="0" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Cesáreas</label>
                                                    <input wire:model="cesareas" type="number" min="0" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Abortos</label>
                                                    <input wire:model="abortos" type="number" min="0" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                {{-- Footer con navegación --}}
                                <div class="flex items-center justify-between px-6 py-3 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                    <div class="flex gap-1">
                                        <span :class="step >= 1 ? 'bg-blue-500' : 'bg-gray-200'" class="w-2 h-2 rounded-full transition-colors duration-200"></span>
                                        <span :class="step >= 2 ? 'bg-blue-500' : 'bg-gray-200'" class="w-2 h-2 rounded-full transition-colors duration-200"></span>
                                        <span :class="step >= 3 ? 'bg-blue-500' : 'bg-gray-200'" class="w-2 h-2 rounded-full transition-colors duration-200"></span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" x-show="step > 1" @click="step--"
                                            class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            Atrás
                                        </button>
                                        <button type="button" wire:click="cancelarEdicion"
                                            class="px-4 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                                            Cancelar
                                        </button>
                                        <button type="button" x-show="step < 3" @click="step++"
                                            class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition flex items-center gap-1">
                                            Siguiente
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                        <button type="submit" x-show="step === 3"
                                            class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Guardar cambios
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    @endif

                </div>
            </div>
        @endif

    </div>
</div>
