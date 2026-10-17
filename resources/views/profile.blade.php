<x-app-layout>
    <x-slot name="header">
        <div class="max-w-5xl mx-auto w-full">
            <h2 class="text-xl font-bold text-gray-900">Mi perfil</h2>
            <p class="text-sm text-gray-500 mt-0.5">Actualiza tus datos, tu contraseña y la seguridad de tu cuenta.</p>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto space-y-6">
            <section class="bg-white rounded-xl shadow-sm border border-gray-200/70 p-6 sm:p-8">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </section>

            <section class="bg-white rounded-xl shadow-sm border border-gray-200/70 p-6 sm:p-8">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </section>

            @if (\App\Models\Medico::where('user_id', auth()->id())->exists())
                <section class="bg-white rounded-xl shadow-sm border border-gray-200/70 p-6 sm:p-8" id="firma">
                    <div class="max-w-xl">
                        <livewire:profile.firma-medico-form />
                    </div>
                </section>
            @endif

            <section class="bg-white rounded-xl shadow-sm border border-gray-200/70 p-6 sm:p-8" id="dos-factores">
                <div class="max-w-xl">
                    <livewire:profile.dos-factores-form />
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
