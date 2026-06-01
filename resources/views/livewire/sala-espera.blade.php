<div class="sw-page">

    {{-- ===== ESTILOS INTEGRADOS ===== --}}
    <style>
        .sw-page {
            background: #f1f5f9;
            min-height: 100vh;
            padding: 1.5rem;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
        }

        /* Header */
        .sw-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        .sw-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sw-header p {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 2px;
        }
        .sw-tabs {
            display: flex;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 3px;
            gap: 2px;
        }
        .sw-tab {
            padding: 6px 16px;
            font-size: 0.8125rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            background: transparent;
            color: #64748b;
            font-weight: 400;
            transition: all 0.15s;
        }
        .sw-tab-active {
            background: #1e40af;
            color: #fff;
            font-weight: 500;
        }
        .sw-date-input {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 0.8125rem;
            color: #374151;
            background: #fff;
        }

        /* Stats row */
        .sw-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 1.25rem;
        }
        .sw-stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .sw-stat-label {
            font-size: 0.6875rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .sw-stat-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .sw-stat-number {
            font-size: 1.375rem;
            font-weight: 600;
            color: #0f172a;
        }

        /* Grid de colas */
        .sw-colas-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        /* Cola card */
        .sw-cola-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .sw-color-bar {
            height: 3px;
            width: 100%;
        }
        .sw-cola-header {
            padding: 13px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
        }
        .sw-cola-title {
            font-size: 0.875rem;
            font-weight: 600;
        }
        .sw-cola-badge {
            font-size: 0.6875rem;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        /* Bloque en atención */
        .sw-en-atencion {
            margin: 12px;
            border-radius: 10px;
            padding: 12px 14px;
            border: 1px solid;
        }
        .sw-en-atencion-vacio {
            background: #f8fafc;
            border-color: #e2e8f0;
            text-align: center;
            padding: 20px;
        }
        .sw-ea-label {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .sw-pulse {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            animation: sw-pulse 1.5s ease-in-out infinite;
            flex-shrink: 0;
        }
        @keyframes sw-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.35; }
        }
        .sw-ea-codigo {
            font-size: 1.125rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .sw-ea-badge {
            font-size: 0.6875rem;
            padding: 2px 9px;
            border-radius: 20px;
            font-weight: 500;
        }
        .sw-ea-nombre {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1e293b;
        }
        .sw-ea-meta {
            font-size: 0.6875rem;
            color: #64748b;
            margin-top: 3px;
        }
        .sw-ea-actions {
            display: flex;
            gap: 6px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        /* Botones */
        .sw-btn {
            border: none;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 5px 12px;
            border-radius: 7px;
            transition: opacity 0.15s, transform 0.1s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        .sw-btn:hover { opacity: 0.85; }
        .sw-btn:active { transform: scale(0.97); }
        .sw-btn-purple { background: #6d28d9; color: #fff; }
        .sw-btn-green  { background: #065f46; color: #fff; }
        .sw-btn-blue   { background: #1e40af; color: #fff; }
        .sw-btn-ghost  { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .sw-btn-red    { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        /* Sección espera */
        .sw-espera-section { padding: 0 12px 12px; }
        .sw-espera-title {
            font-size: 0.6875rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 10px 0 8px;
        }

        /* Fila paciente */
        .sw-paciente-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
            margin-bottom: 5px;
            background: #fff;
            transition: background 0.1s, border-color 0.1s;
        }
        .sw-paciente-row:hover {
            background: #f8fafc;
            border-color: #e2e8f0;
        }
        .sw-p-num {
            font-size: 0.8125rem;
            font-weight: 700;
            min-width: 40px;
        }
        .sw-p-info { flex: 1; min-width: 0; }
        .sw-p-nombre {
            font-size: 0.8125rem;
            font-weight: 500;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sw-p-meta {
            font-size: 0.6875rem;
            color: #94a3b8;
            margin-top: 1px;
        }
        .sw-tiempo {
            font-size: 0.6875rem;
            color: #94a3b8;
            min-width: 32px;
            text-align: right;
            white-space: nowrap;
        }
        .sw-p-actions { display: flex; gap: 4px; }

        /* Empty */
        .sw-empty {
            font-size: 0.8125rem;
            color: #94a3b8;
            text-align: center;
            padding: 18px 0;
        }

        /* Historial tabla */
        .sw-historial-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .sw-historial-header {
            padding: 13px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .sw-historial-title {
            font-size: 0.875rem;
            font-weight: 600;
        }
        .sw-table { width: 100%; font-size: 0.8125rem; border-collapse: collapse; }
        .sw-table thead th {
            text-align: left;
            padding: 9px 14px;
            font-size: 0.6875rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
        }
        .sw-table tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.1s; }
        .sw-table tbody tr:last-child { border-bottom: none; }
        .sw-table tbody tr:hover { background: #f8fafc; }
        .sw-table td { padding: 10px 14px; color: #374151; vertical-align: middle; }
        .sw-table td.sw-td-bold { font-weight: 700; color: #0f172a; }
        .sw-table td.sw-td-muted { color: #94a3b8; }

        /* Badges de estado */
        .sw-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.6875rem;
            font-weight: 500;
            white-space: nowrap;
        }
        .sw-badge-blue    { background: #eff6ff; color: #1d4ed8; }
        .sw-badge-amber   { background: #fffbeb; color: #92400e; }
        .sw-badge-green   { background: #f0fdf4; color: #14532d; }
        .sw-badge-red     { background: #fef2f2; color: #991b1b; }

        /* Modal */
        .sw-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.45);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            backdrop-filter: blur(2px);
        }
        .sw-modal {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 480px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .sw-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 20px 20px 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        .sw-modal-title { font-size: 1rem; font-weight: 600; color: #0f172a; }
        .sw-modal-sub   { font-size: 0.75rem; color: #64748b; margin-top: 2px; }
        .sw-modal-close {
            background: #f1f5f9; border: none; border-radius: 6px;
            width: 28px; height: 28px; cursor: pointer; font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
            color: #64748b; transition: background 0.15s;
        }
        .sw-modal-close:hover { background: #e2e8f0; }
        .sw-modal-body { padding: 16px 20px; }
        .sw-modal-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 16px 0 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #f1f5f9;
        }
        .sw-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .sw-modal-field label { font-size: 0.6875rem; color: #94a3b8; margin-bottom: 2px; display: block; }
        .sw-modal-field p { font-size: 0.8125rem; font-weight: 500; color: #1e293b; }
        .sw-modal-actions {
            display: flex;
            gap: 8px;
            padding: 14px 20px 20px;
            border-top: 1px solid #f1f5f9;
            flex-wrap: wrap;
        }
        .sw-modal-actions .sw-btn { flex: 1; justify-content: center; padding: 8px 14px; font-size: 0.8125rem; }
    </style>

    {{-- ===== HEADER ===== --}}
    <div class="sw-header">
        <div>
            <h1>
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="color:#1e40af">
                    <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="2"/>
                    <path d="M9 12h6M9 16h4"/>
                </svg>
                Sala de espera
            </h1>
            <p>{{ now()->isoFormat('dddd D [de] MMMM [de] YYYY') }} &nbsp;·&nbsp; Actualiza cada 10s</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            @if ($vista === 'historial')
                <input type="date" wire:model.live="fecha" class="sw-date-input">
            @endif
            <div class="sw-tabs">
                <button wire:click="$set('vista', 'activos')"
                    class="sw-tab {{ $vista === 'activos' ? 'sw-tab-active' : '' }}">
                    Activos
                </button>
                @can('expediente.ver')
                    <button wire:click="$set('vista', 'historial')"
                        class="sw-tab {{ $vista === 'historial' ? 'sw-tab-active' : '' }}">
                        Historial
                    </button>
                @endcan
            </div>
        </div>
    </div>

    @if ($vista === 'activos')

        {{-- ===== STATS ROW ===== --}}
        @php
            $totalEsperando  = collect($colas)->sum(fn($c) => $c['esperando']->count());
            $totalAtencion   = collect($colas)->filter(fn($c) => $c['en_atencion'])->count();
        @endphp
        <div class="sw-stats">
            <div class="sw-stat">
                <div class="sw-stat-label">
                    <span class="sw-stat-dot" style="background:#f59e0b"></span> En atención
                </div>
                <div class="sw-stat-number">{{ $totalAtencion }}</div>
            </div>
            <div class="sw-stat">
                <div class="sw-stat-label">
                    <span class="sw-stat-dot" style="background:#1e40af"></span> En espera
                </div>
                <div class="sw-stat-number">{{ $totalEsperando }}</div>
            </div>
            <div class="sw-stat">
                <div class="sw-stat-label">
                    <span class="sw-stat-dot" style="background:#065f46"></span> Completados hoy
                </div>
                <div class="sw-stat-number">
                    {{ \App\Models\Turno::whereDate('created_at', today())->where('estado', 'completado')->count() }}
                </div>
            </div>
            <div class="sw-stat">
                <div class="sw-stat-label">
                    <span class="sw-stat-dot" style="background:#b91c1c"></span> Cancelados hoy
                </div>
                <div class="sw-stat-number">
                    {{ \App\Models\Turno::whereDate('created_at', today())->where('estado', 'cancelado')->count() }}
                </div>
            </div>
        </div>

        {{-- ===== VISTA ACTIVOS ===== --}}
        <div class="sw-colas-grid">
            @foreach ($colas as $cola)
                @php $color = $cola['especialidad']->color_agenda; @endphp
                <div class="sw-cola-card">

                    {{-- Barra de color delgada --}}
                    <div class="sw-color-bar" style="background:{{ $color }}"></div>

                    {{-- Header cola --}}
                    <div class="sw-cola-header">
                        <div class="sw-cola-title" style="color:{{ $color }}">
                            {{ $cola['especialidad']->nombre }}
                        </div>
                        <span class="sw-cola-badge"
                            style="background:{{ $color }}18; color:{{ $color }};">
                            {{ $cola['esperando']->count() }} en espera
                        </span>
                    </div>

                    {{-- Bloque en atención --}}
                    @if ($cola['en_atencion'])
                        <div class="sw-en-atencion"
                            style="background:{{ $color }}0f; border-color:{{ $color }}30; margin:12px;">
                            <div class="sw-ea-label" style="color:{{ $color }}cc;">
                                <span class="sw-pulse" style="background:{{ $color }}"></span>
                                En atención ahora
                            </div>
                            <div class="sw-ea-codigo" style="color:{{ $color }};">
                                #{{ $cola['en_atencion']->codigo }}
                                <span class="sw-ea-badge"
                                    style="background:{{ $color }}20; color:{{ $color }};">
                                    Llamado
                                </span>
                            </div>
                            <div class="sw-ea-nombre">
                                {{ $cola['en_atencion']->paciente?->nombre_completo ?? $cola['en_atencion']->nombre_temporal }}
                            </div>
                            <div class="sw-ea-meta">
                                C.I. {{ $cola['en_atencion']->cedula }}
                                &nbsp;·&nbsp; {{ $cola['en_atencion']->motivo }}
                                &nbsp;·&nbsp; {{ $cola['en_atencion']->hora_registro->format('H:i') }}
                                &nbsp;·&nbsp; Tel: {{ $cola['en_atencion']->paciente?->telefono ?? $cola['en_atencion']->telefono }}
                            </div>
                            <div class="sw-ea-actions">
                                @can('expediente.crear')
                                    <a href="{{ route('turno.atender', $cola['en_atencion']->id) }}" wire:navigate
                                        class="sw-btn sw-btn-purple">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        Atender
                                    </a>
                                @endcan
                                @can('turnos.gestionar')
                                    <button wire:click="completar({{ $cola['en_atencion']->id }})"
                                        class="sw-btn sw-btn-green">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                        Completar
                                    </button>
                                @endcan
                            </div>
                        </div>
                    @else
                        <div class="sw-en-atencion sw-en-atencion-vacio">
                            <p class="sw-empty" style="padding:0">Sin paciente en atención</p>
                        </div>
                    @endif

                    {{-- Lista en espera --}}
                    <div class="sw-espera-section">
                        @if ($cola['esperando']->count() > 0)
                            <div class="sw-espera-title">En espera ({{ $cola['esperando']->count() }})</div>
                            <div style="max-height:22rem;overflow-y:auto;">
                                @foreach ($cola['esperando'] as $turno)
                                    <div class="sw-paciente-row">
                                        <div class="sw-p-num" style="color:{{ $color }}">
                                            #{{ $turno->codigo }}
                                        </div>
                                        <div class="sw-p-info">
                                            <div class="sw-p-nombre">
                                                {{ $turno->paciente?->nombre_completo ?? $turno->nombre_temporal }}
                                            </div>
                                            <div class="sw-p-meta">
                                                {{ $turno->cedula }} &nbsp;·&nbsp; {{ $turno->motivo }}
                                            </div>
                                        </div>
                                        <div class="sw-tiempo">
                                            {{ $turno->hora_registro->diffForHumans(null, true, true, 1) }}
                                        </div>
                                        <div class="sw-p-actions">
                                            <button wire:click="verDetalle({{ $turno->id }})"
                                                title="Ver detalle"
                                                style="background:none;border:none;cursor:pointer;padding:4px 6px;border-radius:6px;color:#94a3b8;font-size:0.75rem;transition:color 0.1s;"
                                                onmouseover="this.style.color='#1e40af'"
                                                onmouseout="this.style.color='#94a3b8'">
                                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </button>
                                            @can('turnos.gestionar')
                                                <button wire:click="llamar({{ $turno->id }})"
                                                    class="sw-btn sw-btn-blue"
                                                    style="background:{{ $color }};">
                                                    Llamar
                                                </button>
                                                <button wire:click="cancelar({{ $turno->id }})"
                                                    class="sw-btn sw-btn-ghost"
                                                    title="Cancelar turno">
                                                    ✕
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="sw-empty">No hay pacientes en espera</p>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>

    @else

        {{-- ===== VISTA HISTORIAL ===== --}}
        @can('expediente.ver')
            <div style="display:flex;flex-direction:column;gap:1rem;">
                @foreach ($historial as $h)
                    @php $color = $h['especialidad']->color_agenda; @endphp
                    <div class="sw-historial-card">
                        <div class="sw-historial-header" style="border-left:3px solid {{ $color }}; padding-left:13px;">
                            <div class="sw-historial-title" style="color:{{ $color }}">
                                {{ $h['especialidad']->nombre }}
                                <span style="font-size:0.8125rem;font-weight:400;color:#64748b;">
                                    — {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                                </span>
                            </div>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="sw-table">
                                <thead>
                                    <tr>
                                        <th>Turno</th>
                                        <th>Paciente</th>
                                        <th>Cédula</th>
                                        <th>Motivo</th>
                                        <th>Teléfono</th>
                                        <th>Hora</th>
                                        <th>Estado</th>
                                        <th style="text-align:center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($h['turnos'] as $turno)
                                        <tr>
                                            <td class="sw-td-bold">#{{ $turno->codigo }}</td>
                                            <td>{{ $turno->paciente?->nombre_completo ?? $turno->nombre_temporal }}</td>
                                            <td class="sw-td-muted">{{ $turno->cedula }}</td>
                                            <td class="sw-td-muted">{{ $turno->motivo }}</td>
                                            <td class="sw-td-muted">{{ $turno->paciente?->telefono ?? $turno->telefono }}</td>
                                            <td class="sw-td-muted">{{ $turno->hora_registro->format('H:i') }}</td>
                                            <td>
                                                @switch($turno->estado)
                                                    @case('esperando')
                                                        <span class="sw-badge sw-badge-blue">
                                                            <span style="width:5px;height:5px;border-radius:50%;background:#1d4ed8;display:inline-block;"></span>
                                                            Esperando
                                                        </span>
                                                        @break
                                                    @case('en_atencion')
                                                        <span class="sw-badge sw-badge-amber">
                                                            <span style="width:5px;height:5px;border-radius:50%;background:#d97706;display:inline-block;"></span>
                                                            En atención
                                                        </span>
                                                        @break
                                                    @case('completado')
                                                        <span class="sw-badge sw-badge-green">
                                                            <span style="width:5px;height:5px;border-radius:50%;background:#15803d;display:inline-block;"></span>
                                                            Completado
                                                        </span>
                                                        @break
                                                    @case('cancelado')
                                                        <span class="sw-badge sw-badge-red">
                                                            <span style="width:5px;height:5px;border-radius:50%;background:#dc2626;display:inline-block;"></span>
                                                            Cancelado
                                                        </span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td style="text-align:center">
                                                <button wire:click="verDetalle({{ $turno->id }})"
                                                    style="background:none;border:none;cursor:pointer;font-size:0.75rem;font-weight:500;color:#1e40af;padding:4px 8px;border-radius:6px;transition:background 0.1s;"
                                                    onmouseover="this.style.background='#eff6ff'"
                                                    onmouseout="this.style.background='none'">
                                                    Ver ficha
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endcan

    @endif

    {{-- ===== MODAL DETALLE ===== --}}
    @if ($detalle)
        <div class="sw-modal-overlay" wire:click.self="cerrarDetalle">
            <div class="sw-modal">

                <div class="sw-modal-header">
                    <div>
                        <div class="sw-modal-title">Turno #{{ $detalle->codigo }}</div>
                        <div class="sw-modal-sub">{{ $detalle->especialidad->nombre }}</div>
                    </div>
                    <button wire:click="cerrarDetalle" class="sw-modal-close" aria-label="Cerrar">✕</button>
                </div>

                <div class="sw-modal-body">

                    <div class="sw-modal-section-title">Información del turno</div>
                    <div class="sw-modal-grid">
                        <div class="sw-modal-field">
                            <label>Paciente</label>
                            <p>{{ $detalle->paciente?->nombre_completo ?? $detalle->nombre_temporal }}</p>
                        </div>
                        <div class="sw-modal-field">
                            <label>Cédula</label>
                            <p>{{ $detalle->cedula }}</p>
                        </div>
                        <div class="sw-modal-field">
                            <label>Teléfono</label>
                            <p>{{ $detalle->paciente?->telefono ?? $detalle->telefono }}</p>
                        </div>
                        <div class="sw-modal-field">
                            <label>Motivo</label>
                            <p>{{ $detalle->motivo }}</p>
                        </div>
                        <div class="sw-modal-field">
                            <label>Hora de registro</label>
                            <p>{{ $detalle->hora_registro->format('H:i') }}</p>
                        </div>
                        <div class="sw-modal-field">
                            <label>Estado</label>
                            <p>
                                @switch($detalle->estado)
                                    @case('esperando')
                                        <span class="sw-badge sw-badge-blue">Esperando</span> @break
                                    @case('en_atencion')
                                        <span class="sw-badge sw-badge-amber">En atención</span> @break
                                    @case('completado')
                                        <span class="sw-badge sw-badge-green">Completado</span> @break
                                    @case('cancelado')
                                        <span class="sw-badge sw-badge-red">Cancelado</span> @break
                                @endswitch
                            </p>
                        </div>
                    </div>

                    @if ($detalle->paciente)
                        <div class="sw-modal-section-title">Datos clínicos</div>
                        <div class="sw-modal-grid">
                            <div class="sw-modal-field">
                                <label>Edad</label>
                                <p>{{ $detalle->paciente->edad ?? '--' }} años</p>
                            </div>
                            <div class="sw-modal-field">
                                <label>Sexo</label>
                                <p style="text-transform:capitalize">{{ $detalle->paciente->sexo }}</p>
                            </div>
                            <div class="sw-modal-field">
                                <label>Peso</label>
                                <p>{{ $detalle->paciente->peso ? $detalle->paciente->peso . ' kg' : '--' }}</p>
                            </div>
                            <div class="sw-modal-field">
                                <label>Altura</label>
                                <p>{{ $detalle->paciente->altura ? $detalle->paciente->altura . ' m' : '--' }}</p>
                            </div>
                            <div class="sw-modal-field" style="grid-column:span 2">
                                <label>Dirección</label>
                                <p>{{ $detalle->paciente->direccion ?? '--' }}</p>
                            </div>
                            <div class="sw-modal-field">
                                <label>Email</label>
                                <p>{{ $detalle->paciente->email ?? '--' }}</p>
                            </div>
                            <div class="sw-modal-field">
                                <label>Alergias</label>
                                <p>{{ $detalle->paciente->alergias ?? '--' }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="sw-modal-actions">
                    @if ($detalle->estado === 'esperando')
                        @can('turnos.gestionar')
                            <button wire:click="llamar({{ $detalle->id }})" wire:click="cerrarDetalle"
                                class="sw-btn sw-btn-blue">
                                Llamar paciente
                            </button>
                            <button wire:click="cancelar({{ $detalle->id }})" wire:click="cerrarDetalle"
                                class="sw-btn sw-btn-red">
                                Cancelar turno
                            </button>
                        @endcan
                    @endif
                    @if ($detalle->estado === 'en_atencion')
                        @can('turnos.gestionar')
                            <button wire:click="completar({{ $detalle->id }})" wire:click="cerrarDetalle"
                                class="sw-btn sw-btn-green">
                                Marcar completado
                            </button>
                        @endcan
                    @endif
                    @can('expediente.crear')
                        <a href="{{ route('turno.atender', $detalle->id) }}" wire:navigate
                            class="sw-btn sw-btn-purple">
                            Atender (médico)
                        </a>
                    @endcan
                </div>

            </div>
        </div>
    @endif

    {{-- Poll automático cada 10s --}}
    <div wire:poll.10s></div>

</div>