<div>
<style>
    .evaluar .tarjeta { background: #fff; border: 1px solid #e9e7e2; border-radius: 16px; }
</style>
@php
    $c = $consulta;
    $p = $c->paciente;
    $ef = $c->examen_fisico ?? [];
    $dato = fn ($v) => filled($v) ? $v : '—';
    $puede = auth()->user()->can('auditoria.gestionar');
    $campo = 'w-full rounded-xl border-gray-200 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
    $contenido = [
        'A' => "{$p?->nombre_completo} · CI {$dato($p?->cedula)} · " . ($p?->sexo ?: 'sexo —') . ' · ' . ($p?->fecha_nacimiento ? $p->fecha_nacimiento->format('d/m/Y') : 'nacimiento —'),
        'B' => $dato($c->motivo_consulta) . ($c->tipo_atencion ? ' (' . \App\Models\ExpedienteConsulta::TIPOS_ATENCION[$c->tipo_atencion] . ')' : ' (sin primera/subsecuente)'),
        'C' => collect(['Personales' => $p?->antecedentes, 'Medicación' => $p?->medicamentos, 'Cirugías' => $p?->cirugias, 'Alergias' => $p?->alergias])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode(' · ') ?: '—',
        'D' => $dato($p?->enfermedades_familiares),
        'E' => $dato($c->anamnesis),
        'F' => collect(['PA' => $ef['pa'] ?? null, 'FC' => $ef['fc'] ?? null, 'FR' => $ef['fr'] ?? null, 'T°' => $ef['temp'] ?? null, 'Peso' => $ef['peso'] ?? null, 'Talla' => $ef['talla'] ?? null])->map(fn ($v, $k) => "{$k} " . (filled($v) ? $v : '—'))->implode(' · '),
        'G' => $dato($c->revision_sistemas),
        'H' => $dato($ef['notas'] ?? null),
        'I' => ($c->codigo_cie10 ? "[{$c->codigo_cie10}] " : '[sin CIE-10] ') . $dato($c->diagnostico) . ($c->tipo_diagnostico ? ' · ' . mb_strtolower(\App\Models\ExpedienteConsulta::TIPOS_DIAGNOSTICO[$c->tipo_diagnostico]) : ''),
        'J' => $dato($c->tratamiento),
        'K' => ($c->medico?->nombre_completo ?? '—') . ' · CI ' . $dato($c->medico?->user?->cedula) . ' · ' . ($c->medico?->firma_path ? 'firma registrada' : 'sin firma registrada'),
        'T' => 'Atención ' . $c->fecha->format('d/m/Y H:i') . ' · registrada ' . $c->created_at?->format('d/m/Y H:i'),
    ];
@endphp
<div class="evaluar max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <a href="{{ route('auditoria.calidad') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Calidad de la historia clínica
    </a>

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Auditoría de la consulta</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $p?->nombre_completo }} · {{ $c->fecha->format('d/m/Y') }} · {{ $c->especialidad?->nombre }}</p>
        </div>
        <span class="self-start px-3 py-1.5 rounded-xl text-sm font-medium {{ \App\Services\CalidadHistoriaService::tono($automatica['puntaje']) }}">Evaluación automática: {{ $automatica['puntaje'] }}%</span>
    </div>

    {{-- Lista de verificación por bloque --}}
    <div class="tarjeta">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Bloques del formulario 002</h2>
            <p class="text-xs text-gray-400">Pre-llenado con la evaluación automática. {{ $puede ? 'Confirme o corrija cada bloque y agregue observaciones.' : 'Solo lectura.' }}</p>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach (\App\Services\CalidadHistoriaService::CRITERIOS as $clave => [$titulo, $detalle, $peso])
                <li class="grid grid-cols-1 md:grid-cols-[14rem_1fr_16rem] gap-3 px-5 py-4" wire:key="crit-{{ $clave }}">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><span class="font-mono text-xs text-gray-400 mr-1">{{ $clave }}</span>{{ $titulo }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $detalle }}{{ $peso < 1 ? ' · peso ' . $peso : '' }}</p>
                    </div>
                    <p class="text-sm text-gray-700 whitespace-pre-line break-words">{{ $contenido[$clave] }}</p>
                    <div class="space-y-2">
                        <label class="inline-flex items-center gap-2 text-sm {{ $puede ? 'cursor-pointer' : '' }}">
                            <input type="checkbox" wire:model.live="resultado.{{ $clave }}.ok" @disabled(!$puede) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="{{ ($resultado[$clave]['ok'] ?? false) ? 'text-emerald-700' : 'text-red-700' }}">{{ ($resultado[$clave]['ok'] ?? false) ? 'Cumple' : 'No cumple' }}</span>
                            @if (($resultado[$clave]['ok'] ?? false) !== $automatica['criterios'][$clave]['ok'])
                                <span class="text-[11px] text-gray-400">(corregido)</span>
                            @endif
                        </label>
                        @if ($puede)
                            <input type="text" wire:model="resultado.{{ $clave }}.obs" maxlength="500" placeholder="Observación" class="{{ $campo }}">
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($puede)
            <div class="px-5 py-4 border-t border-gray-100 space-y-3">
                <label class="block text-sm font-medium text-gray-700">Observación general
                    <textarea wire:model="observacion" rows="3" maxlength="3000" class="mt-1 {{ $campo }}"></textarea>
                </label>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">Puntaje de esta evaluación: <span class="font-semibold text-gray-900 tabular-nums">{{ \App\Services\CalidadHistoriaService::puntaje($resultado) }}%</span></p>
                    <div class="flex gap-2">
                        <button wire:click="guardar" class="px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">Guardar borrador</button>
                        <button x-data x-on:click="Swal.fire({ icon: 'question', title: '¿Cerrar la evaluación?', text: 'Una evaluación cerrada queda como registro de auditoría y ya no se puede modificar.', showCancelButton: true, confirmButtonText: 'Cerrar evaluación', cancelButtonText: 'Volver', confirmButtonColor: @js($marca->color()) }).then(r => { if (r.isConfirmed) $wire.guardar(true) })"
                                class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm">Cerrar evaluación</button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Evaluaciones cerradas y planes de acción --}}
    @foreach ($cerradas as $a)
        <div class="tarjeta" wire:key="aud-{{ $a->id }}">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">{{ $a->numero() }} · <span class="tabular-nums">{{ $a->puntaje }}%</span></h2>
                    <p class="text-xs text-gray-400">Cerrada el {{ $a->cerrada_at->format('d/m/Y H:i') }} por {{ $a->auditor?->name }} · automática {{ $a->puntaje_automatico }}%</p>
                </div>
                <a href="{{ route('auditoria.informe', $a) }}" class="self-start px-3 py-1.5 text-xs font-medium text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50">Informe PDF</a>
            </div>
            @if ($a->observacion)
                <p class="px-5 pt-4 text-sm text-gray-700 whitespace-pre-line">{{ $a->observacion }}</p>
            @endif
            <div class="px-5 py-4 space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Planes de acción</p>
                @forelse ($a->planes as $plan)
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $plan->vencido() ? 'bg-red-50 text-red-700' : ($plan->estado === 'cumplido' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700') }}">{{ $plan->vencido() ? 'Vencido' : \App\Models\PlanAccion::ESTADOS[$plan->estado] }}</span>
                        <span class="text-gray-800">{{ $plan->accion }}</span>
                        <span class="text-xs text-gray-400">· {{ $plan->responsable?->name }} · hasta {{ $plan->fecha_limite->format('d/m/Y') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Sin planes de acción.</p>
                @endforelse

                @if ($puede && $loop->first)
                    <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="text-sm text-gray-700 md:col-span-2">Hallazgo
                            <textarea wire:model="hallazgo" rows="2" class="mt-1 {{ $campo }}"></textarea>
                            @error('hallazgo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </label>
                        <label class="text-sm text-gray-700 md:col-span-2">Acción de mejora
                            <textarea wire:model="accion" rows="2" placeholder="Qué se hará para corregir el hallazgo" class="mt-1 {{ $campo }}"></textarea>
                            @error('accion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </label>
                        <label class="text-sm text-gray-700">Responsable
                            <select wire:model="responsable" class="mt-1 {{ $campo }}">
                                <option value="">Seleccione…</option>
                                @foreach ($usuarios as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            @error('responsable') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </label>
                        <label class="text-sm text-gray-700">Fecha límite
                            <input type="date" wire:model="fechaLimite" class="mt-1 {{ $campo }}">
                            @error('fechaLimite') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </label>
                        <div class="md:col-span-2 flex justify-end">
                            <button wire:click="agregarPlan({{ $a->id }})" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm">Agregar plan de acción</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
</div>
