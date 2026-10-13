@php
    $pasos = [1 => 'Especialidad', 2 => 'Médico', 3 => 'Día y hora', 4 => 'Sus datos'];
    $tarjeta = 'w-full text-left rounded-2xl border border-gray-200 bg-white px-4 py-4 hover:border-blue-400 hover:bg-blue-50/40 transition';
    $campo = 'w-full rounded-xl border-gray-300 shadow-xs text-base sm:text-sm focus:border-blue-500 focus:ring-blue-500';
    $etiqueta = 'block text-sm font-medium text-gray-700 mb-1';
@endphp
<div>
    @if (!$habilitada)
        <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center">
            <h1 class="text-xl font-bold text-gray-900">La reserva en línea no está disponible</h1>
            <p class="mt-2 text-gray-600">Para agendar una cita comuníquese con la clínica{{ $marca->telefono() ? ' al ' . $marca->telefono() : '' }}.</p>
            @if ($marca->telefono())
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $marca->telefono()) }}" class="mt-5 inline-flex px-5 py-3 rounded-xl bg-blue-600 text-white font-semibold">Llamar</a>
            @endif
        </div>
    @elseif ($paso === 5 && $cita)
        {{-- Confirmación --}}
        <div class="rounded-2xl border border-emerald-200 bg-white p-6 sm:p-8 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">Reserva recibida</h1>
            <p class="mt-2 text-gray-600">La clínica confirmará su cita. Si necesita cambiarla, llame e indique su código.</p>
            <p class="mt-5 text-xs uppercase tracking-wider text-gray-400">Código de reserva</p>
            <p class="text-3xl font-bold tracking-[0.2em] text-blue-700 font-mono">{{ $cita->codigo_reserva }}</p>
            <dl class="mt-6 mx-auto max-w-sm divide-y divide-gray-100 rounded-xl border border-gray-100 text-left text-sm">
                <div class="flex justify-between gap-4 px-4 py-2.5"><dt class="text-gray-500">Día</dt><dd class="font-medium text-gray-900 text-right">{{ $fechaTexto($cita->fecha_hora->toDateString()) }}</dd></div>
                <div class="flex justify-between gap-4 px-4 py-2.5"><dt class="text-gray-500">Hora</dt><dd class="font-medium text-gray-900">{{ $cita->fecha_hora->format('H:i') }}</dd></div>
                <div class="flex justify-between gap-4 px-4 py-2.5"><dt class="text-gray-500">Especialidad</dt><dd class="font-medium text-gray-900 text-right">{{ $cita->especialidad?->nombre }}</dd></div>
                <div class="flex justify-between gap-4 px-4 py-2.5"><dt class="text-gray-500">Médico</dt><dd class="font-medium text-gray-900 text-right">{{ $cita->medico?->nombre_completo }}</dd></div>
            </dl>
            <p class="mt-5 text-sm text-gray-500">Llegue 15 minutos antes con su cédula.</p>
            <a href="{{ route('home') }}" class="mt-6 inline-flex px-5 py-3 rounded-xl border border-gray-200 bg-white font-medium text-gray-700 hover:bg-gray-50">Volver al inicio</a>
        </div>
    @else
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Agendar cita</h1>
        <p class="mt-1 text-gray-500">Elija cuándo quiere ser atendido. La clínica confirmará su reserva.</p>

        {{-- Progreso --}}
        <ol class="mt-5 mb-6 flex gap-1.5" aria-label="Pasos">
            @foreach ($pasos as $n => $texto)
                <li class="flex-1">
                    <button type="button" wire:click="volver({{ $n }})" @disabled($n >= $paso) class="w-full text-left disabled:cursor-default" aria-current="{{ $n === $paso ? 'step' : 'false' }}">
                        <span class="block h-1.5 rounded-full {{ $n <= $paso ? 'bg-blue-600' : 'bg-gray-200' }}"></span>
                        <span class="mt-1.5 block text-[11px] sm:text-xs {{ $n === $paso ? 'font-semibold text-gray-900' : 'text-gray-400' }}">{{ $texto }}</span>
                    </button>
                </li>
            @endforeach
        </ol>

        {{-- Resumen de lo elegido --}}
        @if ($paso > 1 && $especialidad)
            <div class="mb-5 flex flex-wrap gap-2 text-sm">
                <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-800">{{ $especialidad->nombre }}</span>
                @if ($paso > 2)<span class="px-3 py-1 rounded-full bg-blue-50 text-blue-800">{{ $medico?->nombre_completo ?? 'Cualquier médico' }}</span>@endif
                @if ($paso > 3 && $fecha && $hora)<span class="px-3 py-1 rounded-full bg-blue-50 text-blue-800">{{ $fechaTexto($fecha) }} · {{ $hora }}</span>@endif
            </div>
        @endif

        @if ($paso === 1)
            <div class="grid gap-3 sm:grid-cols-2">
                @forelse ($especialidades as $e)
                    <button type="button" wire:click="elegirEspecialidad({{ $e->id }})" wire:key="esp-{{ $e->id }}" class="{{ $tarjeta }}">
                        <span class="block font-semibold text-gray-900">{{ $e->nombre }}</span>
                        <span class="block text-sm text-gray-500">{{ $e->medicos()->where('activo', true)->count() }} {{ $e->medicos()->where('activo', true)->count() === 1 ? 'médico' : 'médicos' }}</span>
                    </button>
                @empty
                    <p class="text-gray-500">No hay especialidades con agenda disponible.</p>
                @endforelse
            </div>
        @elseif ($paso === 2)
            <div class="grid gap-3 sm:grid-cols-2">
                <button type="button" wire:click="elegirMedico('')" class="{{ $tarjeta }}">
                    <span class="block font-semibold text-gray-900">Cualquier médico</span>
                    <span class="block text-sm text-gray-500">Le mostramos más horarios disponibles</span>
                </button>
                @foreach ($medicos as $m)
                    <button type="button" wire:click="elegirMedico('{{ $m->id }}')" wire:key="med-{{ $m->id }}" class="{{ $tarjeta }}">
                        <span class="block font-semibold text-gray-900">{{ $m->nombre_completo }}</span>
                        <span class="block text-sm text-gray-500">{{ $especialidad?->nombre }}</span>
                    </button>
                @endforeach
            </div>
        @elseif ($paso === 3)
            @error('hora') <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $message }}</div> @enderror
            @if (!collect($fechas)->filter()->count())
                <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center text-gray-600">No hay horarios disponibles en los próximos días. Pruebe con otro médico o llame a la clínica.</div>
            @else
                <p class="text-sm font-medium text-gray-700 mb-2">Día</p>
                <div class="-mx-4 px-4 flex gap-2 overflow-x-auto pb-2 snap-x">
                    @foreach ($fechas as $f => $libres)
                        @php $d = \Carbon\Carbon::parse($f)->locale('es'); @endphp
                        <button type="button" wire:click="elegirFecha('{{ $f }}')" wire:key="f-{{ $f }}" @disabled(!$libres)
                                class="snap-start shrink-0 w-18 rounded-xl border px-2 py-2.5 text-center transition disabled:opacity-35 disabled:cursor-not-allowed {{ $fecha === $f ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-800 hover:border-blue-400' }}">
                            <span class="block text-[11px] uppercase {{ $fecha === $f ? 'text-white/80' : 'text-gray-500' }}">{{ $d->translatedFormat('D') }}</span>
                            <span class="block text-lg font-bold leading-tight">{{ $d->day }}</span>
                            <span class="block text-[11px] {{ $fecha === $f ? 'text-white/80' : 'text-gray-500' }}">{{ $d->translatedFormat('M') }}</span>
                        </button>
                    @endforeach
                </div>

                @if ($fecha)
                    <p class="mt-5 text-sm font-medium text-gray-700 mb-2">Hora · {{ $fechaTexto($fecha) }}</p>
                    @if ($horarios)
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                            @foreach ($horarios as $h => $ids)
                                <button type="button" wire:click="elegirHora('{{ $h }}')" wire:key="h-{{ $fecha }}-{{ $h }}"
                                        class="rounded-xl border border-gray-200 bg-white py-3 text-center font-semibold tabular-nums text-gray-900 hover:border-blue-500 hover:bg-blue-50">{{ $h }}</button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No quedan horarios libres este día.</p>
                    @endif
                @endif
            @endif
        @elseif ($paso === 4)
            <form wire:submit="reservar" class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                <div>
                    <label for="motivo" class="{{ $etiqueta }}">Motivo de la cita</label>
                    <select id="motivo" wire:model="motivo" class="{{ $campo }}">
                        <option value="">Elija una opción</option>
                        @foreach ($motivos as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                    </select>
                    @error('motivo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cedula" class="{{ $etiqueta }}">Cédula</label>
                    <input id="cedula" wire:model="cedula" type="text" inputmode="numeric" maxlength="10" autocomplete="off" class="{{ $campo }} font-mono">
                    @error('cedula') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nombres" class="{{ $etiqueta }}">Nombres</label>
                        <input id="nombres" wire:model="nombres" type="text" autocomplete="given-name" class="{{ $campo }}">
                        @error('nombres') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="apellidos" class="{{ $etiqueta }}">Apellidos</label>
                        <input id="apellidos" wire:model="apellidos" type="text" autocomplete="family-name" class="{{ $campo }}">
                        @error('apellidos') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="telefono" class="{{ $etiqueta }}">Celular</label>
                        <input id="telefono" wire:model="telefono" type="tel" inputmode="tel" autocomplete="tel" placeholder="0991234567" class="{{ $campo }}">
                        @error('telefono') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="{{ $etiqueta }}">Correo <span class="font-normal text-gray-400">(opcional)</span></label>
                        <input id="email" wire:model="email" type="email" autocomplete="email" class="{{ $campo }}">
                        @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sexo" class="{{ $etiqueta }}">Sexo</label>
                        <select id="sexo" wire:model="sexo" class="{{ $campo }}">
                            <option value="">Elija una opción</option>
                            <option value="femenino">Femenino</option>
                            <option value="masculino">Masculino</option>
                        </select>
                        @error('sexo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="fecha_nacimiento" class="{{ $etiqueta }}">Fecha de nacimiento <span class="font-normal text-gray-400">(opcional)</span></label>
                        <input id="fecha_nacimiento" wire:model="fecha_nacimiento" type="date" class="{{ $campo }}">
                        @error('fecha_nacimiento') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                {{-- Trampa para robots: oculta para las personas --}}
                <div class="hidden" aria-hidden="true">
                    <label for="sitio_web">Sitio web</label>
                    <input id="sitio_web" wire:model="sitio_web" type="text" tabindex="-1" autocomplete="off">
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                    <button type="button" wire:click="volver(3)" class="text-sm text-gray-500 hover:text-gray-800">Cambiar día u hora</button>
                    <button type="submit" wire:loading.attr="disabled" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold shadow-xs hover:bg-blue-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="reservar">Reservar cita</span>
                        <span wire:loading wire:target="reservar">Reservando…</span>
                    </button>
                </div>
            </form>
        @endif
    @endif
</div>
