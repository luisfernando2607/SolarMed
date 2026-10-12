@php
    $nombresRol = ['admin' => 'Administrador', 'medico' => 'Médico', 'secretaria' => 'Secretaría', 'enfermeria' => 'Enfermería', 'auditor' => 'Auditor'];
@endphp
<div>
<div class="max-w-6xl mx-auto py-8 px-4">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Roles y permisos</h1>
        <p class="text-sm text-gray-500">Qué puede hacer cada rol. Los cambios se aplican al instante y quedan en la auditoría. El rol Administrador siempre tiene todos los permisos.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b bg-gray-50/60">
                    <th class="text-left font-medium text-gray-500 px-5 py-3 w-1/2">Permiso</th>
                    @foreach ($roles as $rol)
                        <th class="px-3 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                            {{ $nombresRol[$rol->name] ?? ucfirst($rol->name) }}
                            <span class="block text-[11px] font-normal text-gray-400">{{ $rol->users_count }} usuario(s)</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($grupos as $grupo => $permisos)
                    <tr class="bg-gray-50/40">
                        <td colspan="{{ $roles->count() + 1 }}" class="px-5 pt-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                            {{ \App\Livewire\RolesPermisos::GRUPOS[$grupo] ?? ucfirst($grupo) }}
                        </td>
                    </tr>
                    @foreach ($permisos as $permiso)
                        <tr class="border-t border-gray-100 hover:bg-gray-50/60" wire:key="p-{{ $permiso }}">
                            <td class="px-5 py-2.5">
                                <span class="text-gray-800">{{ \App\Livewire\RolesPermisos::DESCRIPCIONES[$permiso] ?? $permiso }}</span>
                                @if (in_array($permiso, \App\Livewire\RolesPermisos::SENSIBLES))
                                    <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200">Sensible</span>
                                @endif
                                <span class="block text-[11px] text-gray-400 font-mono">{{ $permiso }}</span>
                            </td>
                            @foreach ($roles as $rol)
                                @php $tiene = isset($asignados[$rol->id][$permiso]); $protegido = $rol->name === \App\Livewire\RolesPermisos::ROL_PROTEGIDO; @endphp
                                <td class="px-3 py-2.5 text-center">
                                    <input type="checkbox" @checked($tiene || $protegido) @disabled($protegido)
                                        aria-label="{{ ($nombresRol[$rol->name] ?? $rol->name) . ': ' . (\App\Livewire\RolesPermisos::DESCRIPCIONES[$permiso] ?? $permiso) }}"
                                        @if (!$protegido)
                                            @if (!$tiene && in_array($permiso, \App\Livewire\RolesPermisos::SENSIBLES))
                                                x-on:click.prevent="if (confirm({{ \Illuminate\Support\Js::from('Este permiso da acceso de administración al rol ' . ($nombresRol[$rol->name] ?? $rol->name) . '. ¿Continuar?') }})) $wire.alternar({{ $rol->id }}, {{ \Illuminate\Support\Js::from($permiso) }})"
                                            @else
                                                wire:click="alternar({{ $rol->id }}, {{ \Illuminate\Support\Js::from($permiso) }})"
                                            @endif
                                        @endif
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 {{ $protegido ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-gray-400 mt-3">Los usuarios con sesión abierta ven los cambios en su siguiente acción.</p>
</div>
</div>
