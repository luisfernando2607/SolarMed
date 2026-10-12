{{-- Formulario MSP 002, bloque I: diagnóstico presuntivo (PRE) o definitivo (DEF) --}}
<div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm">
    <span class="text-xs text-gray-500">Diagnóstico:</span>
    @foreach (\App\Models\ExpedienteConsulta::TIPOS_DIAGNOSTICO as $valor => $texto)
        <label class="inline-flex items-center gap-1.5 cursor-pointer text-gray-700">
            <input type="radio" wire:model="tipo_diagnostico" value="{{ $valor }}" class="text-blue-600 focus:ring-blue-500">
            {{ $texto }}
        </label>
    @endforeach
</div>
@error('tipo_diagnostico') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
