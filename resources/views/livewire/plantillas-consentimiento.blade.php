@php
    $campo = 'w-full rounded-xl border-gray-200 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
    $textos = [
        ['en_que_consiste', '¿En qué consiste? *', 3], ['como_se_realiza', '¿Cómo se realiza?', 3],
        ['beneficios', 'Beneficios', 2], ['riesgos_frecuentes', 'Riesgos frecuentes (poco graves)', 2],
        ['riesgos_graves', 'Riesgos poco frecuentes (graves)', 2], ['alternativas', 'Alternativas al procedimiento', 2],
        ['manejo_posterior', 'Manejo posterior', 2], ['consecuencias_no_realizar', 'Consecuencias si no se realiza', 2],
    ];
@endphp
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Plantillas de consentimiento</h1>
            <p class="text-sm text-gray-500 mt-1">Textos por procedimiento que se cargan al crear un consentimiento informado (formulario 024). Cada profesional los redacta y los revisa para cada paciente.</p>
        </div>
        @unless ($editando)
            <button wire:click="nueva" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-sm shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nueva plantilla
            </button>
        @endunless
    </div>

    @if ($editando)
        <form wire:submit="guardar" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="{{ $etiqueta }}">Procedimiento *</label>
                    <input wire:model="procedimiento" type="text" class="{{ $campo }}">
                    @error('procedimiento') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Duración estimada</label>
                    <input wire:model="duracion_estimada" type="text" class="{{ $campo }}">
                </div>
            </div>
            @foreach ($textos as [$nombre, $titulo, $filas])
                <div>
                    <label class="{{ $etiqueta }}">{{ $titulo }}</label>
                    <textarea wire:model="{{ $nombre }}" rows="{{ $filas }}" class="{{ $campo }}"></textarea>
                    @error($nombre) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="cancelar" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Guardar plantilla</button>
            </div>
        </form>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Procedimiento</th>
                        <th class="text-left font-medium px-5 py-3 hidden md:table-cell">Creada por</th>
                        <th class="text-left font-medium px-5 py-3">Estado</th>
                        <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($plantillas as $pl)
                        <tr class="hover:bg-gray-50/70" wire:key="pl-{{ $pl->id }}">
                            <td class="px-5 py-3">
                                <span class="block font-medium text-gray-900">{{ $pl->procedimiento }}</span>
                                <span class="block text-xs text-gray-500 truncate max-w-[260px] sm:max-w-md">{{ str($pl->en_que_consiste)->squish()->limit(90) }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-600 hidden md:table-cell">{{ $pl->autor?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $pl->activo ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $pl->activo ? 'Activa' : 'Inactiva' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <button wire:click="editar({{ $pl->id }})" class="px-2.5 py-1.5 text-xs font-medium text-blue-700 rounded-lg hover:bg-blue-50">Editar</button>
                                <button wire:click="alternarActivo({{ $pl->id }})" class="px-2.5 py-1.5 text-xs text-gray-600 rounded-lg hover:bg-gray-100">{{ $pl->activo ? 'Desactivar' : 'Activar' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-14 text-center">
                            <p class="text-sm font-medium text-gray-700">Aún no hay plantillas</p>
                            <p class="text-xs text-gray-400 mt-1">Cree una aquí o use «Guardar textos como plantilla» al redactar un consentimiento.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
