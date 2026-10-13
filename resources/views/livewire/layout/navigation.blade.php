<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }

    /** Secciones del menú: [título, [[texto, ruta, rutas activas, permiso, ícono], …]] */
    public function with(): array
    {
        $esAdmin = auth()->user()->can('configuracion.editar');

        return ['secciones' => [
            ['', [
                ['Inicio', $esAdmin ? 'admin.dashboard' : 'dashboard', $esAdmin ? 'admin.dashboard' : 'dashboard', null, 'inicio'],
            ]],
            ['Atención', [
                ['Agenda', 'agenda', 'agenda*', 'turnos.ver', 'agenda'],
                ['Registrar turno', 'turnos.crear', 'turnos.crear*', 'turnos.ver', 'turno'],
                ['Sala de espera', 'sala-espera', 'sala-espera*|turno.atender', 'turnos.ver', 'sala'],
            ]],
            ['Pacientes', [
                ['Pacientes', 'pacientes', 'pacientes|pacientes.ver|pacientes.archivos|prenatal|certificados.*|pedidos.*', 'pacientes.ver', 'pacientes'],
                ['Expedientes', 'expedientes', 'expedientes*|consultas.*', 'expediente.ver', 'expedientes'],
            ]],
            ['Facturación', [
                ['Facturas', 'facturas', 'facturas*', 'facturas.ver', 'facturas'],
                ['Ventas', 'reportes.ventas', 'reportes.ventas', 'reportes.ver', 'reportes'],
            ]],
            ['Estadística', [
                ['Morbilidad', 'reportes.morbilidad', 'reportes.morbilidad', 'reportes.ver|auditoria.ver', 'morbilidad'],
            ]],
            ['Auditoría', [
                ['Registro de accesos', 'auditoria', 'auditoria', 'auditoria.ver', 'auditoria'],
                ['Calidad de HC', 'auditoria.calidad', 'auditoria.calidad|auditoria.evaluar', 'auditoria.ver', 'calidad'],
                ['Planes de acción', 'auditoria.planes', 'auditoria.planes*', 'auditoria.ver', 'planes'],
                ['Solicitudes', 'auditoria.solicitudes', 'auditoria.solicitudes*', 'auditoria.ver', 'solicitudes'],
                ['Incidentes', 'auditoria.incidentes', 'auditoria.incidentes*', 'auditoria.ver', 'incidentes'],
            ]],
            ['Administración', [
                ['Usuarios', 'usuarios', 'usuarios*', 'usuarios.ver', 'usuarios'],
                ['Roles y permisos', 'roles', 'roles*', 'configuracion.editar', 'roles'],
                ['Especialidades', 'especialidades', 'especialidades*', 'configuracion.editar', 'especialidades'],
                ['Tarifario', 'tarifario', 'tarifario*', 'configuracion.editar', 'tarifario'],
                ['SRI', 'sri.config', 'sri.*', 'configuracion.editar', 'sri'],
                ['Configuración', 'configuracion', 'configuracion*', 'configuracion.editar', 'configuracion'],
            ]],
        ]];
    }
}; ?>

@php
    // Íconos (Heroicons outline, 24px)
    $iconos = [
        'inicio' => 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'agenda' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'turno' => 'M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'sala' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'pacientes' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'expedientes' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
        'facturas' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
        'morbilidad' => 'M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z',
        'reportes' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        'usuarios' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z',
        'roles' => 'M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z',
        'especialidades' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z',
        'tarifario' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'sri' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        'configuracion' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'calidad' => 'M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75',
        'planes' => 'M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5',
        'auditoria' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
        'solicitudes' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108M6.75 21h7.5a2.25 2.25 0 002.25-2.25V9.375c0-.621-.504-1.125-1.125-1.125H4.875c-.621 0-1.125.504-1.125 1.125v9.375A2.25 2.25 0 006 21h.75z',
        'incidentes' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        'portada' =>'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418',
        'kiosco' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'perfil' => 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z',
        'salir' => 'M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9',
    ];
    $icono = fn (string $n) => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="' . $iconos[$n] . '"/></svg>';
    $user = auth()->user();
@endphp

@php
    $iniciales = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $c = $marca->color();
@endphp

<div style="--m: {{ $c }}; --m-suave: color-mix(in srgb, {{ $c }} 10%, white); --m-medio: color-mix(in srgb, {{ $c }} 22%, white);">
    <style>
        .sb-nav::-webkit-scrollbar { width: 6px; }
        .sb-nav::-webkit-scrollbar-thumb { background: #e5e3de; border-radius: 9999px; }
        .sb-nav { scrollbar-width: thin; scrollbar-color: #e5e3de transparent; }
        .sb-item { position: relative; }
        .sb-item.activo { background: var(--m-suave); color: var(--m); }
        .sb-item.activo::before { content: ''; position: absolute; left: -8px; top: 8px; bottom: 8px; width: 3px; border-radius: 0 3px 3px 0; background: var(--m); }
        .sb-tip { position: absolute; left: calc(100% + 12px); top: 50%; transform: translateY(-50%); white-space: nowrap; pointer-events: none;
                  background: #1f1f1d; color: #fff; font-size: 12px; padding: 4px 10px; border-radius: 8px; opacity: 0; transition: opacity .15s; z-index: 60; }
        .sb-item:hover .sb-tip { opacity: 1; }
    </style>

    {{-- Barra superior en celular --}}
    <div class="lg:hidden sticky top-0 z-30 flex items-center gap-3 h-14 px-4 bg-white/95 backdrop-blur border-b">
        <button type="button" @click="menuAbierto = true" class="p-2 -ml-2 rounded-lg text-gray-600 hover:bg-gray-100" aria-label="Abrir menú">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <x-application-logo class="h-8 w-auto max-w-[90px] object-contain" />
        <span class="font-semibold truncate" style="color: var(--m)">{{ $marca->nombre() }}</span>
        <button type="button" @click="$dispatch('abrir-busqueda')" class="ml-auto p-2 -mr-2 rounded-lg text-gray-500 hover:bg-gray-100" aria-label="Buscar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        </button>
    </div>

    {{-- Fondo oscuro del menú en celular --}}
    <div x-show="menuAbierto" x-transition.opacity @click="menuAbierto = false" class="lg:hidden fixed inset-0 z-40 bg-gray-900/40 backdrop-blur-[1px]" style="display: none"></div>

    {{-- Barra lateral --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white border-r border-gray-200/80 transition-all duration-200 w-64 -translate-x-full lg:translate-x-0"
           :class="{ 'translate-x-0 shadow-2xl': menuAbierto, 'lg:w-[72px]': colapsado, 'lg:w-64': !colapsado }"
           @keydown.escape.window="menuAbierto = false">

        {{-- Marca --}}
        <div class="flex items-center gap-3 h-[72px] px-4 shrink-0">
            <a href="{{ $user->can('configuracion.editar') ? route('admin.dashboard') : route('dashboard') }}" wire:navigate class="flex items-center gap-3 min-w-0">
                <span class="flex items-center justify-center w-10 h-10 rounded-xl shrink-0 ring-1 ring-gray-200 bg-white overflow-hidden">
                    <x-application-logo class="w-8 h-8 object-contain" />
                </span>
                <span class="min-w-0" :class="colapsado && 'lg:hidden'">
                    <span class="block font-semibold text-gray-900 truncate leading-tight">{{ $marca->nombre() }}</span>
                    @if ($marca->subtitulo())
                        <span class="block text-[11px] text-gray-400 truncate">{{ $marca->subtitulo() }}</span>
                    @endif
                </span>
            </a>
            <button type="button" @click="menuAbierto = false" class="lg:hidden ml-auto p-1.5 rounded-lg text-gray-400 hover:bg-gray-100" aria-label="Cerrar menú">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Botón contraer (borde derecho) --}}
        <button type="button" @click="colapsado = !colapsado"
                class="hidden lg:flex absolute -right-3 top-[60px] w-6 h-6 items-center justify-center rounded-full bg-white border border-gray-200 text-gray-400 shadow-sm hover:text-gray-700 hover:border-gray-300 transition"
                :title="colapsado ? 'Expandir menú' : 'Contraer menú'" :aria-label="colapsado ? 'Expandir menú' : 'Contraer menú'">
            <svg class="w-3.5 h-3.5 transition-transform" :class="colapsado && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        </button>

        {{-- Buscador (Ctrl+K) --}}
        <div class="px-3 pb-3 shrink-0">
            <button type="button" @click="$dispatch('abrir-busqueda'); menuAbierto = false"
                    class="sb-item relative flex items-center gap-3 w-full px-3 py-2 rounded-xl border border-gray-200 bg-gray-50/70 text-sm text-gray-400 hover:text-gray-600 hover:border-gray-300 transition"
                    :class="colapsado && 'lg:justify-center lg:px-0'" aria-label="Buscar">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <span class="flex-1 text-left" :class="colapsado && 'lg:hidden'">Buscar…</span>
                <kbd class="hidden lg:inline text-[10px] border border-gray-200 rounded px-1 bg-white" :class="colapsado && 'lg:!hidden'">Ctrl K</kbd>
                <span class="sb-tip hidden" :class="colapsado && 'lg:block'">Buscar (Ctrl K)</span>
            </button>
        </div>

        <nav class="sb-nav flex-1 overflow-y-auto overflow-x-visible px-3 pb-4 space-y-5" :class="colapsado && 'lg:overflow-visible'">
            @foreach ($secciones as [$titulo, $items])
                @php $visibles = array_filter($items, fn ($i) => !$i[3] || $user->canAny(explode('|', $i[3]))); @endphp
                @if ($visibles)
                    <div class="space-y-0.5">
                        @if ($titulo)
                            <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400" :class="colapsado && 'lg:hidden'">{{ $titulo }}</p>
                            <div class="hidden mx-3 mb-2 border-t border-gray-100" :class="colapsado && 'lg:block'"></div>
                        @endif
                        @foreach ($visibles as [$texto, $ruta, $activas, $permiso, $ic])
                            @php $activo = request()->routeIs(...explode('|', $activas)); @endphp
                            <a href="{{ route($ruta) }}" wire:navigate @if ($activo) aria-current="page" @endif
                               class="sb-item group flex items-center gap-3 px-3 py-2 rounded-xl text-sm transition-colors {{ $activo ? 'activo font-semibold' : 'font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                               :class="colapsado && 'lg:justify-center lg:px-0'">
                                <span class="{{ $activo ? '' : 'text-gray-400 group-hover:text-gray-600' }}">{!! $icono($ic) !!}</span>
                                <span class="truncate" :class="colapsado && 'lg:hidden'">{{ $texto }}</span>
                                <span class="sb-tip hidden" :class="colapsado && 'lg:block'">{{ $texto }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach

            <div class="space-y-0.5">
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400" :class="colapsado && 'lg:hidden'">Público</p>
                <div class="hidden mx-3 mb-2 border-t border-gray-100" :class="colapsado && 'lg:block'"></div>
                <a href="{{ route('home') }}" target="_blank" class="sb-item group flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900" :class="colapsado && 'lg:justify-center lg:px-0'">
                    <span class="text-gray-400 group-hover:text-gray-600">{!! $icono('portada') !!}</span>
                    <span class="truncate flex-1" :class="colapsado && 'lg:hidden'">Portada</span>
                    <svg class="w-3.5 h-3.5 text-gray-300" :class="colapsado && 'lg:hidden'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    <span class="sb-tip hidden" :class="colapsado && 'lg:block'">Portada</span>
                </a>
                @can('turnos.ver')
                    <a href="{{ route('turno.form') }}" target="_blank" class="sb-item group flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900" :class="colapsado && 'lg:justify-center lg:px-0'">
                        <span class="text-gray-400 group-hover:text-gray-600">{!! $icono('kiosco') !!}</span>
                        <span class="truncate flex-1" :class="colapsado && 'lg:hidden'">Formulario de turnos</span>
                        <svg class="w-3.5 h-3.5 text-gray-300" :class="colapsado && 'lg:hidden'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                        <span class="sb-tip hidden" :class="colapsado && 'lg:block'">Formulario de turnos</span>
                    </a>
                @endcan
            </div>
        </nav>

        {{-- Usuario --}}
        <div class="p-3 border-t border-gray-100 shrink-0">
            <div class="flex items-center gap-3 p-2 rounded-xl {{ request()->routeIs('profile') ? 'bg-gray-50' : '' }}" :class="colapsado && 'lg:flex-col lg:p-0 lg:gap-2'">
                <a href="{{ route('profile') }}" wire:navigate class="sb-item flex items-center gap-3 min-w-0 flex-1" title="Mi perfil">
                    <span class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold shrink-0" style="background: var(--m-medio); color: var(--m)">{{ $iniciales }}</span>
                    <span class="min-w-0" :class="colapsado && 'lg:hidden'">
                        <span class="block text-sm font-semibold text-gray-800 truncate" x-data="{{ json_encode(['name' => $user->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                        <span class="block text-xs text-gray-400 truncate">{{ ucfirst($user->roles->first()?->name ?? 'usuario') }}</span>
                    </span>
                    <span class="sb-tip hidden" :class="colapsado && 'lg:block'">Mi perfil</span>
                </a>
                <button type="button" wire:click="logout" class="sb-item p-2 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 transition" aria-label="Cerrar sesión" title="Cerrar sesión">
                    {!! $icono('salir') !!}
                    <span class="sb-tip hidden" :class="colapsado && 'lg:block'">Cerrar sesión</span>
                </button>
            </div>
        </div>
    </aside>
</div>
