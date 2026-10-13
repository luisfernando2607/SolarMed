<div>
<div class="max-w-6xl mx-auto py-8 px-4">
    <div class="mb-6">
        <a href="{{ url()->previous() }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Volver</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Control prenatal — {{ $paciente->nombre_completo }}</h1>
        <p class="text-sm text-gray-500">
            C.I. {{ $paciente->cedula }}
            @if ($paciente->edad !== null) · {{ $paciente->edad }} años @endif
            · G{{ $paciente->gestas ?? '-' }} P{{ $paciente->partos ?? '-' }} C{{ $paciente->cesareas ?? '-' }} A{{ $paciente->abortos ?? '-' }}
            @if ($paciente->grupo_sanguineo) · Grupo {{ $paciente->grupo_sanguineo }} @endif
            @if ($paciente->alergias) · <span class="text-red-600 font-medium">Alergias: {{ $paciente->alergias }}</span> @endif
        </p>
    </div>

    @if (!$embarazo)
        <div class="bg-white rounded-xl shadow-xs border p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Sin embarazo activo</h2>
            @can('expediente.crear')
                <p class="text-sm text-gray-500 mb-4">Registre la fecha de última menstruación para iniciar el seguimiento.</p>
                <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">FUM *</label>
                        <input wire:model="fum" type="date" max="{{ now()->format('Y-m-d') }}"
                            class="rounded-lg border-gray-300 shadow-xs focus:border-pink-500 focus:ring-pink-500 text-sm">
                    </div>
                    <button wire:click="iniciarEmbarazo"
                        class="px-5 py-2.5 bg-pink-600 text-white text-sm font-medium rounded-lg hover:bg-pink-700 transition">Iniciar control prenatal</button>
                </div>
                @error('fum') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            @endcan
        </div>
    @else
        {{-- Resumen --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-pink-50 border border-pink-200 rounded-xl p-4">
                <p class="text-xs text-pink-700">Edad gestacional hoy</p>
                <p class="text-2xl font-bold text-pink-800">{{ $egHoy['semanas'] }}<span class="text-base font-medium">+{{ $egHoy['dias'] }}</span> <span class="text-sm font-normal">sem</span></p>
                <p class="text-xs text-pink-700">{{ $trimestre }}.º trimestre</p>
            </div>
            <div class="bg-white border rounded-xl p-4">
                <p class="text-xs text-gray-500">FUM</p>
                <p class="text-lg font-semibold text-gray-900">{{ $embarazo->fum->format('d/m/Y') }}</p>
            </div>
            <div class="bg-white border rounded-xl p-4">
                <p class="text-xs text-gray-500">FPP (Naegele)</p>
                <p class="text-lg font-semibold text-gray-900">{{ $embarazo->fpp->format('d/m/Y') }}</p>
                <p class="text-xs text-gray-500">{{ now()->startOfDay()->diffInDays($embarazo->fpp, false) >= 0 ? 'faltan ' . (int) now()->startOfDay()->diffInDays($embarazo->fpp) . ' días' : 'fecha superada' }}</p>
            </div>
            <div class="bg-white border rounded-xl p-4">
                <p class="text-xs text-gray-500">Controles</p>
                <p class="text-lg font-semibold text-gray-900">{{ $embarazo->controles->count() }}</p>
                <p class="text-xs text-gray-500">Médico: {{ $embarazo->medico?->nombre_completo }}</p>
            </div>
        </div>

        {{-- Controles --}}
        <div class="bg-white rounded-xl shadow-xs border mb-6 overflow-hidden">
            <div class="px-6 py-4 border-b"><h2 class="text-lg font-semibold text-gray-900">Controles</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-2">Fecha</th>
                            <th class="text-left px-4 py-2">EG</th>
                            <th class="text-right px-4 py-2">Peso kg</th>
                            <th class="text-center px-4 py-2">PA</th>
                            <th class="text-right px-4 py-2">AU cm</th>
                            <th class="text-right px-4 py-2">FCF</th>
                            <th class="text-left px-4 py-2">Presentación</th>
                            <th class="text-center px-4 py-2">MF</th>
                            <th class="text-left px-4 py-2">Edemas</th>
                            <th class="text-left px-4 py-2">Alertas / notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($embarazo->controles as $c)
                            @php $al = $alertas[$c->id] ?? []; @endphp
                            <tr class="{{ collect($al)->contains('nivel', 'alta') ? 'bg-red-50' : (count($al) ? 'bg-amber-50' : '') }}">
                                <td class="px-4 py-2 whitespace-nowrap">{{ $c->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $c->semanas_gestacion }}+{{ $c->dias_gestacion ?? 0 }}</td>
                                <td class="px-4 py-2 text-right">{{ $c->peso_materno ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">{{ $c->presion_arterial ?? '—' }}</td>
                                <td class="px-4 py-2 text-right">{{ $c->altura_uterina ?? '—' }}</td>
                                <td class="px-4 py-2 text-right">{{ $c->fcf ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $c->presentacion ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">{{ $c->movimientos_fetales === null ? '—' : ($c->movimientos_fetales ? 'Sí' : 'No') }}</td>
                                <td class="px-4 py-2">{{ $c->edemas ?? '—' }}</td>
                                <td class="px-4 py-2 text-xs">
                                    @foreach ($al as $a)
                                        <p class="{{ $a['nivel'] === 'alta' ? 'text-red-700 font-medium' : 'text-amber-700' }}">⚠ {{ $a['mensaje'] }}</p>
                                    @endforeach
                                    @if ($c->observaciones)<p class="text-gray-600">{{ $c->observaciones }}</p>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-6 text-center text-gray-400">Aún no hay controles registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @can('expediente.crear')
            {{-- Nuevo control --}}
            <div class="bg-white rounded-xl shadow-xs border p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Registrar control</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fecha *</label>
                        <input wire:model="fecha" type="date" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Peso materno (kg)</label>
                        <input wire:model="peso_materno" type="number" step="0.1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @error('peso_materno') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Presión arterial</label>
                        <input wire:model="presion_arterial" type="text" placeholder="120/80" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @error('presion_arterial') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Altura uterina (cm)</label>
                        <input wire:model="altura_uterina" type="number" step="0.5" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @error('altura_uterina') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">FCF (lpm)</label>
                        <input wire:model="fcf" type="number" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @error('fcf') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Presentación</label>
                        <select wire:model="presentacion" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                            <option value="">—</option>
                            @foreach (\App\Livewire\ControlPrenatalPaciente::PRESENTACIONES as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Movimientos fetales</label>
                        <select wire:model="movimientos_fetales" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                            <option value="">—</option>
                            <option value="1">Presentes</option>
                            <option value="0">Ausentes</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Edemas</label>
                        <select wire:model="edemas" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                            <option value="">—</option>
                            @foreach (\App\Livewire\ControlPrenatalPaciente::EDEMAS as $e)
                                <option value="{{ $e }}">{{ $e }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2 md:col-span-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Observaciones</label>
                        <textarea wire:model="observaciones" rows="2" class="w-full rounded-lg border-gray-300 shadow-xs text-sm"></textarea>
                    </div>
                </div>
                <div class="flex justify-end mt-4">
                    <button wire:click="registrarControl"
                        class="px-5 py-2.5 bg-pink-600 text-white text-sm font-medium rounded-lg hover:bg-pink-700 transition">Guardar control</button>
                </div>
            </div>
        @endcan

        {{-- Checklist de exámenes --}}
        <div class="bg-white rounded-xl shadow-xs border p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Exámenes por trimestre</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($checklistConfig as $t => $examenes)
                    <div>
                        <h3 class="text-sm font-semibold mb-2 {{ $t === $trimestre ? 'text-pink-700' : 'text-gray-600' }}">
                            {{ $t }}.º trimestre @if ($t === $trimestre)<span class="text-xs font-normal">(actual)</span>@endif
                        </h3>
                        <div class="space-y-1.5">
                            @foreach ($examenes as $clave => $nombre)
                                @php $hecho = $embarazo->checklist[$clave] ?? null; @endphp
                                <label class="flex items-start gap-2 text-sm {{ $hecho ? 'text-gray-500' : ($t < $trimestre ? 'text-red-700' : 'text-gray-800') }}">
                                    <input type="checkbox" @checked($hecho) wire:click="toggleExamen('{{ $clave }}')"
                                        @cannot('expediente.crear') disabled @endcannot
                                        class="mt-0.5 rounded-sm border-gray-300 text-pink-600 focus:ring-pink-500">
                                    <span>{{ $nombre }}
                                        @if ($hecho)<span class="text-xs text-gray-400">({{ \Carbon\Carbon::parse($hecho)->format('d/m/Y') }})</span>
                                        @elseif ($t < $trimestre)<span class="text-xs">— pendiente</span>@endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @can('expediente.crear')
            {{-- Cierre --}}
            <div class="bg-white rounded-xl shadow-xs border p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Finalizar embarazo</h2>
                <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Desenlace *</label>
                        <select wire:model="desenlace" class="rounded-lg border-gray-300 shadow-xs text-sm">
                            <option value="">Seleccione...</option>
                            @foreach (\App\Models\Embarazo::DESENLACES as $clave => $nombre)
                                <option value="{{ $clave }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fecha *</label>
                        <input wire:model="fecha_fin" type="date" class="rounded-lg border-gray-300 shadow-xs text-sm">
                    </div>
                    <button wire:click="cerrarEmbarazo" wire:confirm="¿Finalizar el seguimiento de este embarazo?"
                        class="px-5 py-2.5 border border-gray-400 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Finalizar</button>
                </div>
                @error('desenlace') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @error('fecha_fin') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        @endcan
    @endif

    @if ($anteriores->isNotEmpty())
        <div class="bg-white rounded-xl shadow-xs border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Embarazos anteriores</h2>
            <div class="divide-y text-sm">
                @foreach ($anteriores as $e)
                    <div class="py-2 flex flex-wrap gap-x-4">
                        <span>FUM {{ $e->fum->format('d/m/Y') }}</span>
                        <span class="text-gray-600">{{ \App\Models\Embarazo::DESENLACES[$e->desenlace] ?? $e->desenlace }} · {{ $e->fecha_fin?->format('d/m/Y') }}</span>
                        <span class="text-gray-400">{{ $e->controles_count }} controles</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
</div>
