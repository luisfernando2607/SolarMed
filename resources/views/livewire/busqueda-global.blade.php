<div x-data="{
        abierto: false,
        activo: 0,
        abrir() { this.abierto = true; this.activo = 0; this.$nextTick(() => this.$refs.campo.focus()); },
        cerrar() { this.abierto = false; },
        resultados() { return [...this.$root.querySelectorAll('[data-resultado]')]; },
        mover(paso) {
            const r = this.resultados();
            if (!r.length) return;
            this.activo = (this.activo + paso + r.length) % r.length;
            r[this.activo].scrollIntoView({ block: 'nearest' });
        },
        ir() { const r = this.resultados()[this.activo]; if (r) { this.cerrar(); Livewire.navigate(r.getAttribute('href')); } },
     }"
     x-on:abrir-busqueda.window="abrir()"
     x-on:keydown.window="
        const escribiendo = ['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) || $event.target.isContentEditable;
        if ((($event.ctrlKey || $event.metaKey) && $event.key.toLowerCase() === 'k') || ($event.key === '/' && !escribiendo)) { $event.preventDefault(); abrir(); }
     "
     x-on:livewire:navigated.window="cerrar()">

    <div x-show="abierto" x-cloak class="fixed inset-0 z-[70] flex items-start justify-center p-4 pt-[12vh]" role="dialog" aria-modal="true" aria-label="Buscar">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-[2px]" x-show="abierto" x-transition.opacity @click="cerrar()"></div>

        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl ring-1 ring-gray-200 overflow-hidden"
             x-show="abierto" x-transition.scale.origin.top.duration.150ms
             @keydown.escape.prevent="cerrar()" @keydown.arrow-down.prevent="mover(1)" @keydown.arrow-up.prevent="mover(-1)" @keydown.enter.prevent="ir()">

            <div class="flex items-center gap-3 px-4 border-b border-gray-100">
                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input x-ref="campo" type="search" wire:model.live.debounce.250ms="q" @input="activo = 0"
                       placeholder="Buscar paciente, cédula, factura o pantalla…" autocomplete="off"
                       class="flex-1 h-14 border-0 focus:ring-0 text-[15px] text-gray-900 placeholder-gray-400 px-0">
                <kbd class="hidden sm:inline text-[11px] text-gray-400 border border-gray-200 rounded-md px-1.5 py-0.5">Esc</kbd>
            </div>

            @php $indice = 0; @endphp
            <div class="max-h-[60vh] overflow-y-auto py-2">
                @if (mb_strlen(trim($q)) < 2)
                    <p class="px-5 py-8 text-center text-sm text-gray-400">Escriba al menos 2 letras. Use <kbd class="text-xs border border-gray-200 rounded px-1">↑</kbd> <kbd class="text-xs border border-gray-200 rounded px-1">↓</kbd> y <kbd class="text-xs border border-gray-200 rounded px-1">Enter</kbd>.</p>
                @elseif ($pacientes->isEmpty() && $facturas->isEmpty() && !$pantallas)
                    <p class="px-5 py-8 text-center text-sm text-gray-400">Sin resultados para «{{ $q }}».</p>
                @else
                    @if ($pacientes->isNotEmpty())
                        <p class="px-5 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Pacientes</p>
                        @foreach ($pacientes as $p)
                            <a href="{{ route('pacientes.ver', $p) }}" data-resultado wire:key="bp-{{ $p->id }}"
                               @mouseenter="activo = {{ $indice }}" @click.prevent="activo = {{ $indice }}; ir()"
                               :class="activo === {{ $indice++ }} ? 'bg-blue-50' : ''"
                               class="flex items-center gap-3 mx-2 px-3 py-2.5 rounded-xl">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold shrink-0">{{ mb_strtoupper(mb_substr((string) $p->nombres, 0, 1) . mb_substr((string) $p->apellidos, 0, 1)) }}</span>
                                <span class="flex-1 min-w-0">
                                    <span class="block text-sm font-medium text-gray-900 truncate">{{ $p->nombre_completo }}</span>
                                    <span class="block text-xs text-gray-500"><span class="font-mono">{{ $p->cedula }}</span>{{ $p->edad !== null ? ' · ' . $p->edad . ' años' : '' }}{{ $p->telefono ? ' · ' . $p->telefono : '' }}</span>
                                </span>
                            </a>
                        @endforeach
                    @endif

                    @if ($facturas->isNotEmpty())
                        <p class="px-5 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Facturas</p>
                        @foreach ($facturas as $f)
                            <a href="{{ route('facturas.ver', $f) }}" data-resultado wire:key="bf-{{ $f->id }}"
                               @mouseenter="activo = {{ $indice }}" @click.prevent="activo = {{ $indice }}; ir()"
                               :class="activo === {{ $indice++ }} ? 'bg-blue-50' : ''"
                               class="flex items-center gap-3 mx-2 px-3 py-2.5 rounded-xl text-sm">
                                <span class="font-mono text-[13px] {{ $f->estado === 'anulada' ? 'line-through text-gray-400' : 'text-gray-900' }}">{{ $f->numero_factura }}</span>
                                <span class="flex-1 min-w-0 truncate text-gray-500">{{ $f->paciente?->nombre_completo }}</span>
                                <span class="text-gray-700 tabular-nums">${{ number_format((float) $f->total, 2) }}</span>
                            </a>
                        @endforeach
                    @endif

                    @if ($pantallas)
                        <p class="px-5 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Ir a</p>
                        @foreach ($pantallas as [$texto, $url])
                            <a href="{{ $url }}" data-resultado wire:key="bs-{{ $loop->index }}"
                               @mouseenter="activo = {{ $indice }}" @click.prevent="activo = {{ $indice }}; ir()"
                               :class="activo === {{ $indice++ }} ? 'bg-blue-50' : ''"
                               class="flex items-center gap-3 mx-2 px-3 py-2.5 rounded-xl text-sm text-gray-800">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                {{ $texto }}
                            </a>
                        @endforeach
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
