<div>
@php
    $campo = 'w-full rounded-xl border-gray-200 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500';
    $tonos = ['pendiente' => 'bg-amber-50 text-amber-700', 'en_proceso' => 'bg-blue-50 text-blue-700', 'cumplido' => 'bg-emerald-50 text-emerald-700'];
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Planes de acción</h1>
            <p class="text-sm text-gray-500 mt-1">Acciones de mejora derivadas de la auditoría de historias clínicas{{ $vencidos ? " · {$vencidos} con plazo vencido" : '' }}</p>
        </div>
        <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 text-sm self-start">
            @foreach (['abiertos' => 'Abiertos', 'vencidos' => 'Vencidos', 'cumplidos' => 'Cumplidos', 'todos' => 'Todos'] as $valor => $texto)
                <button wire:click="$set('filtro', '{{ $valor }}')" class="px-3 py-1.5 rounded-lg transition {{ $filtro === $valor ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">{{ $texto }}</button>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 divide-y divide-gray-100">
        @forelse ($planes as $plan)
            @php $vencido = $plan->vencido(); @endphp
            <div class="px-5 py-4 {{ $vencido ? 'bg-red-50/40' : '' }}" wire:key="plan-{{ $plan->id }}">
                <div class="flex flex-col lg:flex-row lg:items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tonos[$plan->estado] }}">{{ \App\Models\PlanAccion::ESTADOS[$plan->estado] }}</span>
                            @if ($vencido)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Vencido hace {{ $plan->fecha_limite->diffInDays(today()) }} d</span>
                            @endif
                            @if ($plan->auditoria)
                                <a href="{{ route('auditoria.evaluar', $plan->auditoria->consulta_id) }}" wire:navigate class="text-xs font-mono text-blue-700 hover:underline">{{ $plan->auditoria->numero() }}</a>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-gray-900 mt-1.5">{{ $plan->accion }}</p>
                        <p class="text-sm text-gray-500 mt-0.5"><span class="text-gray-400">Hallazgo:</span> {{ $plan->hallazgo }}</p>
                        @if ($plan->evidencia)
                            <p class="text-sm text-gray-500 mt-0.5"><span class="text-gray-400">Evidencia:</span> {{ $plan->evidencia }}</p>
                        @endif
                    </div>
                    <div class="text-sm lg:text-right shrink-0">
                        <p class="text-gray-700">{{ $plan->responsable?->name ?? '—' }}</p>
                        <p class="text-xs {{ $vencido ? 'text-red-600 font-medium' : 'text-gray-400' }}">Hasta {{ $plan->fecha_limite->format('d/m/Y') }}</p>
                        @if ($plan->medico)
                            <p class="text-xs text-gray-400">{{ $plan->medico->nombre_completo }}</p>
                        @endif
                        @can('auditoria.gestionar')
                            @if ($plan->estado !== 'cumplido' && $editandoId !== $plan->id)
                                <button wire:click="editar({{ $plan->id }})" class="mt-2 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100">Actualizar</button>
                            @endif
                        @endcan
                    </div>
                </div>

                @if ($editandoId === $plan->id)
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-[12rem_1fr_auto] gap-3 items-start">
                        <select wire:model.live="estado" class="{{ $campo }}" aria-label="Estado">
                            @foreach (\App\Models\PlanAccion::ESTADOS as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                        <div>
                            <textarea wire:model="evidencia" rows="2" placeholder="{{ $estado === 'cumplido' ? 'Evidencia del cumplimiento (obligatoria)' : 'Avance o evidencia (opcional)' }}" class="{{ $campo }}"></textarea>
                            @error('evidencia') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="cancelar" class="px-3 py-2 rounded-xl text-sm text-gray-600 hover:bg-gray-100">Cancelar</button>
                            <button wire:click="guardar" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Guardar</button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="px-5 py-12 text-center text-sm text-gray-400">No hay planes de acción {{ $filtro === 'todos' ? '' : $filtro }}.</p>
        @endforelse
    </div>
    @if ($planes->hasPages())
        <div class="mt-4">{{ $planes->links() }}</div>
    @endif
</div>
</div>
