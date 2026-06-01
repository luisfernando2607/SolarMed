<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="mb-6">
        <a href="{{ route('turno.atender', $turno->id) }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">&larr; Volver a la atención</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Nueva receta — {{ $turno->paciente?->nombre_completo ?? $turno->nombre_temporal }}</h1>
    </div>

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">{{ session('error') }}</div>
    @endif

    <form wire:submit="guardar" class="space-y-6">
        {{-- Medicamentos --}}
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Medicamentos</h2>
                <button type="button" wire:click="agregarMedicamento"
                    class="px-3 py-1.5 text-sm bg-blue-50 text-blue-700 font-medium rounded-lg hover:bg-blue-100 transition">
                    + Agregar
                </button>
            </div>

            @error('medicamentos') <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror

            @foreach ($medicamentos as $i => $med)
                <div class="border rounded-lg p-4 mb-3 {{ $loop->last ? '' : 'border-gray-200' }}">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-sm font-medium text-gray-500">Medicamento #{{ $i + 1 }}</span>
                        @if (count($medicamentos) > 1)
                            <button type="button" wire:click="quitarMedicamento({{ $i }})"
                                class="text-red-400 hover:text-red-600 text-sm">Eliminar</button>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Medicamento *</label>
                            <input wire:model="medicamentos.{{ $i }}.medicamento" type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Nombre del medicamento">
                            @error("medicamentos.{$i}.medicamento") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Presentación</label>
                            <input wire:model="medicamentos.{{ $i }}.presentacion" type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Tabletas 500mg">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Dosis</label>
                            <input wire:model="medicamentos.{{ $i }}.dosis" type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="1 tableta">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Frecuencia</label>
                            <input wire:model="medicamentos.{{ $i }}.frecuencia" type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Cada 8 horas">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Duración</label>
                            <input wire:model="medicamentos.{{ $i }}.duracion" type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="7 días">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Indicaciones</label>
                            <input wire:model="medicamentos.{{ $i }}.indicaciones" type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Después de cada comida">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Observaciones --}}
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Observaciones</h2>
            <textarea wire:model="observaciones" rows="3"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('turno.atender', $turno->id) }}" wire:navigate
               class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </a>
            <button type="submit"
                class="px-5 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition shadow-sm">
                Guardar receta
            </button>
        </div>
    </form>
</div>
