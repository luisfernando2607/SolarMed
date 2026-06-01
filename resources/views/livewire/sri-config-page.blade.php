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
            <h1 class="text-2xl font-bold text-gray-900">Configuración SRI</h1>
            <p class="text-sm text-gray-500">Datos para la facturación electrónica del SRI</p>
        </div>

        <form wire:submit="guardar" class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Datos del contribuyente</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">RUC *</label>
                        <input wire:model="sri_ruc" type="text" maxlength="13"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('sri_ruc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Razón social *</label>
                        <input wire:model="sri_razon_social" type="text"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('sri_razon_social') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nombre comercial</label>
                        <input wire:model="sri_nombre_comercial" type="text"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono</label>
                        <input wire:model="sri_telefono" type="text"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                        <input wire:model="sri_email" type="email"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Contribuyente especial</label>
                        <input wire:model="sri_contribuyente_especial" type="text"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                            placeholder="Resolución">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Dirección matriz *</label>
                    <input wire:model="sri_direccion" type="text"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    @error('sri_direccion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Configuración de emisión</h2>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Establecimiento *</label>
                        <input wire:model="sri_establecimiento" type="text" maxlength="3"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                            placeholder="001">
                        @error('sri_establecimiento') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Punto emisión *</label>
                        <input wire:model="sri_pto_emi" type="text" maxlength="3"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                            placeholder="001">
                        @error('sri_pto_emi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Ambiente *</label>
                        <select wire:model="sri_ambiente"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="1">Pruebas</option>
                            <option value="2">Producción</option>
                        </select>
                        @error('sri_ambiente') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Contabilidad *</label>
                        <select wire:model="sri_obligado_contabilidad"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="NO">NO</option>
                            <option value="SI">SI</option>
                        </select>
                        @error('sri_obligado_contabilidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm text-sm">
                    Guardar configuración
                </button>
            </div>
        </form>
    </div>
</div>
