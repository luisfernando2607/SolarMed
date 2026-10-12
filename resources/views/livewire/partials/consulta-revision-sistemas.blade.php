{{-- Formulario MSP 002, bloque G: revisión actual de órganos y sistemas --}}
<div class="mt-3">
    <label class="block text-sm font-medium text-gray-700 mb-1">Revisión de órganos y sistemas</label>
    <textarea wire:model="revision_sistemas" rows="2" placeholder="Sistemas con hallazgos, o «sin alteraciones»"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
    @error('revision_sistemas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>
