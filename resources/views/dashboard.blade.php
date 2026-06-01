<x-app-layout>


    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 1. Bienvenida + accesos rápidos --}}
            <div class="bg-white rounded-xl shadow-sm border p-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Bienvenido, {{ auth()->user()->name }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Has iniciado sesión como
                        <strong>{{ auth()->user()->roles->first()?->name ?? 'usuario' }}</strong>.
                    </p>
                </div>
                @can('turnos.ver')
                    <div class="flex flex-wrap items-center gap-2">
                        @role('secretaria')
                            <span class="text-sm text-gray-500 font-medium" x-data="{ now: new Date() }" x-init="setInterval(() => now = new Date(), 1000)">
                                <span x-text="now.toLocaleDateString('es-EC', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"></span>,
                                <span class="font-semibold text-gray-700" x-text="now.toLocaleTimeString('es-EC', { hour: '2-digit', minute: '2-digit' })"></span>
                            </span>
                            <a href="{{ route('turnos.crear') }}" wire:navigate
                               class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                                Registrar turno
                            </a>
                            <a href="{{ route('sala-espera') }}" wire:navigate
                               class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                Sala de espera
                            </a>
                        @else
                            <a href="{{ route('sala-espera') }}" wire:navigate
                               class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                Sala de espera
                            </a>
                            @role('medico')
                                <a href="{{ route('pacientes') }}" wire:navigate
                                   class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition border border-gray-200">
                                    Buscar pacientes
                                </a>
                            @endrole
                        @endrole
                    </div>
                @endcan
            </div>

            {{-- 2. Estadísticas --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg border-l-4 border-blue-500 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Turnos hoy</p>
                    <p class="text-3xl font-bold text-blue-700 mt-1">
                        {{ \App\Models\Turno::whereDate('fecha', today())->count() }}
                    </p>
                </div>
                <div class="bg-white rounded-lg border-l-4 border-pink-500 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pacientes en espera</p>
                    <p class="text-3xl font-bold text-pink-700 mt-1">
                        {{ \App\Models\Turno::whereDate('fecha', today())->where('estado', 'esperando')->count() }}
                    </p>
                </div>
                <div class="bg-white rounded-lg border-l-4 border-green-500 shadow-sm p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total pacientes</p>
                    <p class="text-3xl font-bold text-green-700 mt-1">
                        {{ \App\Models\Paciente::count() }}
                    </p>
                </div>
            </div>

            {{-- 3. Módulos --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Módulos</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">

                    <a href="{{ route('sala-espera') }}" wire:navigate
                       class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-400 hover:shadow-sm transition group">
                        <div class="text-2xl mb-2">🩺</div>
                        <h3 class="font-semibold text-sm text-gray-900 group-hover:text-blue-600">Sala de espera</h3>
                        <p class="text-xs text-gray-400 mt-1">Cola de pacientes y turnos</p>
                    </a>

                    @can('pacientes.ver')
                        <a href="{{ route('pacientes') }}" wire:navigate
                           class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-400 hover:shadow-sm transition group">
                            <div class="text-2xl mb-2">👥</div>
                            <h3 class="font-semibold text-sm text-gray-900 group-hover:text-blue-600">Pacientes</h3>
                            <p class="text-xs text-gray-400 mt-1">Buscar y ver fichas</p>
                        </a>
                    @endcan

                    @can('expediente.ver')
                        <a href="{{ route('expedientes') }}" wire:navigate
                           class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-400 hover:shadow-sm transition group">
                            <div class="text-2xl mb-2">📋</div>
                            <h3 class="font-semibold text-sm text-gray-900 group-hover:text-blue-600">Expediente clínico</h3>
                            <p class="text-xs text-gray-400 mt-1">Consultas y diagnósticos</p>
                        </a>
                    @endcan

                    @unlessrole('medico')
                        <a href="{{ route('turno.form') }}" wire:navigate
                           class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-400 hover:shadow-sm transition group">
                            <div class="text-2xl mb-2">📝</div>
                            <h3 class="font-semibold text-sm text-gray-900 group-hover:text-blue-600">Formulario paciente</h3>
                            <p class="text-xs text-gray-400 mt-1">Registrar nuevo paciente</p>
                        </a>
                    @endunlessrole

                    @can('usuarios.ver')
                        <a href="{{ route('usuarios') }}" wire:navigate
                           class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-400 hover:shadow-sm transition group">
                            <div class="text-2xl mb-2">🔐</div>
                            <h3 class="font-semibold text-sm text-gray-900 group-hover:text-blue-600">Usuarios</h3>
                            <p class="text-xs text-gray-400 mt-1">Gestionar accesos</p>
                        </a>
                    @endcan

                    @can('facturas.ver')
                        <a href="{{ route('facturas') }}" wire:navigate
                           class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-400 hover:shadow-sm transition group">
                            <div class="text-2xl mb-2">💰</div>
                            <h3 class="font-semibold text-sm text-gray-900 group-hover:text-blue-600">Facturación</h3>
                            <p class="text-xs text-gray-400 mt-1">Consultas y servicios</p>
                        </a>
                    @endcan

                </div>
            </div>

        </div>
    </div>
</x-app-layout>