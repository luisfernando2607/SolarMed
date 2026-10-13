<div class="max-w-5xl mx-auto py-8 px-4">
    <div class="mb-6">
        <a href="{{ url()->previous() }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Volver</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Pedido de exámenes — {{ $paciente->nombre_completo }}</h1>
        <p class="text-sm text-gray-500">C.I. {{ $paciente->cedula }}@if ($paciente->edad !== null) · {{ $paciente->edad }} años @endif</p>
    </div>

    @if ($emitidoId)
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <p class="font-semibold text-green-800">Pedido de exámenes emitido.</p>
            <div class="flex gap-2">
                <a href="{{ route('pedidos.pdf', $emitidoId) }}" target="_blank" class="px-4 py-2 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800">Imprimir / PDF</a>
                <button wire:click="nuevo" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Nuevo pedido</button>
            </div>
        </div>
    @else
        <form wire:submit="emitir" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach (['laboratorio' => ['Laboratorio', $catalogoLaboratorio], 'imagen' => ['Imagen y otros procedimientos', $catalogoImagen]] as $campo => [$titulo, $lista])
                    <div class="bg-white rounded-xl shadow-xs border p-5">
                        <h2 class="font-semibold text-gray-800 mb-3">{{ $titulo }} <span class="text-xs font-normal text-gray-400">({{ count($this->{$campo}) }} seleccionados)</span></h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5">
                            @foreach ($lista as $examen)
                                <label class="flex items-start gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model.live="{{ $campo }}" value="{{ $examen }}" class="mt-0.5 rounded-sm border-gray-300">
                                    <span>{{ $examen }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @error('laboratorio') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

            <div class="bg-white rounded-xl shadow-xs border p-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Otros exámenes</label>
                    <textarea wire:model="otros" rows="2" maxlength="1000" placeholder="Exámenes que no están en la lista" class="w-full rounded-lg border-gray-300 shadow-xs text-sm"></textarea>
                </div>
                <div class="grid grid-cols-4 gap-4">
                    <div class="col-span-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Diagnóstico presuntivo</label>
                        <input wire:model="diagnostico" type="text" maxlength="500" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">CIE-10</label>
                        <input wire:model="codigo_cie10" type="text" maxlength="10" class="w-full rounded-lg border-gray-300 shadow-xs text-sm uppercase">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Indicaciones para el paciente</label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach (\App\Livewire\CrearPedidoExamenes::INDICACIONES_RAPIDAS as $i => $texto)
                            <button type="button" wire:click="agregarIndicacion({{ $i }})" class="px-2.5 py-1 text-xs rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200">+ {{ $texto }}</button>
                        @endforeach
                    </div>
                    <textarea wire:model="indicaciones" rows="3" maxlength="1000" class="w-full rounded-lg border-gray-300 shadow-xs text-sm"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Emitir pedido</button>
                </div>
            </div>
        </form>
    @endif

    @if ($anteriores->isNotEmpty())
        <div class="bg-white rounded-xl shadow-xs border p-6 mt-6">
            <h2 class="font-semibold text-gray-800 mb-3">Pedidos anteriores</h2>
            <div class="divide-y text-sm">
                @foreach ($anteriores as $p)
                    <div class="py-2 flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span class="font-mono text-xs">N.º {{ $p->numero() }}</span>
                        <span>{{ $p->fecha->format('d/m/Y') }}</span>
                        <span class="text-gray-600 truncate max-w-md">{{ collect($p->laboratorio)->merge($p->imagen)->take(4)->implode(', ') }}{{ $p->totalExamenes() > 4 ? '…' : '' }}</span>
                        <a href="{{ route('pedidos.pdf', $p->id) }}" target="_blank" class="ml-auto text-xs text-blue-600 hover:underline">PDF</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
