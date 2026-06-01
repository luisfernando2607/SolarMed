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
    <div class="mb-6">
        <a href="{{ route('turno.atender', $turno->id) }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">&larr; Volver a la atención</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Nueva consulta — {{ $turno->paciente?->nombre_completo ?? $turno->nombre_temporal }}</h1>
        <p class="text-sm text-gray-500">{{ $turno->especialidad->nombre }} &middot; {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">{{ session('error') }}</div>
    @endif

    <form wire:submit="guardar" class="space-y-6">
        {{-- Tipo de consulta --}}
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Tipo de consulta</h2>
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
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Motivo y antecedentes</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo de consulta *</label>
                    <textarea wire:model="motivo_consulta" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('motivo_consulta') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Anamnesis</label>
                    <textarea wire:model="anamnesis" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
            </div>
        </div>

        {{-- Examen físico --}}
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Examen físico</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
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
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notas del examen físico</label>
                <textarea wire:model="examen_fisico_notas" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
        </div>

        {{-- Diagnóstico y tratamiento --}}
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Diagnóstico y tratamiento</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Diagnóstico *</label>
                        <textarea wire:model="diagnostico" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        @error('diagnostico') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="relative" x-data="{ open: false }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">CIE-10</label>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tratamiento</label>
                    <textarea wire:model="tratamiento" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Indicaciones</label>
                    <textarea wire:model="indicaciones" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
            </div>
        </div>

        {{-- Derivación --}}
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Derivación</h2>
            <label class="flex items-center gap-2 mb-3">
                <input type="checkbox" wire:model="requiere_derivacion" class="rounded border-gray-300 text-blue-600">
                <span class="text-sm">Requiere derivación a otra especialidad</span>
            </label>
            @if ($requiere_derivacion)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad de derivación</label>
                    <input wire:model="derivacion_especialidad" type="text" class="w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('derivacion_especialidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>

        {{-- Botones --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('turno.atender', $turno->id) }}" wire:navigate
               class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </a>
            <button type="submit"
                class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
                Guardar consulta
            </button>
        </div>
    </form>
</div>
</div>
