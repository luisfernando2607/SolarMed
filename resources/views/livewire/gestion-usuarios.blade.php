<div>
    <div class="max-w-7xl mx-auto py-8 px-4">
        {{-- Encabezado --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
                <p class="text-sm text-gray-500 mt-1">{{ number_format($usuarios->total()) }} {{ $usuarios->total() === 1 ? 'cuenta con acceso' : 'cuentas con acceso' }} al sistema</p>
            </div>
            @can('usuarios.gestionar')
                <button wire:click="crear" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nuevo usuario
                </button>
            @endcan
        </div>

        <div class="mb-4">
            <div class="relative sm:max-w-md">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input wire:model.live.debounce.300ms="busqueda" type="search"
                    class="w-full pl-10 rounded-xl border-gray-200 bg-white shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Buscar por nombre o email…">
            </div>
        </div>

        @php
            $flecha = function (string $campo) use ($sortBy, $sortDirection) {
                return $sortBy === $campo
                    ? '<svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="' . ($sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7') . '"/></svg>'
                    : '<svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>';
            };
            $tonosRol = [
                'admin' => 'bg-violet-50 text-violet-700',
                'medico' => 'bg-blue-50 text-blue-700',
                'secretaria' => 'bg-amber-50 text-amber-700',
                'enfermeria' => 'bg-emerald-50 text-emerald-700',
                'auditor' => 'bg-slate-100 text-slate-700',
            ];
        @endphp

        <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 border-b border-gray-100">
                        <tr>
                            <th class="text-left font-medium px-5 py-3"><button wire:click="ordenarPor('name')" class="inline-flex items-center gap-1 hover:text-gray-800">Usuario {!! $flecha('name') !!}</button></th>
                            <th class="text-left font-medium px-5 py-3 hidden md:table-cell"><button wire:click="ordenarPor('cedula')" class="inline-flex items-center gap-1 hover:text-gray-800">Cédula {!! $flecha('cedula') !!}</button></th>
                            <th class="text-left font-medium px-5 py-3">Rol</th>
                            <th class="text-left font-medium px-5 py-3 hidden lg:table-cell">Facturación</th>
                            <th class="text-left font-medium px-5 py-3 hidden sm:table-cell"><button wire:click="ordenarPor('created_at')" class="inline-flex items-center gap-1 hover:text-gray-800">Alta {!! $flecha('created_at') !!}</button></th>
                            <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($usuarios as $u)
                            @php
                                $partes = preg_split('/\s+/', trim((string) $u->name)) ?: [];
                                $ini = mb_strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
                                $rolNombre = $u->roles->first()?->name;
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex items-center justify-center w-9 h-9 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold shrink-0">{{ $ini ?: '?' }}</span>
                                        <span class="min-w-0">
                                            <span class="block font-medium text-gray-900 truncate max-w-[150px] sm:max-w-[220px]">
                                                {{ $u->name }}
                                                @if ($u->id === auth()->id())<span class="ml-1 text-xs font-normal text-gray-400">(usted)</span>@endif
                                            </span>
                                            <span class="block text-xs text-gray-500 truncate max-w-[150px] sm:max-w-[220px]">{{ $u->email }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 hidden md:table-cell text-gray-600 font-mono text-[13px]">{{ $u->cedula ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $tonosRol[$rolNombre] ?? 'bg-gray-100 text-gray-600' }}">{{ $rolNombre ?? 'Sin rol' }}</span>
                                    {{-- Requisitos AM 5216-A: clave personal y acuerdo de confidencialidad --}}
                                    <span class="mt-1 flex flex-wrap gap-1">
                                        @if ($u->debe_cambiar_password)
                                            <span class="px-1.5 py-0.5 rounded text-[11px] bg-amber-50 text-amber-700" title="Aún usa la clave asignada; deberá cambiarla al entrar">Clave temporal</span>
                                        @endif
                                        @if ($u->confidencialidad_aceptada_at)
                                            <span class="px-1.5 py-0.5 rounded text-[11px] bg-gray-100 text-gray-500" title="Aceptó el acuerdo de confidencialidad el {{ $u->confidencialidad_aceptada_at->format('d/m/Y H:i') }}">Confidencialidad ✓</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[11px] bg-amber-50 text-amber-700">Sin acuerdo de confidencialidad</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-5 py-3 hidden lg:table-cell">
                                    @if ($u->medico)
                                        <div class="flex flex-wrap gap-1">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $u->medico->ruc ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $u->medico->ruc ? 'RUC' : 'Sin RUC' }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $u->medico->p12_path ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $u->medico->p12_path ? 'Firma .p12' : 'Sin .p12' }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">No aplica</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 hidden sm:table-cell text-gray-500 whitespace-nowrap">{{ $u->created_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    @can('usuarios.gestionar')
                                        <button wire:click="editar({{ $u->id }})"
                                            class="inline-flex p-2 rounded-lg text-gray-400 hover:text-blue-700 hover:bg-blue-50 transition"
                                            title="Editar" aria-label="Editar usuario">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        </button>
                                        @if ($u->id !== auth()->id())
                                            <button x-data
                                                x-on:click="Swal.fire({
                                                    icon: 'warning',
                                                    title: '¿Eliminar usuario?',
                                                    text: {{ \Illuminate\Support\Js::from($u->name . '. Esta acción no se puede deshacer.') }},
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#dc2626',
                                                    cancelButtonColor: '#6b7280',
                                                    confirmButtonText: 'Sí, eliminar',
                                                    cancelButtonText: 'Cancelar'
                                                }).then((result) => { if (result.isConfirmed) $wire.eliminar({{ $u->id }}) })"
                                                class="inline-flex p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition"
                                                title="Eliminar" aria-label="Eliminar usuario">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-14 text-center">
                                    <p class="text-sm font-medium text-gray-700">{{ $busqueda ? 'No hay usuarios para esta búsqueda' : 'Aún no hay usuarios' }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $busqueda ? 'Pruebe con otro nombre o email.' : 'Cree el primero desde "Nuevo usuario".' }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($usuarios->hasPages())
                <div class="p-4 border-t border-gray-100">{{ $usuarios->links() }}</div>
            @endif
        </div>

        {{-- Modal crear / editar --}}
        @if ($editMode || $editId === 0)
            <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-50 flex items-start justify-center p-4 pt-12 overflow-y-auto"
                 wire:click.self="resetForm">
                <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 max-w-lg w-full p-6">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-xl font-bold text-gray-900">{{ $editMode ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
                        <button wire:click="resetForm" class="p-1.5 -m-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition" aria-label="Cerrar"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>

                    <form wire:submit="guardar" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nombre *</label>
                            <input wire:model="name" type="text"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Nombre completo">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email *</label>
                            <input wire:model="email" type="email"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="correo@ejemplo.com">
                            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Cédula / RUC</label>
                            <input wire:model="cedula" type="text" maxlength="13"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Cédula o RUC">
                            @error('cedula') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Contraseña {{ $editMode ? '(dejar vacío para mantener)' : '*' }}</label>
                            <input wire:model="password" type="password"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="{{ $editMode ? 'Nueva contraseña' : 'Contraseña' }}">
                            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Confirmar contraseña {{ $editMode ? '(dejar vacío para mantener)' : '*' }}</label>
                            <input wire:model="password_confirmation" type="password"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Repetir contraseña">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Rol *</label>
                            <select wire:model.live="rol"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Seleccione un rol...</option>
                                @foreach ($roles as $r)
                                    <option value="{{ $r->name }}">{{ ucfirst($r->name) }}</option>
                                @endforeach
                            </select>
                            @error('rol') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if ($editMode && $rol === 'medico')
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Certificado digital .p12</label>
                                <input wire:model="p12_file" type="file" accept=".p12,.pfx"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                                        file:rounded-lg file:border-0 file:text-sm file:font-medium
                                        file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                @error('p12_file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                @if ($p12_file)
                                    <p class="text-xs text-gray-400 mt-1">{{ $p12_file->getClientOriginalName() }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Contraseña del certificado</label>
                                <input wire:model="p12_password" type="password" autocomplete="new-password"
                                    class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                @error('p12_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        @if ($rol === 'medico')
                            <div class="border-t border-gray-100 pt-4 space-y-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700">Datos del prescriptor</h4>
                                    <p class="text-xs text-gray-400">Obligatorios en la receta médica (Resolución ACESS-2023-0030, Art. 5).</p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Registro profesional ACESS</label>
                                        <input wire:model="colegiatura" type="text" maxlength="40" placeholder="Número de registro"
                                            class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                        @error('colegiatura') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono de contacto permanente</label>
                                        <input wire:model="telefono_medico" type="text" maxlength="40" placeholder="0991234567"
                                            class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                        @error('telefono_medico') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-gray-100 pt-4 space-y-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700">Datos tributarios (facturación electrónica)</h4>
                                    <p class="text-xs text-gray-400">Las facturas de este médico se emiten con su RUC. Si lo deja vacío se usan los datos de SRI &gt; Configuración.</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">RUC</label>
                                    <input wire:model="ruc" type="text" maxlength="13" placeholder="0912345678001"
                                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @error('ruc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Razón social</label>
                                    <input wire:model="razon_social" type="text" placeholder="Como consta en el RUC"
                                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @error('razon_social') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre comercial (opcional)</label>
                                    <input wire:model="nombre_comercial" type="text" 
                                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @error('nombre_comercial') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Dirección matriz</label>
                                    <input wire:model="direccion_matriz" type="text" 
                                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @error('direccion_matriz') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Obligado a llevar contabilidad</label>
                                        <select wire:model="obligado_contabilidad" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                            <option value="NO">NO</option>
                                            <option value="SI">SI</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Régimen RIMPE</label>
                                        <select wire:model="regimen_rimpe" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                            <option value="">No aplica</option>
                                            @foreach (\App\Models\Medico::REGIMENES_RIMPE as $regimen)
                                                <option value="{{ $regimen }}">{{ $regimen }}</option>
                                            @endforeach
                                        </select>
                                        @error('regimen_rimpe') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Establecimiento</label>
                                    <input wire:model="sri_establecimiento" type="text" maxlength="3"
                                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @error('sri_establecimiento') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                    <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Punto de emisión</label>
                                    <input wire:model="sri_pto_emi" type="text" maxlength="3"
                                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @error('sri_pto_emi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" wire:click="resetForm"
                                class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition text-sm">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition shadow-sm text-sm">
                                {{ $editMode ? 'Guardar cambios' : 'Crear usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
