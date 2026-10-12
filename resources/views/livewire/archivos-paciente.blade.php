<div x-data x-on:notify.window="Swal.fire({
    icon: $event.detail.type, title: $event.detail.type === 'success' ? 'Listo' : 'Error',
    text: $event.detail.message, timer: 3000, showConfirmButton: false, toast: true, position: 'top-end'
})">
<div class="max-w-6xl mx-auto py-8 px-4">
    <div class="mb-6">
        <a href="{{ url()->previous() }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Volver</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Archivos — {{ $paciente->nombre_completo }}</h1>
        <p class="text-sm text-gray-500">C.I. {{ $paciente->cedula }} · Laboratorios, imágenes, referencias y fotografías clínicas</p>
    </div>

    @can('expediente.crear')
        <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Subir archivo</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Archivo (PDF, JPG o PNG, máx. {{ round(\App\Services\ArchivoService::maxKb() / 1024, 1) }} MB) *</label>
                    <input wire:model="archivo" type="file" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <div wire:loading wire:target="archivo" class="text-xs text-gray-500 mt-1">Cargando...</div>
                    @error('archivo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Categoría *</label>
                    <select wire:model="categoria" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        @foreach ($categorias as $clave => $nombre)
                            <option value="{{ $clave }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
                    <input wire:model="descripcion" type="text" maxlength="255" placeholder="Ej.: Biometría 12/10"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button wire:click="subir" wire:loading.attr="disabled" wire:target="subir,archivo"
                    class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50">Subir</button>
            </div>
        </div>
    @endcan

    <div class="flex flex-wrap gap-2 mb-4">
        <button wire:click="$set('filtro', '')" class="px-3 py-1.5 text-sm rounded-full border {{ $filtro === '' ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700' }}">Todos</button>
        @foreach ($categorias as $clave => $nombre)
            <button wire:click="$set('filtro', '{{ $clave }}')" class="px-3 py-1.5 text-sm rounded-full border {{ $filtro === $clave ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700' }}">{{ $nombre }}</button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($archivos as $a)
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden flex flex-col">
                <a href="{{ route('pacientes.archivo', $a->id) }}" target="_blank" class="block h-36 bg-gray-50 flex items-center justify-center overflow-hidden">
                    @if ($a->esImagen())
                        <img src="{{ route('pacientes.archivo', $a->id) }}" alt="{{ $a->nombre_original }}" class="object-cover w-full h-full" loading="lazy">
                    @else
                        <span class="text-4xl font-bold text-red-500">PDF</span>
                    @endif
                </a>
                <div class="p-4 flex-1 flex flex-col">
                    <p class="text-sm font-medium text-gray-900 truncate" title="{{ $a->nombre_original }}">{{ $a->descripcion ?: $a->nombre_original }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $categorias[$a->categoria] ?? $a->categoria }} · {{ $a->tamanio_kb }} KB</p>
                    <p class="text-xs text-gray-400">{{ $a->created_at->format('d/m/Y H:i') }} · {{ $a->user?->name ?? '—' }}</p>
                    <div class="mt-auto pt-3 flex gap-2">
                        <a href="{{ route('pacientes.archivo', $a->id) }}" target="_blank" class="text-xs font-medium text-blue-600 hover:text-blue-800">Abrir</a>
                        @if ($a->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                            <button wire:click="eliminar({{ $a->id }})" wire:confirm="¿Eliminar este archivo? Quedará registrado en la auditoría."
                                class="text-xs font-medium text-red-600 hover:text-red-800 ml-auto">Eliminar</button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400 col-span-full text-center py-10">No hay archivos{{ $filtro ? ' en esta categoría' : '' }}.</p>
        @endforelse
    </div>
</div>
</div>
