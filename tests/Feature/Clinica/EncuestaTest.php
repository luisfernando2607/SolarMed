<?php

namespace Tests\Feature\Clinica;

use App\Livewire\ReporteSatisfaccion;
use App\Livewire\ResponderEncuesta;
use App\Models\Encuesta;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use App\Services\AjustesService;
use App\Services\EncuestaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EncuestaTest extends TestCase
{
    use RefreshDatabase;

    private Medico $medico;
    private Paciente $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia', 'prefijo_turno' => 'O']);
        $u = User::factory()->create(); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'María', 'apellidos' => 'Cuadros', 'especialidad_id' => $esp->id]);
        $this->ana = Paciente::create(['nombres' => 'Ana Lucía', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino', 'telefono' => '0991234567']);
    }

    private function consulta(): ExpedienteConsulta
    {
        return ExpedienteConsulta::create(['paciente_id' => $this->ana->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->medico->especialidad_id,
            'tipo_consulta' => 'ginecologica', 'fecha' => now(), 'motivo_consulta' => 'Control', 'diagnostico' => 'Sano']);
    }

    private function responder(Encuesta $e, array $r, int $rec, bool $dispositivo = false): void
    {
        $t = Livewire::withQueryParams($dispositivo ? ['dispositivo' => 1] : [])->test(ResponderEncuesta::class, ['token' => $e->token]);
        foreach ($r as $campo => $v) {
            $t->set("respuestas.{$campo}", $v);
        }
        $t->set('recomendacion', $rec)->set('comentario', 'Muy amables')->call('enviar')->assertHasNoErrors()->assertSee('¡Gracias por su opinión!');
    }

    public function test_cada_consulta_crea_su_encuesta_y_se_responde_una_vez(): void
    {
        $e = $this->consulta()->encuesta;
        $this->assertNotNull($e);
        $this->assertSame(40, strlen($e->token));

        $this->get(route('encuesta.responder', $e->token))->assertOk()->assertSee('¿Cómo fue su atención?')->assertDontSee('Pérez');

        Livewire::test(ResponderEncuesta::class, ['token' => $e->token])->call('enviar')->assertHasErrors(['recomendacion', 'respuestas.p_trato']);
        $this->responder($e, ['p_trato' => 5, 'p_espera' => 3, 'p_explicacion' => 5, 'p_instalaciones' => 4], 9, true);

        $e->refresh();
        $this->assertSame('dispositivo', $e->canal_respuesta);
        $this->assertSame(4.3, $e->promedio());
        $this->get(route('encuesta.responder', $e->token))->assertSee('ya fue respondida');
    }

    public function test_enlace_expirado_y_token_invalido(): void
    {
        $e = $this->consulta()->encuesta;
        $e->update(['expira_at' => now()->subDay()]);
        $this->get(route('encuesta.responder', $e->token))->assertSee('ya expiró');
        $this->get('/encuesta/' . str_repeat('a', 40))->assertNotFound();
    }

    public function test_desactivadas_no_se_crean_y_whatsapp_sin_nombre_reservado(): void
    {
        app(AjustesService::class)->guardar(['encuestas.activas' => false]);
        $this->assertNull($this->consulta()->encuesta);

        app(AjustesService::class)->guardar(['encuestas.activas' => true]);
        $e = $this->consulta()->encuesta;
        $this->assertStringContainsString(rawurlencode('Hola Ana,'), app(EncuestaService::class)->enlaceWhatsapp($e));

        $this->ana->update(['identidad_reservada' => true]);
        $wa = app(EncuestaService::class)->enlaceWhatsapp($e->fresh());
        $this->assertStringNotContainsString('Ana', rawurldecode($wa));
    }

    public function test_reporte_calcula_promedio_y_nps(): void
    {
        $this->responder($this->consulta()->encuesta, ['p_trato' => 5, 'p_espera' => 5, 'p_explicacion' => 5, 'p_instalaciones' => 5], 10);
        $this->responder($this->consulta()->encuesta, ['p_trato' => 3, 'p_espera' => 3, 'p_explicacion' => 3, 'p_instalaciones' => 3], 5);
        $this->consulta(); // una sin responder

        $admin = User::factory()->create(); $admin->assignRole('admin');
        Livewire::actingAs($admin)->test(ReporteSatisfaccion::class)
            ->assertViewHas('kpis', fn ($k) => $k['respuestas'] === 2 && $k['creadas'] === 3 && $k['promedio'] === 4.0 && $k['nps'] === 0 && $k['tasa'] === 67)
            ->assertSee('Muy amables')
            ->assertDontSee('Pérez');

        $this->actingAs($this->medico->user)->get(route('reportes.satisfaccion'))->assertForbidden();
        $this->actingAs($this->medico->user)->get(route('dashboard'))->assertSee('Satisfacción de mis pacientes');
    }
}
