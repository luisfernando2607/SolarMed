<?php

namespace Tests\Feature\Clinica;

use App\Livewire\Auditoria;
use App\Livewire\Cuenta\AcuerdoConfidencialidad;
use App\Livewire\Cuenta\CambiarPassword;
use App\Models\Paciente;
use App\Models\RegistroAcceso;
use App\Models\User;
use App\Services\AuditoriaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use LogicException;
use Tests\TestCase;

class TrazabilidadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->admin = User::factory()->create(['email' => 'admin@clinica.ec']);
        $this->admin->assignRole('admin');
    }

    public function test_ingresos_correctos_y_fallidos_quedan_registrados(): void
    {
        Volt::test('pages.auth.login')->set('form.email', 'admin@clinica.ec')->set('form.password', 'mala')->call('login');
        Volt::test('pages.auth.login')->set('form.email', 'nadie@x.ec')->set('form.password', 'mala')->call('login');
        Volt::test('pages.auth.login')->set('form.email', 'admin@clinica.ec')->set('form.password', 'password')->call('login');

        $r = RegistroAcceso::orderBy('id')->get();
        $this->assertSame(['login_fallido', 'login_fallido', 'login'], $r->pluck('accion')->all());
        $this->assertSame($this->admin->id, $r[0]->user_id);
        $this->assertNull($r[1]->user_id);
        $this->assertStringContainsString('nadie@x.ec', $r[1]->detalle);
        $this->assertSame($this->admin->id, $r[2]->user_id);
    }

    public function test_cadena_de_hashes_detecta_alteraciones(): void
    {
        $this->actingAs($this->admin);
        $servicio = app(AuditoriaService::class);
        $p = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
        foreach (['ver', 'descargar', 'editar'] as $accion) {
            $servicio->registrar($accion, 'paciente', $p->id, $p->id, 'Detalle con tildes: ñandú');
        }

        $this->assertTrue($servicio->verificarIntegridad()['integro']);
        $this->artisan('auditoria:verificar')->assertSuccessful();

        // El modelo no permite modificar ni borrar
        $segundo = RegistroAcceso::orderBy('id')->skip(1)->first();
        $this->assertThrows(fn () => $segundo->update(['detalle' => 'x']), LogicException::class);
        $this->assertThrows(fn () => $segundo->delete(), LogicException::class);

        // Una alteración directa en la base se detecta
        DB::table('registro_accesos')->where('id', $segundo->id)->update(['detalle' => 'manipulado']);
        $r = $servicio->verificarIntegridad();
        $this->assertFalse($r['integro']);
        $this->assertSame($segundo->id, $r['primer_error']);
        $this->artisan('auditoria:verificar')->assertFailed();
    }

    public function test_borrar_un_registro_rompe_la_cadena(): void
    {
        $this->actingAs($this->admin);
        $servicio = app(AuditoriaService::class);
        foreach (range(1, 3) as $i) {
            $servicio->registrar('ver', 'paciente', $i);
        }
        DB::table('registro_accesos')->where('id', RegistroAcceso::orderBy('id')->skip(1)->value('id'))->delete();

        $this->assertFalse($servicio->verificarIntegridad()['integro']);
    }

    public function test_cuenta_nueva_debe_cambiar_clave_y_aceptar_confidencialidad(): void
    {
        $u = User::factory()->nueva()->create(['password' => Hash::make('Temporal123')]);
        $u->assignRole('medico');

        $this->actingAs($u)->get(route('dashboard'))->assertRedirect(route('cuenta.password'));

        Livewire::actingAs($u)->test(CambiarPassword::class)
            ->set('password_actual', 'Temporal123')->set('password', 'corta')->set('password_confirmation', 'corta')
            ->call('guardar')->assertHasErrors('password')
            ->set('password', 'Temporal123')->set('password_confirmation', 'Temporal123')
            ->call('guardar')->assertHasErrors('password')
            ->set('password', 'MiClavePropia2026')->set('password_confirmation', 'MiClavePropia2026')
            ->call('guardar')->assertHasNoErrors();

        $u->refresh();
        $this->assertFalse($u->debe_cambiar_password);
        $this->assertTrue(Hash::check('MiClavePropia2026', $u->password));
        $this->actingAs($u)->get(route('dashboard'))->assertRedirect(route('cuenta.confidencialidad'));

        Livewire::actingAs($u)->test(AcuerdoConfidencialidad::class)
            ->call('aceptar')->assertHasErrors('acepto')
            ->set('acepto', true)->call('aceptar')->assertHasNoErrors();

        $this->assertNotNull($u->refresh()->confidencialidad_aceptada_at);
        $this->actingAs($u)->get(route('dashboard'))->assertOk();
        $this->assertSame(['cambiar_password', 'aceptar_confidencialidad'],
            RegistroAcceso::where('user_id', $u->id)->orderBy('id')->pluck('accion')->all());
    }

    public function test_clave_asignada_por_el_admin_obliga_a_cambiarla(): void
    {
        $u = User::factory()->create();
        $u->assignRole('secretaria');

        Livewire::actingAs($this->admin)->test(\App\Livewire\GestionUsuarios::class)
            ->call('editar', $u->id)
            ->set('password', 'Asignada2026')->set('password_confirmation', 'Asignada2026')
            ->call('guardar')->assertHasNoErrors();

        $this->assertTrue($u->refresh()->debe_cambiar_password);
    }

    public function test_exportar_csv_con_filtros_y_sello(): void
    {
        $this->actingAs($this->admin);
        $p = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
        app(AuditoriaService::class)->registrar('ver', 'paciente', $p->id, $p->id, '=HYPERLINK("http://x")');
        app(AuditoriaService::class)->registrar('login', 'sesion');

        $csv = $this->get(route('auditoria.exportar', ['accion' => 'ver']))->assertOk()->streamedContent();

        $this->assertStringContainsString('0102030405', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Inició sesión', $csv);
        $this->assertMatchesRegularExpression('/[0-9a-f]{64}/', $csv);
        $this->assertSame('exportar', RegistroAcceso::latest('id')->value('accion'));

        $medico = User::factory()->create(); $medico->assignRole('medico');
        $this->actingAs($medico)->get(route('auditoria.exportar'))->assertForbidden();
    }

    public function test_pantalla_verifica_integridad_y_filtra_sesiones(): void
    {
        $this->actingAs($this->admin);
        app(AuditoriaService::class)->registrar('login_fallido', 'sesion', null, null, 'Contraseña incorrecta: x@y.ec');
        app(AuditoriaService::class)->registrar('ver', 'paciente', 1);

        Livewire::test(Auditoria::class)
            ->assertSee('Integridad aún no verificada')
            ->call('verificarIntegridad')
            ->assertSee('Registro íntegro')
            ->set('accion', 'sesiones')
            ->assertSee('Intento fallido')
            ->assertDontSee('#1');
    }

    public function test_las_peticiones_internas_de_livewire_no_se_desvian(): void
    {
        // Sin esto el formulario de cambio de clave (y el de activar 2FA en el perfil) no podría enviarse
        $u = User::factory()->nueva()->create();
        $respuesta = $this->actingAs($u)->postJson('/livewire/update', ['components' => []]);

        $this->assertFalse($respuesta->isRedirect(route('cuenta.password')));

        config(['clinica.dos_factores_roles' => ['medico']]);
        $m = User::factory()->create(); $m->assignRole('medico');
        $this->assertFalse($this->actingAs($m)->postJson('/livewire/update', ['components' => []])->isRedirect());
        $this->actingAs($m)->get(route('dashboard'))->assertRedirect();
    }
}
