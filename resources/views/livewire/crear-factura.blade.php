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
            <a href="{{ route('facturas') }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">&larr; Volver a facturación</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Nueva factura</h1>
        </div>

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">{{ session('error') }}</div>
        @endif

        {{-- Step 1: Seleccionar paciente --}}
        @if (!$selectedPaciente)
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Seleccionar paciente</h2>
                <div class="mb-4">
                    <input wire:model.live.debounce.300ms="busqueda" type="text"
                        class="w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Buscar por nombre o cédula...">
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($pacientes as $p)
                        <div class="flex items-center justify-between py-3">
                            <div>
                                <p class="font-medium text-gray-900">{{ $p->nombre_completo }}</p>
                                <p class="text-sm text-gray-500">{{ $p->cedula }} &middot; {{ $p->edad ?? '--' }} años</p>
                            </div>
                            <button wire:click="seleccionarPaciente({{ $p->id }})"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                Seleccionar
                            </button>
                        </div>
                    @empty
                        <p class="py-4 text-center text-gray-400">
                            @if ($busqueda) No se encontraron pacientes con "{{ $busqueda }}"
                            @else Escriba para buscar pacientes @endif
                        </p>
                    @endforelse
                </div>
                @if ($pacientes->hasPages())
                    <div class="mt-4 border-t pt-4">{{ $pacientes->links() }}</div>
                @endif
            </div>
        @endif

        {{-- Step 2: Agregar servicios --}}
        @if ($selectedPaciente)
            <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Paciente</h2>
                        <p class="text-sm text-gray-600">{{ $selectedPaciente->nombre_completo }} &middot; {{ $selectedPaciente->cedula }}</p>
                    </div>
                    <button wire:click="resetPaciente" class="text-sm text-gray-500 hover:text-gray-700">Cambiar paciente</button>
                </div>

                <h3 class="text-sm font-semibold text-gray-700 mb-3">Servicios disponibles</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mb-6">
                    @php
                        $idsEnItems = collect($this->items)->pluck('servicio_id')->toArray();
                    @endphp
                    @foreach ($serviciosDisponibles as $s)
                        <button wire:click="agregarItem({{ $s->id }})"
                            class="text-left px-3 py-2 rounded-lg border text-sm transition
                                {{ in_array($s->id, $idsEnItems) ? 'border-blue-400 bg-blue-50 text-blue-700' : 'border-gray-200 hover:border-blue-300 hover:bg-blue-50' }}">
                            <span class="font-medium">{{ $s->nombre }}</span>
                            <span class="block text-xs text-gray-500 mt-0.5">${{ number_format($s->precio, 2) }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Items agregados --}}
                @if (count($items) > 0)
                    <div class="border rounded-lg overflow-hidden mb-4">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="text-left px-4 py-2">Servicio</th>
                                    <th class="text-center px-4 py-2 w-24">Cant.</th>
                                    <th class="text-right px-4 py-2 w-24">P. Unit.</th>
                                    <th class="text-right px-4 py-2 w-24">Subtotal</th>
                                    <th class="text-center px-4 py-2 w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($items as $i => $item)
                                    <tr>
                                        <td class="px-4 py-2">{{ $item['descripcion'] }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="number" min="1"
                                                value="{{ $item['cantidad'] }}"
                                                wire:change="actualizarCantidad({{ $i }}, $event.target.value)"
                                                class="w-16 text-center rounded border-gray-300 text-sm">
                                        </td>
                                        <td class="px-4 py-2 text-right">${{ number_format($item['precio_unitario'], 2) }}</td>
                                        <td class="px-4 py-2 text-right font-medium">${{ number_format($item['subtotal'], 2) }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <button wire:click="quitarItem({{ $i }})"
                                                class="text-red-500 hover:text-red-700 text-lg leading-none">&times;</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Totales --}}
                    <div class="space-y-2 text-sm border-t pt-4">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal:</span>
                            <span class="font-medium">${{ number_format($this->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Descuento:</span>
                            <input type="number" step="0.01" min="0" wire:model.live="descuento"
                                class="w-28 text-right rounded border-gray-300 text-sm"
                                placeholder="0.00">
                        </div>
                        <div class="flex justify-between text-base font-bold border-t pt-2">
                            <span>TOTAL:</span>
                            <span>${{ number_format($this->total, 2) }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400 py-4 text-center">Seleccione servicios para facturar</p>
                @endif
            </div>

            {{-- Step 3: Pago --}}
            @if (count($items) > 0)
                <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Información de pago</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Forma de pago *</label>
                            <select wire:model="forma_pago"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                            @error('forma_pago') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Referencia (opcional)</label>
                            <input wire:model="referencia_pago" type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Nº cheque, transf...">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Observaciones</label>
                            <input wire:model="observaciones" type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Notas adicionales">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="resetPaciente"
                        class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition text-sm">
                        Cancelar
                    </button>
                    <button wire:click="guardar"
                        class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm text-sm">
                        Crear factura — ${{ number_format($this->total, 2) }}
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>
