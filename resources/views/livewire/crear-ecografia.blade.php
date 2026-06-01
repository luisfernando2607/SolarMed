<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="mb-6">
        <a href="{{ route('turno.atender', $turno->id) }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">&larr; Volver a la atención</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Nueva ecografía — {{ $turno->paciente?->nombre_completo ?? $turno->nombre_temporal }}</h1>
    </div>

    @if (session('success') && $ecografiaCreada)
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-4 mb-6">
            Ecografía registrada exitosamente.
            @if ($ecografiaCreada->pdf_path)
                <a href="{{ route('ecografias.pdf', $ecografiaCreada->id) }}" target="_blank" class="underline font-medium ml-2">Ver PDF</a>
            @endif
        </div>
        <div class="flex justify-center">
            <a href="{{ route('turno.atender', $turno->id) }}" wire:navigate
               class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                Volver a la atención
            </a>
        </div>
    @else
        <form wire:submit="guardar" class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Datos generales</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input wire:model="fecha" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Indicación</label>
                        <input wire:model="indicacion" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Datos obstétricos</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Semanas de gestación</label>
                        <input wire:model="semanas_gestacion" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">FUM</label>
                        <input wire:model="fum" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">FPP</label>
                        <input wire:model="fpp" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Presentación</label>
                        <input wire:model="presentacion" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Cefálica">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">LCF (lpm)</label>
                        <input wire:model="lcf" type="number" min="60" max="220" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Placenta</label>
                        <input wire:model="placenta" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Anterior, grado 0">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Líquido amniótico</label>
                        <input wire:model="liquido_amniotico" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Normal">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Biometría fetal</h2>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">DBP (mm)</label>
                        <input wire:model="dbp" type="number" step="0.1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">CC (mm)</label>
                        <input wire:model="cc" type="number" step="0.1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">CA (mm)</label>
                        <input wire:model="ca" type="number" step="0.1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">LF (mm)</label>
                        <input wire:model="lf" type="number" step="0.1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Peso fetal (g)</label>
                        <input wire:model="peso_fetal_estimado" type="number" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Conclusión</h2>
                <textarea wire:model="conclusion" rows="4" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('turno.atender', $turno->id) }}" wire:navigate
                   class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-5 py-2.5 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition shadow-sm">
                    Guardar ecografía
                </button>
            </div>
        </form>
    @endif
</div>
