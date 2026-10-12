<div wire:poll.60s>
<style>
    .inicio .tarjeta { background: #fff; border: 1px solid #e9e7e2; border-radius: 16px; }
</style>
@php
    $hora = (int) now()->format('H');
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    $nombre = $medico ? trim(($medico->nombres ? 'Dr(a). ' . Str::before($medico->nombres, ' ') : '')) : Str::before(auth()->user()->name, ' ');
    $estadosCita = [
        'pendiente' => ['Pendiente', 'bg-amber-50 text-amber-700'],
        'confirmada' => ['Confirmada', 'bg-emerald-50 text-emerald-700'],
        'completada' => ['Atendida', 'bg-gray-100 text-gray-500'],
    ];
    $boton = 'inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition';
@endphp
<div class="inicio max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500">{{ ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')) }}</p>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mt-1">{{ $saludo }}, {{ $nombre ?: auth()->user()->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $medico ? ($medico->especialidad?->nombre . ' · ' . $marca->nombre()) : $marca->nombre() }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('turnos.ver')
                <a href="{{ route('sala-espera') }}" wire:navigate class="{{ $boton }}">Sala de espera</a>
                <a href="{{ route('agenda') }}" wire:navigate class="{{ $boton }}">Agenda</a>
            @endcan
            @can('pacientes.ver')
                <a href="{{ route('pacientes') }}" wire:navigate class="{{ $boton }}">Buscar paciente</a>
            @endcan
            @if (!$medico)
                @can('turnos.ver')
                    <a href="{{ route('turnos.crear') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white shadow-sm hover:opacity-90 transition" style="background: {{ $marca->color() }}">+ Registrar turno</a>
                @endcan
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach ($kpis as $k)
            <a href="{{ route($k['ruta']) }}" wire:navigate class="tarjeta p-5 hover:shadow-sm transition">
                <p class="text-sm font-medium text-gray-500">{{ $k['titulo'] }}</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ $k['valor'] }}</p>
                <p class="mt-2 text-xs text-gray-400">{{ $k['detalle'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
        {{-- Agenda de hoy --}}
        <section class="tarjeta p-5 lg:col-span-3">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">{{ $medico ? 'Mi agenda de hoy' : 'Agenda de hoy' }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $citasHoy->count() }} {{ $citasHoy->count() === 1 ? 'cita' : 'citas' }}</p>
                </div>
                @can('turnos.ver')<a href="{{ route('agenda') }}" wire:navigate class="text-xs font-medium hover:underline" style="color: {{ $marca->color() }}">Ver agenda →</a>@endcan
            </div>
            @forelse ($citasHoy as $c)
                @php $pasada = $c->fecha_hora->copy()->addMinutes($c->duracion_min)->isPast(); @endphp
                <div class="flex items-center gap-4 py-3 border-t first:border-t-0 {{ $c->estado === 'completada' ? 'opacity-60' : '' }}">
                    <div class="w-14 text-center shrink-0">
                        <p class="text-base font-semibold {{ $pasada && $c->estado !== 'completada' ? 'text-gray-400' : 'text-gray-900' }}">{{ $c->fecha_hora->format('H:i') }}</p>
                        <p class="text-[11px] text-gray-400">{{ $c->duracion_min }} min</p>
                    </div>
                    <div class="w-1 self-stretch rounded-full" style="background: {{ $c->especialidad?->color_agenda ?? $marca->color() }}"></div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $c->paciente?->nombre_visible }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $c->tipo }}@unless ($medico) · {{ $c->medico?->nombre_completo }}@endunless</p>
                    </div>
                    @if (in_array($c->estado, ['pendiente', 'confirmada'], true) && $c->fecha_hora->isFuture() && ($wa = app(\App\Services\WhatsappService::class)->enlaceRecordatorio($c)))
                        <x-boton-whatsapp :href="$wa" compacto class="shrink-0" />
                    @endif
                    <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium {{ $estadosCita[$c->estado][1] ?? 'bg-gray-100 text-gray-600' }}">{{ $estadosCita[$c->estado][0] ?? $c->estado }}</span>
                </div>
            @empty
                <div class="py-10 text-center">
                    <p class="text-sm text-gray-500">No hay citas para hoy.</p>
                    @can('turnos.ver')<a href="{{ route('agenda') }}" wire:navigate class="inline-block mt-2 text-sm font-medium hover:underline" style="color: {{ $marca->color() }}">Agendar una cita</a>@endcan
                </div>
            @endforelse
        </section>

        {{-- Sala de espera --}}
        <section class="tarjeta p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">En sala de espera</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $totalEsperando }} {{ $totalEsperando === 1 ? 'paciente' : 'pacientes' }}{{ $medico ? ' de ' . $medico->especialidad?->nombre : '' }}</p>
                </div>
                @can('turnos.ver')<a href="{{ route('sala-espera') }}" wire:navigate class="text-xs font-medium hover:underline" style="color: {{ $marca->color() }}">Ir a la sala →</a>@endcan
            </div>
            @forelse ($esperando as $t)
                <div class="flex items-center gap-3 py-2.5 border-t first:border-t-0">
                    <span class="flex items-center justify-center w-12 h-9 rounded-lg text-sm font-bold text-white shrink-0" style="background: {{ $t->especialidad?->color_agenda ?? $marca->color() }}">{{ $t->prefijo }}{{ $t->numero_turno }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $t->paciente?->nombre_visible ?? $t->nombre_temporal }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $t->motivo }}</p>
                    </div>
                    <span class="text-xs text-gray-400 shrink-0" title="Hora de llegada">{{ $t->hora_registro ? \Carbon\Carbon::parse($t->hora_registro)->diffForHumans(null, true) : '' }}</span>
                </div>
            @empty
                <p class="py-10 text-center text-sm text-gray-500">Nadie en espera.</p>
            @endforelse
        </section>
    </div>

    <section>
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">Requiere atención</h2>
        <livewire:panel-pendientes :mostrar-hoy="false" />
    </section>
</div>
</div>
