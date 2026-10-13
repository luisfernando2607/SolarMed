<div>
<style>
    .ficha .tarjeta { background: #fff; border: 1px solid #e9e7e2; border-radius: 16px; }
</style>
@php
    $p = $paciente;
    $ini = mb_strtoupper(mb_substr((string) $p->nombres, 0, 1) . mb_substr((string) $p->apellidos, 0, 1));
    $tonoSexo = ['femenino' => 'bg-pink-50 text-pink-700', 'masculino' => 'bg-sky-50 text-sky-700'][$p->sexo] ?? 'bg-gray-100 text-gray-600';
    $boton = 'inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition';
    $estadosCita = [
        'pendiente' => ['Pendiente', 'bg-amber-50 text-amber-700'],
        'confirmada' => ['Confirmada', 'bg-emerald-50 text-emerald-700'],
        'completada' => ['Atendida', 'bg-gray-100 text-gray-600'],
        'cancelada' => ['Cancelada', 'bg-red-50 text-red-700'],
    ];
    $dato = fn ($v) => filled($v) ? $v : '—';
    $pestanas = array_filter([
        'consultas' => $clinico ? ['Consultas', $consultas->count()] : null,
        'documentos' => $clinico ? ['Documentos', $documentos->count()] : null,
        'citas' => auth()->user()->can('turnos.ver') ? ['Citas', $citas->count()] : null,
        'facturas' => auth()->user()->can('facturas.ver') ? ['Facturas', $facturas->count()] : null,
    ]);
@endphp
<div class="ficha max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <a href="{{ route('pacientes') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Pacientes
    </a>

    {{-- Encabezado --}}
    <div class="tarjeta p-5 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-center gap-5">
            <div class="flex items-center gap-4 min-w-0 flex-1">
                <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 text-lg font-semibold shrink-0">{{ $ini }}</span>
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ $p->nombre_completo }}</h1>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-sm text-gray-500">
                        <span class="font-mono">{{ $p->cedula }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $tonoSexo }}">{{ $p->sexo }}</span>
                        @if ($p->edad !== null)
                            <span>{{ $p->edad }} años</span>
                        @endif
                        @if ($p->telefono)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $p->telefono) }}" class="hover:text-blue-700">{{ $p->telefono }}</a>
                        @endif
                        @if ($p->identidad_reservada)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700" title="AM 5216-A Art. 13: en documentos y pantallas compartidas se muestra la cédula en lugar del nombre">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                Identidad reservada
                            </span>
                        @endif
                        @php $pasiva = $p->archivoPasivo($ultimaAtencion); @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $pasiva ? 'bg-gray-100 text-gray-600' : 'bg-emerald-50 text-emerald-700' }}"
                              title="Última atención: {{ $ultimaAtencion?->format('d/m/Y') ?? '—' }}. Pasa al archivo pasivo tras {{ \App\Models\Paciente::ANIOS_ARCHIVO_ACTIVO }} años sin atención (AM 5216-A Art. 17)">
                            HC {{ $pasiva ? 'pasiva' : 'activa' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('pacientes.editar')
                    <a href="{{ route('pacientes', ['editar' => $p->id]) }}" wire:navigate class="{{ $boton }}">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                        Editar
                    </a>
                @endcan
                @can('expediente.crear')
                    <a href="{{ route('pedidos.crear', $p) }}" wire:navigate class="{{ $boton }}">Pedido de exámenes</a>
                    <a href="{{ route('certificados.crear', $p) }}" wire:navigate class="{{ $boton }}">Certificado</a>
                    <a href="{{ route('consentimientos.crear', $p) }}" wire:navigate class="{{ $boton }}" title="Consentimiento informado (formulario 024)">Consentimiento</a>
                @endcan
                @if ($clinico)
                    <a href="{{ route('pacientes.pdf', $p) }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Historia clínica
                    </a>
                @endif
            </div>
        </div>

        {{-- Avisos importantes --}}
        @if (($clinico && filled($p->alergias)) || $turnoActivo || $proximaCita || $embarazo)
            <div class="flex flex-wrap gap-2 mt-5 pt-5 border-t border-gray-100">
                @if ($clinico && filled($p->alergias))
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-50 text-red-700 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        <span><span class="font-medium">Alergias:</span> {{ $p->alergias }}</span>
                    </span>
                @endif
                @if ($turnoActivo)
                    @can('expediente.ver')
                        <a href="{{ route('turno.atender', $turnoActivo) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 text-sm hover:bg-emerald-100">
                            Turno {{ $turnoActivo->codigo }} hoy · {{ $turnoActivo->estado === 'en_atencion' ? 'en atención' : 'esperando' }} — Atender
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 text-sm">
                            Turno {{ $turnoActivo->codigo }} hoy · {{ $turnoActivo->estado === 'en_atencion' ? 'en atención' : 'esperando' }}
                        </span>
                    @endcan
                @endif
                @if ($proximaCita)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        Próxima cita: {{ $proximaCita->fecha_hora->translatedFormat('D d M · H:i') }}{{ $proximaCita->medico ? ' · ' . $proximaCita->medico->nombre_completo : '' }}
                    </span>
                @endif
                @if ($embarazo)
                    @php $eg = $embarazo->edadGestacional(); @endphp
                    <a href="{{ route('prenatal', $p) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-pink-50 text-pink-700 text-sm hover:bg-pink-100">
                        Embarazo en control · {{ $eg['semanas'] }} semanas {{ $eg['dias'] ? "+{$eg['dias']} días" : '' }}
                    </a>
                @endif
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

        {{-- Columna izquierda: datos --}}
        <div class="space-y-5">
            <div class="tarjeta p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Datos personales</h2>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div><dt class="text-xs text-gray-400">Nacimiento</dt><dd class="text-gray-800">{{ $p->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Ciudad</dt><dd class="text-gray-800">{{ $dato($p->ciudad) }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Teléfono alternativo</dt><dd class="text-gray-800">{{ $dato($p->telefono_secundario) }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Ocupación</dt><dd class="text-gray-800">{{ $dato($p->ocupacion) }}</dd></div>
                    <div class="col-span-2"><dt class="text-xs text-gray-400">Email</dt><dd class="text-gray-800 break-all">{{ $dato($p->email) }}</dd></div>
                    <div class="col-span-2"><dt class="text-xs text-gray-400">Dirección</dt><dd class="text-gray-800">{{ $dato($p->direccion) }}</dd></div>
                    <div class="col-span-2"><dt class="text-xs text-gray-400">Referido por</dt><dd class="text-gray-800">{{ $dato($p->referido_por) }}</dd></div>
                </dl>
            </div>

            @if ($clinico)
                <div class="tarjeta p-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Antecedentes</h2>
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        @foreach ([['Grupo', $p->grupo_sanguineo], ['Peso', $p->peso ? rtrim(rtrim((string) $p->peso, '0'), '.') . ' kg' : null], ['Talla', $p->altura ? $p->altura . ' m' : null]] as [$t, $v])
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5 text-center">
                                <p class="font-semibold text-gray-900">{{ $dato($v) }}</p>
                                <p class="text-[11px] text-gray-400">{{ $t }}</p>
                            </div>
                        @endforeach
                    </div>
                    <dl class="space-y-3 text-sm">
                        @foreach ([['Medicamentos actuales', $p->medicamentos], ['Cirugías previas', $p->cirugias], ['Personales', $p->antecedentes], ['Familiares', $p->enfermedades_familiares]] as [$t, $v])
                            <div><dt class="text-xs text-gray-400">{{ $t }}</dt><dd class="text-gray-800 whitespace-pre-line">{{ $dato($v) }}</dd></div>
                        @endforeach
                    </dl>
                </div>

                @if ($p->sexo === 'femenino')
                    <div class="tarjeta p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-semibold text-gray-900">Gineco-obstétrico</h2>
                            <a href="{{ route('prenatal', $p) }}" wire:navigate class="text-xs font-medium text-blue-700 hover:underline">Control prenatal</a>
                        </div>
                        <div class="grid grid-cols-4 gap-2 mb-3 text-center">
                            @foreach ([['G', $p->gestas], ['P', $p->partos], ['C', $p->cesareas], ['A', $p->abortos]] as [$t, $v])
                                <div class="rounded-xl bg-gray-50 py-2"><p class="font-semibold text-gray-900">{{ $v ?? '—' }}</p><p class="text-[11px] text-gray-400">{{ $t }}</p></div>
                            @endforeach
                        </div>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-xs text-gray-400">FUM</dt><dd class="text-gray-800">{{ $p->fum?->format('d/m/Y') ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Anticonceptivo</dt><dd class="text-gray-800">{{ $dato($p->metodo_anticonceptivo) }}</dd></div>
                        </dl>
                    </div>
                @endif

                <a href="{{ route('pacientes.archivos', $p) }}" wire:navigate class="tarjeta flex items-center gap-3 p-4 hover:border-gray-300 transition">
                    <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-gray-100 text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
                    </span>
                    <span class="flex-1">
                        <span class="block text-sm font-medium text-gray-900">Archivos del paciente</span>
                        <span class="block text-xs text-gray-500">{{ $archivos }} {{ $archivos === 1 ? 'archivo' : 'archivos' }} · laboratorios, imágenes, referencias</span>
                    </span>
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            @endif
        </div>

        {{-- Columna derecha: historial --}}
        <div class="lg:col-span-2 tarjeta" x-data="{ tab: @js(array_key_first($pestanas)) }">
            @if ($pestanas)
                <div class="flex gap-1 px-3 pt-3 border-b border-gray-100 overflow-x-auto">
                    @foreach ($pestanas as $clave => [$texto, $n])
                        <button type="button" @click="tab = '{{ $clave }}'"
                                :class="tab === '{{ $clave }}' ? 'border-blue-600 text-blue-700 font-medium' : 'border-transparent text-gray-500 hover:text-gray-800'"
                                class="px-3 py-2.5 text-sm border-b-2 -mb-px whitespace-nowrap transition">
                            {{ $texto }} <span class="ml-1 text-xs text-gray-400">{{ $n }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            @if ($clinico)
                <div x-show="tab === 'consultas'" class="divide-y divide-gray-100">
                    @forelse ($consultas as $c)
                        <div class="flex gap-4 px-5 py-4">
                            <div class="w-16 shrink-0 text-xs text-gray-400 leading-relaxed pt-0.5">
                                <span class="block font-medium text-gray-700">{{ $c->fecha->format('d/m/Y') }}</span>{{ $c->fecha->format('H:i') !== '00:00' ? $c->fecha->format('H:i') : '' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">{{ $c->motivo_consulta }}</p>
                                @if ($c->diagnostico)
                                    <p class="text-sm text-gray-600 mt-0.5">{{ $c->diagnostico }}</p>
                                @endif
                                <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs text-gray-400">
                                    @if ($c->tipo_atencion)
                                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">{{ \App\Models\ExpedienteConsulta::TIPOS_ATENCION[$c->tipo_atencion] ?? $c->tipo_atencion }}</span>
                                    @endif
                                    @if ($c->tipo_diagnostico)
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Dx {{ mb_strtolower(\App\Models\ExpedienteConsulta::TIPOS_DIAGNOSTICO[$c->tipo_diagnostico] ?? $c->tipo_diagnostico) }}</span>
                                    @endif
                                    @if ($c->codigo_cie10)
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-mono">{{ $c->codigo_cie10 }}</span>
                                    @endif
                                    <span>{{ $c->medico?->nombre_completo ?? '—' }}{{ $c->especialidad ? ' · ' . $c->especialidad->nombre : '' }}</span>
                                </div>
                                @if ($c->tratamiento)
                                    <details class="mt-2 text-sm">
                                        <summary class="cursor-pointer text-xs font-medium text-blue-700">Ver tratamiento e indicaciones</summary>
                                        @if ($c->revision_sistemas)
                                            <p class="mt-2 text-gray-500"><span class="font-medium text-gray-600">Revisión de sistemas:</span> {{ $c->revision_sistemas }}</p>
                                        @endif
                                        <p class="mt-2 text-gray-700 whitespace-pre-line">{{ $c->tratamiento }}</p>
                                        @if ($c->indicaciones)
                                            <p class="mt-2 text-gray-500 whitespace-pre-line">{{ $c->indicaciones }}</p>
                                        @endif
                                    </details>
                                @endif
                            </div>
                            @can('update', $c)
                                <a href="{{ route('consultas.editar', $c->id) }}" wire:navigate class="self-start shrink-0 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition">Editar</a>
                            @endcan
                        </div>
                    @empty
                        <p class="px-5 py-12 text-center text-sm text-gray-400">Todavía no hay consultas registradas.</p>
                    @endforelse
                </div>

                <div x-show="tab === 'documentos'" x-cloak class="divide-y divide-gray-100">
                    @forelse ($documentos as $d)
                        <div class="flex items-center gap-4 px-5 py-3.5">
                            <span class="w-20 shrink-0 text-xs text-gray-500">{{ $d['fecha']?->format('d/m/Y') ?? '—' }}</span>
                            <div class="flex-1 min-w-0">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $d['tono'] }}">{{ $d['tipo'] }}</span>
                                <p class="text-sm text-gray-700 mt-1 truncate">{{ $d['detalle'] ?: '—' }}</p>
                                @if ($d['medico'])
                                    <p class="text-xs text-gray-400">{{ $d['medico'] }}</p>
                                @endif
                            </div>
                            @isset($d['ver'])
                                <a href="{{ $d['ver'] }}" wire:navigate class="shrink-0 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition">Abrir</a>
                            @endisset
                            <a href="{{ $d['pdf'] }}" class="shrink-0 px-3 py-1.5 text-xs font-medium text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition">PDF</a>
                        </div>
                    @empty
                        <p class="px-5 py-12 text-center text-sm text-gray-400">Sin recetas, certificados, pedidos, ecografías ni consentimientos.</p>
                    @endforelse
                </div>
            @endif

            @can('turnos.ver')
                <div x-show="tab === 'citas'" x-cloak class="divide-y divide-gray-100">
                    @forelse ($citas as $cita)
                        @php [$txt, $tono] = $estadosCita[$cita->estado] ?? [ucfirst($cita->estado), 'bg-gray-100 text-gray-600']; @endphp
                        <div class="flex items-center gap-4 px-5 py-3.5 text-sm">
                            <span class="w-28 shrink-0 text-gray-700">{{ $cita->fecha_hora->format('d/m/Y H:i') }}</span>
                            <span class="flex-1 min-w-0 truncate text-gray-600">{{ $cita->tipo ?? 'Cita' }}{{ $cita->medico ? ' · ' . $cita->medico->nombre_completo : '' }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tono }}">{{ $txt }}</span>
                        </div>
                    @empty
                        <p class="px-5 py-12 text-center text-sm text-gray-400">Sin citas agendadas.</p>
                    @endforelse
                </div>
            @endcan

            @can('facturas.ver')
                <div x-show="tab === 'facturas'" x-cloak class="divide-y divide-gray-100">
                    @forelse ($facturas as $f)
                        <a href="{{ route('facturas.ver', $f) }}" wire:navigate class="flex items-center gap-4 px-5 py-3.5 text-sm hover:bg-gray-50/70 transition">
                            <span class="w-24 shrink-0 text-gray-500">{{ $f->fecha?->format('d/m/Y') }}</span>
                            <span class="flex-1 font-mono text-[13px] {{ $f->estado === 'anulada' ? 'line-through text-gray-400' : 'text-gray-900' }}">{{ $f->numero_factura }}</span>
                            @if ($f->estado === 'anulada')
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">Anulada</span>
                            @endif
                            <span class="w-20 text-right font-medium text-gray-900 tabular-nums">${{ number_format((float) $f->total, 2) }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-12 text-center text-sm text-gray-400">Sin facturas.</p>
                    @endforelse
                </div>
            @endcan

            @unless ($pestanas)
                <p class="px-5 py-12 text-center text-sm text-gray-400">No tiene permisos para ver el historial de este paciente.</p>
            @endunless
        </div>
    </div>
</div>
</div>
