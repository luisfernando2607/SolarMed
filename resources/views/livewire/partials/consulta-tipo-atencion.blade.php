{{-- Formulario MSP 002, bloque B: primera consulta o subsecuente --}}
<div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm">
    <span class="text-xs text-gray-500">Atención:</span>
    @foreach (\App\Models\ExpedienteConsulta::TIPOS_ATENCION as $valor => $texto)
        <label class="inline-flex items-center gap-1.5 cursor-pointer text-gray-700">
            <input type="radio" wire:model="tipo_atencion" value="{{ $valor }}" class="text-blue-600 focus:ring-blue-500">
            {{ $texto }}
        </label>
    @endforeach
</div>
@error('tipo_atencion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
