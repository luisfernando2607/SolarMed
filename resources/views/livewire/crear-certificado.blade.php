<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="mb-6">
        <a href="{{ url()->previous() }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Volver</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Certificado médico — {{ $paciente->nombre_completo }}</h1>
        <p class="text-sm text-gray-500">C.I. {{ $paciente->cedula }}@if ($paciente->edad !== null) · {{ $paciente->edad }} años @endif</p>
    </div>

    @if ($emitidoId)
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="font-semibold text-green-800">Certificado emitido.</p>
                <p class="text-sm text-green-700">Incluye un código QR para que quien lo reciba verifique su autenticidad.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('certificados.pdf', $emitidoId) }}" target="_blank" class="px-4 py-2 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800">Imprimir / PDF</a>
                <button wire:click="nuevo" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Emitir otro</button>
            </div>
        </div>
    @else
        <form wire:submit="emitir" class="bg-white rounded-xl shadow-sm border p-6 space-y-5">
            <div class="flex gap-3">
                @foreach (['atencion' => 'Certificado de atención', 'reposo' => 'Certificado de reposo'] as $valor => $texto)
                    <label class="flex-1 cursor-pointer border rounded-lg p-3 text-sm {{ $tipo === $valor ? 'border-blue-500 bg-blue-50 text-blue-800 font-medium' : 'text-gray-700' }}">
                        <input type="radio" wire:model.live="tipo" value="{{ $valor }}" class="mr-2">{{ $texto }}
                    </label>
                @endforeach
            </div>
            @error('tipo') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de atención *</label>
                    <input wire:model="fecha_atencion" type="date" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    @error('fecha_atencion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hora de atención</label>
                    <input wire:model="hora_atencion" type="time" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            @if ($tipo === 'reposo')
                <div class="grid grid-cols-3 gap-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Días de reposo *</label>
                        <input wire:model.live="dias_reposo" type="number" min="1" max="90" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        @error('dias_reposo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Desde *</label>
                        <input wire:model.live="reposo_desde" type="date" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        @error('reposo_desde') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Hasta (inclusive)</label>
                        <p class="py-2 text-sm font-medium text-gray-800">{{ $this->reposoHasta ? \Carbon\Carbon::parse($this->reposoHasta)->format('d/m/Y') : '—' }}</p>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-4 gap-4">
                <div class="col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Diagnóstico {{ $tipo === 'reposo' ? '*' : '' }}</label>
                    <input wire:model="diagnostico" type="text" maxlength="500" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    @error('diagnostico') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">CIE-10</label>
                    <input wire:model="codigo_cie10" type="text" maxlength="10" class="w-full rounded-lg border-gray-300 shadow-sm text-sm uppercase">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input wire:model="mostrar_diagnostico" type="checkbox" class="rounded border-gray-300">
                Imprimir el diagnóstico en el certificado <span class="text-xs text-gray-400">(desmarque si la paciente no autoriza revelarlo)</span>
            </label>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Observaciones / recomendaciones</label>
                <textarea wire:model="observaciones" rows="3" maxlength="1000" class="w-full rounded-lg border-gray-300 shadow-sm text-sm"></textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Emitir certificado</button>
            </div>
        </form>
    @endif

    @if ($anteriores->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border p-6 mt-6">
            <h2 class="font-semibold text-gray-800 mb-3">Certificados emitidos</h2>
            <div class="divide-y text-sm">
                @foreach ($anteriores as $c)
                    <div class="py-2 flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span class="font-mono text-xs">N.º {{ $c->numero() }}</span>
                        <span>{{ $c->fecha_emision->format('d/m/Y') }}</span>
                        <span class="text-gray-600">{{ $c->tipo === 'reposo' ? "Reposo {$c->dias_reposo} día(s)" : 'Atención' }}</span>
                        <span class="text-gray-400">{{ $c->medico?->nombre_completo }}</span>
                        <a href="{{ route('certificados.pdf', $c->id) }}" target="_blank" class="ml-auto text-xs text-blue-600 hover:underline">PDF</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
