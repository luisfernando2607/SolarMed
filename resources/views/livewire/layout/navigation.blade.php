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
}; ?>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->user()->can('configuracion.editar') ? route('admin.dashboard') : route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                        <x-application-logo class="block h-9 w-auto" />
                        <span class="text-lg font-bold text-blue-900 hidden sm:block">SolarMed</span>
                    </a>
                </div>
                <div class="hidden space-x-1 sm:-my-px sm:ms-6 sm:flex">
                    @can('configuracion.editar')
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard*')" wire:navigate>
                            {{ __('Dashboard Admin') }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @endcan
                    @cannot('configuracion.editar')
                        @can('turnos.ver')
                            <x-nav-link :href="route('turnos.crear')" :active="request()->routeIs('turnos.crear*')" wire:navigate>
                                {{ __('Registrar turno') }}
                            </x-nav-link>
                            <x-nav-link :href="route('sala-espera')" :active="request()->routeIs('sala-espera*')" wire:navigate>
                                {{ __('Sala de Espera') }}
                            </x-nav-link>
                        @endcan
                        @can('turnos.ver')
                            @unlessrole('medico')
                                @unlessrole('secretaria')
                                    <x-nav-link :href="route('turno.form')" wire:navigate>
                                        {{ __('Formulario Paciente') }}
                                    </x-nav-link>
                                @endunlessrole
                            @endunlessrole
                        @endcan
                        @can('pacientes.ver')
                            <x-nav-link :href="route('pacientes')" :active="request()->routeIs('pacientes*')" wire:navigate>
                                {{ __('Pacientes') }}
                            </x-nav-link>
                        @endcan
                        @can('expediente.ver')
                            <x-nav-link :href="route('expedientes')" :active="request()->routeIs('expedientes*')" wire:navigate>
                                {{ __('Expedientes') }}
                            </x-nav-link>
                        @endcan
                        @can('facturas.ver')
                            <x-nav-link :href="route('facturas')" :active="request()->routeIs('facturas*')" wire:navigate>
                                {{ __('Facturación') }}
                            </x-nav-link>
                        @endcan
                    @endcannot
                    @can('usuarios.ver')
                        <x-nav-link :href="route('usuarios')" :active="request()->routeIs('usuarios*')" wire:navigate>
                            {{ __('Usuarios') }}
                        </x-nav-link>
                    @endcan
                    @can('configuracion.editar')
                        <x-nav-link :href="route('tarifario')" :active="request()->routeIs('tarifario*')" wire:navigate>
                            {{ __('Tarifario') }}
                        </x-nav-link>
                        <x-nav-link :href="route('sri.config')" :active="request()->routeIs('sri.config*')" wire:navigate>
                            {{ __('SRI') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Mi Perfil') }}
                        </x-dropdown-link>
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @can('configuracion.editar')
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard*')" wire:navigate>
                    {{ __('Dashboard Admin') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @endcan
            @cannot('configuracion.editar')
                @can('turnos.ver')
                    <x-responsive-nav-link :href="route('turnos.crear')" :active="request()->routeIs('turnos.crear*')" wire:navigate>
                        {{ __('Registrar turno') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('sala-espera')" :active="request()->routeIs('sala-espera*')" wire:navigate>
                        {{ __('Sala de Espera') }}
                    </x-responsive-nav-link>
                @endcan
                @can('turnos.ver')
                    @unlessrole('medico')
                        @unlessrole('secretaria')
                            <x-responsive-nav-link :href="route('turno.form')" wire:navigate>
                                {{ __('Formulario QR') }}
                            </x-responsive-nav-link>
                        @endunlessrole
                    @endunlessrole
                @endcan
                @can('pacientes.ver')
                    <x-responsive-nav-link :href="route('pacientes')" wire:navigate>
                        {{ __('Pacientes') }}
                    </x-responsive-nav-link>
                @endcan
                @can('expediente.ver')
                    <x-responsive-nav-link :href="route('expedientes')" wire:navigate>
                        {{ __('Expedientes') }}
                    </x-responsive-nav-link>
                @endcan
                @can('facturas.ver')
                    <x-responsive-nav-link :href="route('facturas')" wire:navigate>
                        {{ __('Facturación') }}
                    </x-responsive-nav-link>
                @endcan
            @endcannot
            @can('usuarios.ver')
                <x-responsive-nav-link :href="route('usuarios')" wire:navigate>
                    {{ __('Usuarios') }}
                </x-responsive-nav-link>
            @endcan
            @can('configuracion.editar')
                <x-responsive-nav-link :href="route('tarifario')" wire:navigate>
                    {{ __('Tarifario') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('sri.config')" wire:navigate>
                    {{ __('SRI') }}
                </x-responsive-nav-link>
            @endcan
        </div>
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Mi Perfil') }}
                </x-responsive-nav-link>
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
