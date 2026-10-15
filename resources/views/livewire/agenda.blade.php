<div>
<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <h1 class="text-2xl font-bold text-gray-900">Agenda de citas</h1>
        <div class="flex items-center gap-3">
            <select wire:model.live="filtroMedico" class="rounded-lg border-gray-300 shadow-xs text-sm">
                <option value="">Todos los médicos</option>
                @foreach ($medicos as $m)
                    <option value="{{ $m->id }}">{{ $m->nombre_completo }} — {{ $m->especialidad?->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @can('turnos.gestionar')
        <p class="text-xs text-gray-500 mb-3">Haga clic en un horario libre para agendar. Arrastre una cita para reprogramarla.</p>
    @endcan

    <div class="bg-white rounded-xl shadow-xs border p-4">
        <div wire:ignore id="calendario-citas"></div>
    </div>

    {{-- Modal crear cita --}}
    @if ($modal === 'crear')
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:keydown.escape.window="cerrarModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Nueva cita</h2>

                {{-- Paciente --}}
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Paciente *</label>
                    @if ($pacienteSeleccionado)
                        <div class="flex items-center justify-between bg-blue-50 rounded-lg px-3 py-2 text-sm">
                            <span>{{ $pacienteSeleccionado->nombre_completo }} <span class="text-gray-500">· {{ $pacienteSeleccionado->cedula }}</span></span>
                            <button wire:click="quitarPaciente" class="text-xs text-blue-700 hover:underline">Cambiar</button>
                        </div>
                    @elseif (!$pacienteNuevo)
                        <input wire:model.live.debounce.300ms="busquedaPaciente" type="text" placeholder="Buscar por cédula o nombre..."
                            class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @if ($pacientes->isNotEmpty())
                            <div class="border rounded-lg mt-1 divide-y max-h-48 overflow-y-auto">
                                @foreach ($pacientes as $p)
                                    <button wire:click="seleccionarPaciente({{ $p->id }})" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50">
                                        {{ $p->nombre_completo }} <span class="text-gray-500">· {{ $p->cedula }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        <button wire:click="$set('pacienteNuevo', true)" class="text-xs text-blue-700 hover:underline mt-1">+ Paciente nuevo</button>
                        @error('busquedaPaciente') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @else
                        <div class="grid grid-cols-2 gap-2 border rounded-lg p-3">
                            <div><input wire:model="nuevo_nombres" placeholder="Nombres *" class="w-full rounded-lg border-gray-300 text-sm">
                                @error('nuevo_nombres') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror</div>
                            <div><input wire:model="nuevo_apellidos" placeholder="Apellidos *" class="w-full rounded-lg border-gray-300 text-sm">
                                @error('nuevo_apellidos') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror</div>
                            <div><input wire:model="nuevo_cedula" placeholder="Cédula *" maxlength="13" class="w-full rounded-lg border-gray-300 text-sm">
                                @error('nuevo_cedula') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror</div>
                            <div><input wire:model="nuevo_telefono" placeholder="Teléfono *" maxlength="15" class="w-full rounded-lg border-gray-300 text-sm">
                                @error('nuevo_telefono') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror</div>
                            <div>
                                <select wire:model="nuevo_sexo" class="w-full rounded-lg border-gray-300 text-sm">
                                    <option value="femenino">Femenino</option>
                                    <option value="masculino">Masculino</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div class="flex items-center"><button wire:click="$set('pacienteNuevo', false)" class="text-xs text-blue-700 hover:underline">Buscar existente</button></div>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Médico *</label>
                        <select wire:model="medico_id" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                            <option value="">Seleccione...</option>
                            @foreach ($medicos as $m)
                                <option value="{{ $m->id }}">{{ $m->nombre_completo }} — {{ $m->especialidad?->nombre }}</option>
                            @endforeach
                        </select>
                        @error('medico_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fecha y hora *</label>
                        <input wire:model="fecha_hora" type="datetime-local" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @error('fecha_hora') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Duración (min) *</label>
                        <select wire:model="duracion_min" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                            @foreach ($duraciones as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo *</label>
                        <select wire:model="tipo" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                            @foreach ($tipos as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Notas</label>
                        <input wire:model="notas" type="text" maxlength="500" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="cerrarModal" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button wire:click="guardarCita" class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Agendar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal ver cita --}}
    @if ($modal === 'ver' && $cita)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:keydown.escape.window="cerrarModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <div class="flex justify-between items-start mb-3">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $cita->tipo }}</h2>
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full
                        @switch($cita->estado)
                            @case('confirmada') bg-green-100 text-green-700 @break
                            @case('cancelada') bg-gray-200 text-gray-600 @break
                            @case('completada') bg-blue-100 text-blue-700 @break
                            @default bg-amber-100 text-amber-700
                        @endswitch">{{ ucfirst($cita->estado) }}</span>
                </div>
                @if ($cita->origen === 'web')
                    <p class="mb-3 inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-800">
                        Reservada en línea por el paciente · código <span class="font-mono">{{ $cita->codigo_reserva }}</span>
                    </p>
                @endif
                <dl class="text-sm space-y-1.5">
                    <div><dt class="inline text-gray-500">Paciente:</dt> <dd class="inline font-medium">{{ $cita->paciente?->nombre_completo }}</dd> <span class="text-gray-500">· {{ $cita->paciente?->cedula }}</span></div>
                    @if ($cita->paciente?->telefono)<div><dt class="inline text-gray-500">Teléfono:</dt> <dd class="inline">{{ $cita->paciente->telefono }}</dd></div>@endif
                    <div><dt class="inline text-gray-500">Médico:</dt> <dd class="inline">{{ $cita->medico?->nombre_completo }}</dd></div>
                    <div><dt class="inline text-gray-500">Fecha:</dt> <dd class="inline">{{ $cita->fecha_hora->translatedFormat('l d/m/Y H:i') }} ({{ $cita->duracion_min }} min)</dd></div>
                    @if ($cita->notas)<div><dt class="inline text-gray-500">Notas:</dt> <dd class="inline">{{ $cita->notas }}</dd></div>@endif
                </dl>

                @if ($ultimoWhatsapp)
                    @php [$waTexto, $waTono] = \App\Models\WhatsappMensaje::ESTADOS[$ultimoWhatsapp->estado] ?? [$ultimoWhatsapp->estado, 'bg-gray-100 text-gray-600']; @endphp
                    <p class="mt-3 text-xs text-gray-500">
                        WhatsApp ({{ \App\Models\WhatsappMensaje::TIPOS[$ultimoWhatsapp->tipo] ?? $ultimoWhatsapp->tipo }}, {{ $ultimoWhatsapp->created_at->format('d/m H:i') }}):
                        <span class="px-1.5 py-0.5 rounded-md font-medium {{ $waTono }}">{{ $waTexto }}</span>
                        @if ($ultimoWhatsapp->respuesta_payload || $ultimoWhatsapp->respuesta_texto)
                            · respondió: <span class="font-medium text-gray-700">{{ $ultimoWhatsapp->respuesta_texto ?: $ultimoWhatsapp->respuesta_payload }}</span>
                        @elseif ($ultimoWhatsapp->estado === 'fallido')
                            · {{ $ultimoWhatsapp->error_mensaje }}
                        @endif
                    </p>
                @endif

                <div class="flex flex-wrap justify-end gap-2 mt-6">
                    <button wire:click="cerrarModal" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Cerrar</button>
                    @if ($enlaceWhatsapp && $whatsappApi)
                        @can('turnos.gestionar')
                            <button wire:click="enviarWhatsapp" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                Enviar recordatorio por WhatsApp
                            </button>
                        @endcan
                    @elseif ($enlaceWhatsapp)
                        <x-boton-whatsapp :href="$enlaceWhatsapp" />
                    @endif
                    @can('turnos.gestionar')
                        @if (in_array($cita->estado, ['pendiente', 'confirmada'], true))
                            <button wire:click="cambiarEstado('cancelada')" wire:confirm="¿Cancelar esta cita?"
                                class="px-4 py-2 border border-red-300 text-red-700 text-sm rounded-lg hover:bg-red-50">Cancelar cita</button>
                            @if ($cita->estado === 'pendiente')
                                <button wire:click="cambiarEstado('confirmada')" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">Confirmar</button>
                            @endif
                            @if ($cita->fecha_hora->isToday())
                                <button wire:click="registrarLlegada" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Registrar llegada</button>
                            @endif
                        @elseif ($cita->estado === 'cancelada')
                            <button wire:click="cambiarEstado('pendiente')" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Reactivar</button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    #calendario-citas .fc-event.cita-cancelada { text-decoration: line-through; opacity: .7; }
    #calendario-citas .fc-event.cita-completada { opacity: .55; }
    #calendario-citas .fc-event { cursor: pointer; font-size: .75rem; }
</style>
</div>

@script
<script>
    const puedeGestionar = @js(auth()->user()->can('turnos.gestionar'));
    const calendario = new window.FullCalendar.Calendar(document.getElementById('calendario-citas'), {
        plugins: window.FullCalendar.plugins,
        locale: window.FullCalendar.esLocale,
        initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
        slotMinTime: @js($calendario['inicio']),
        slotMaxTime: @js($calendario['fin']),
        slotDuration: '00:15:00',
        allDaySlot: false,
        nowIndicator: true,
        height: 'auto',
        businessHours: { daysOfWeek: @js($calendario['dias']), startTime: @js($calendario['inicio']), endTime: @js($calendario['fin']) },
        selectable: puedeGestionar,
        editable: puedeGestionar,
        events: (info, ok, error) => $wire.eventos(info.startStr, info.endStr).then(ok).catch(error),
        dateClick: (info) => { if (puedeGestionar && info.view.type !== 'dayGridMonth') $wire.nuevaCita(info.dateStr); },
        eventClick: (info) => $wire.verCita(Number(info.event.id)),
        eventDrop: (info) => $wire.mover(Number(info.event.id), info.event.startStr, info.event.endStr).then(ok => { if (!ok) info.revert(); }),
        eventResize: (info) => $wire.mover(Number(info.event.id), info.event.startStr, info.event.endStr).then(ok => { if (!ok) info.revert(); }),
    });
    calendario.render();
    // Ajusta el calendario cuando cambia el ancho disponible (p. ej. al contraer la barra lateral)
    new ResizeObserver(() => calendario.updateSize()).observe(document.getElementById('calendario-citas'));
    $wire.on('agenda-actualizada', () => calendario.refetchEvents());
</script>
@endscript
