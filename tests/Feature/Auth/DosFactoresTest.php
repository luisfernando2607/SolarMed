<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\DosFactoresService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class DosFactoresTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConDosFactores(): array
    {
        $secreto = (new Google2FA())->generateSecretKey(32);
        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => $secreto,
            'two_factor_recovery_codes' => ['aaaaa-bbbbb', 'ccccc-ddddd'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return [$user, $secreto];
    }

    public function test_activar_desde_el_perfil(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $c = Volt::test('profile.dos-factores-form')->call('activar')->assertSee('Escanee este código');
        $secreto = $c->get('secretoPendiente');

        $c->set('codigo', '000000')->call('confirmar')->assertHasErrors('codigo');
        $this->assertFalse($user->fresh()->tieneDosFactores());

        $c->set('codigo', (new Google2FA())->getCurrentOtp($secreto))->call('confirmar')->assertHasNoErrors();
        $user->refresh();
        $this->assertTrue($user->tieneDosFactores());
        $this->assertCount(8, $user->two_factor_recovery_codes);
        $this->assertSame($secreto, $user->two_factor_secret);
        $this->assertNotSame($secreto, \DB::table('users')->where('id', $user->id)->value('two_factor_secret'), 'El secreto se guarda cifrado');
    }

    public function test_login_pide_el_codigo_y_no_inicia_sesion_solo_con_la_contrasena(): void
    {
        [$user, $secreto] = $this->usuarioConDosFactores();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('dos-factores.desafio'));
        $this->assertGuest();

        Volt::test('pages.auth.dos-factores')->set('codigo', '123456')->call('verificar')->assertHasErrors('codigo');
        $this->assertGuest();

        $codigo = (new Google2FA())->getCurrentOtp($secreto);
        Volt::test('pages.auth.dos-factores')->set('codigo', $codigo)->call('verificar')->assertHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_el_mismo_codigo_no_se_acepta_dos_veces(): void
    {
        [$user, $secreto] = $this->usuarioConDosFactores();
        $codigo = (new Google2FA())->getCurrentOtp($secreto);

        session(['dos_factores.login' => ['id' => $user->id, 'remember' => false, 'expira' => now()->addMinutes(5)->getTimestamp()]]);
        Volt::test('pages.auth.dos-factores')->set('codigo', $codigo)->call('verificar')->assertHasNoErrors();

        auth()->logout();
        session(['dos_factores.login' => ['id' => $user->id, 'remember' => false, 'expira' => now()->addMinutes(5)->getTimestamp()]]);
        Volt::test('pages.auth.dos-factores')->set('codigo', $codigo)->call('verificar')->assertHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_codigo_de_recuperacion_sirve_una_sola_vez(): void
    {
        [$user] = $this->usuarioConDosFactores();
        session(['dos_factores.login' => ['id' => $user->id, 'remember' => false, 'expira' => now()->addMinutes(5)->getTimestamp()]]);

        Volt::test('pages.auth.dos-factores')
            ->set('usarRecuperacion', true)
            ->set('codigo', 'AAAAA-BBBBB')
            ->call('verificar')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(['ccccc-ddddd'], $user->fresh()->two_factor_recovery_codes);
    }

    public function test_limite_de_intentos(): void
    {
        [$user] = $this->usuarioConDosFactores();
        session(['dos_factores.login' => ['id' => $user->id, 'remember' => false, 'expira' => now()->addMinutes(5)->getTimestamp()]]);

        $c = Volt::test('pages.auth.dos-factores');
        foreach (range(1, 5) as $_) {
            $c->set('codigo', '000000')->call('verificar');
        }
        $c->set('codigo', '000000')->call('verificar')->assertSee('Demasiados intentos');
    }

    public function test_desafio_expirado_vuelve_al_login(): void
    {
        [$user] = $this->usuarioConDosFactores();
        session(['dos_factores.login' => ['id' => $user->id, 'remember' => false, 'expira' => now()->subMinute()->getTimestamp()]]);

        $this->get(route('dos-factores.desafio'))->assertRedirect(route('login'));
    }

    public function test_rol_obligado_debe_activarla_antes_de_usar_el_sistema(): void
    {
        $this->seed(RolesSeeder::class);
        config(['clinica.dos_factores_roles' => ['medico']]);
        $user = User::factory()->create();
        $user->assignRole('medico');

        $this->actingAs($user)->get('/pacientes')->assertRedirect(route('cuenta.dos-factores'));
        $this->actingAs($user)->get(route('cuenta.dos-factores'))->assertOk();
        $this->actingAs($user)->get('/profile')->assertOk();

        Volt::test('profile.dos-factores-form')->assertSee('Su rol requiere');

        $user->forceFill(['two_factor_secret' => 'x', 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($user->fresh())->get('/pacientes')->assertOk();
    }

    public function test_desactivar_pide_contrasena_y_no_se_permite_si_el_rol_la_exige(): void
    {
        [$user] = $this->usuarioConDosFactores();
        $this->actingAs($user);

        Volt::test('profile.dos-factores-form')->set('password', 'mala')->call('desactivar')->assertHasErrors('password');
        $this->assertTrue($user->fresh()->tieneDosFactores());

        Volt::test('profile.dos-factores-form')->set('password', 'password')->call('desactivar')->assertHasNoErrors();
        $this->assertFalse($user->fresh()->tieneDosFactores());
    }
}
