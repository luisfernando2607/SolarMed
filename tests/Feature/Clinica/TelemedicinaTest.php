<?php

namespace Tests\Feature\Clinica;

use App\Livewire\Agenda;
use App\Livewire\CrearConsultaSinTurno;
use App\Livewire\DetalleTeleconsulta;
use App\Livewire\PacienteTeleconsulta;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\RegistroAcceso;
use App\Models\Teleconsulta;
use App\Models\User;
use App\Services\AjustesService;
use App\Services\TelemedicinaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TelemedicinaTest extends TestCase
{
    use RefreshDatabase;

    private Medico $medico;
    private User $recepcion;
    private Paciente $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        app(AjustesService::class)->guardar(['telemedicina.activa' => true]);
        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia', 'prefijo_turno' => 'O']);
        $u = User::factory()->create(); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'María', 'apellidos' => 'Cuadros', 'especialidad_id' => $esp->id]);
        $this->recepcion = User::factory()->create(); $this->recepcion->assignRole('admin');
        $this->ana = Paciente::create(['nombres' => 'Ana Lucía', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino',
            'telefono' => '0991234567', 'alergias' => 'Penicilina']);
    }

    private function agendar(string $cuando = '+2 hours'): Teleconsulta
    {
        Livewire::actingAs($this->recepcion)->test(Agenda::class)
            ->call('nuevaCita', now()->modify($cuando)->format('Y-m-d\TH:i'))
            ->call('seleccionarPaciente', $this->ana->id)
            ->set('medico_id', $this->medico->id)
            ->set('modalidad', 'telemedicina')
            ->call('guardarCita')->assertHasNoErrors();

        return Teleconsulta::latest('id')->firstOrFail();
    }

    public function test_agendar_en_modalidad_telemedicina_crea_sala_sin_datos_del_paciente(): void
    {
        $tc = $this->agendar();

        $this->assertSame('telemedicina', $tc->cita->modalidad);
        $this->assertSame('programada', $tc->estado);
        $this->assertStringStartsWith('https://meet.jit.si/consulta-', $tc->sala_url);
        $sala = basename($tc->sala_url);
        $this->assertGreaterThanOrEqual(32, strlen($sala) - strlen('consulta-'));
        foreach (['Ana', 'Pérez', 'Perez', '0102030405', 'Cuadros', 'Maria'] as $dato) {
            $this->assertStringNotContainsStringIgnoringCase($dato, $tc->sala_url);
        }
        $this->assertSame(48, strlen($tc->token));
        $this->assertNotSame(basename(app(TelemedicinaService::class)->urlSala()), $sala);
    }

    public function test_desactivada_no_permite_la_modalidad(): void
    {
        app(AjustesService::class)->guardar(['telemedicina.activa' => false]);
        Livewire::actingAs($this->recepcion)->test(Agenda::class)
            ->call('nuevaCita', now()->addHours(2)->format('Y-m-d\TH:i'))
            ->call('seleccionarPaciente', $this->ana->id)
            ->set('medico_id', $this->medico->id)
            ->set('modalidad', 'telemedicina')
            ->call('guardarCita')->assertHasErrors('modalidad');
        $this->assertSame(0, Teleconsulta::count());
    }

    public function test_pagina_del_paciente_exige_consentimiento_y_no_muestra_datos_clinicos(): void
    {
        $tc = $this->agendar('+10 minutes');

        $this->get(route('teleconsulta.paciente', $tc->token))->assertOk()
            ->assertSee('Autorización para la atención por medios telemáticos')
            ->assertDontSee('Pérez')->assertDontSee('0102030405')->assertDontSee('Penicilina')
            ->assertDontSee($tc->sala_url);

        Livewire::test(PacienteTeleconsulta::class, ['token' => $tc->token])
            ->set('autoriza', true)->call('aceptar')->assertHasErrors('consiente')
            ->set('consiente', true)->call('aceptar')->assertHasNoErrors()
            ->assertSee('Entrar a la videoconsulta')->assertSee($tc->sala_url);

        $tc->refresh();
        $this->assertTrue($tc->tieneConsentimiento());
        $this->assertSame('enlace', $tc->consentimiento_medio);
        $this->assertNotNull($tc->consentimiento_ip);
        $this->assertSame('crear', RegistroAcceso::where('recurso', 'teleconsulta')->latest('id')->value('accion'));

        $this->get('/teleconsulta/' . str_repeat('a', 48))->assertNotFound();
    }

    public function test_la_pagina_publica_limita_los_intentos(): void
    {
        $ruta = '/teleconsulta/' . str_repeat('b', 48);
        for ($i = 0; $i < 60; $i++) {
            $this->get($ruta)->assertNotFound();
        }
        $this->get($ruta)->assertStatus(429);
    }

    public function test_la_sala_solo_se_entrega_dentro_del_horario(): void
    {
        $tc = $this->agendar('+3 hours');
        $tc->update(['autorizacion_telematica_at' => now(), 'consentimiento_datos_at' => now(), 'consentimiento_medio' => 'enlace']);

        Livewire::test(PacienteTeleconsulta::class, ['token' => $tc->token])
            ->assertDontSee($tc->sala_url)->assertSee('El botón para entrar aparecerá aquí');

        $this->travelTo($tc->fecha_hora->copy()->subMinutes(5));
        Livewire::test(PacienteTeleconsulta::class, ['token' => $tc->token])->assertSee($tc->sala_url);

        $this->travelTo($tc->fecha_hora->copy()->addMinutes(90));
        Livewire::test(PacienteTeleconsulta::class, ['token' => $tc->token])->assertDontSee($tc->sala_url)->assertSee('ya pasó');
    }

    public function test_el_medico_atiende_y_la_consulta_queda_con_modalidad_telemedicina(): void
    {
        $tc = $this->agendar();
        $medico = $this->medico->user;

        // Sin consentimiento no se inicia
        Livewire::actingAs($medico)->test(DetalleTeleconsulta::class, ['teleconsulta' => $tc])->call('iniciar')->assertForbidden();

        Livewire::actingAs($medico)->test(DetalleTeleconsulta::class, ['teleconsulta' => $tc])
            ->call('registrarConsentimientoVerbal')->assertHasErrors('confirmaVerbal')
            ->set('confirmaVerbal', true)->call('registrarConsentimientoVerbal')->assertHasNoErrors()
            ->call('iniciar');
        $tc->refresh();
        $this->assertSame('verbal', $tc->consentimiento_medio);
        $this->assertSame($medico->id, $tc->consentimiento_registrado_por);
        $this->assertSame('en_curso', $tc->estado);

        $this->actingAs($medico);
        Livewire::withQueryParams(['teleconsulta' => $tc->id])->test(CrearConsultaSinTurno::class)
            ->assertSet('modalidad', 'telemedicina')
            ->set('motivo_consulta', 'Control por video')->set('diagnostico', 'Sana')->set('tipo_consulta', 'ginecologica')
            ->call('guardar')->assertHasNoErrors()->assertRedirect(route('telemedicina.ver', $tc->id));

        $consulta = ExpedienteConsulta::sole();
        $this->assertSame('telemedicina', $consulta->modalidad);
        $this->assertSame($consulta->id, $tc->fresh()->consulta_id);

        Livewire::actingAs($medico)->test(DetalleTeleconsulta::class, ['teleconsulta' => $tc])
            ->set('resultado', 'parcial')->set('observaciones', 'Se cortó el audio dos veces')->call('finalizar')->assertHasNoErrors();
        $tc->refresh();
        $this->assertSame('finalizada', $tc->estado);
        $this->assertSame('parcial', $tc->resultado_conexion);
        $this->assertSame('completada', $tc->cita->estado);

        $this->actingAs($medico)->get(route('pacientes.ver', $this->ana))->assertSee('Telemedicina');
    }

    public function test_cancelar_o_mover_la_cita_actualiza_la_teleconsulta(): void
    {
        $tc = $this->agendar('+1 day');
        $nueva = $tc->fecha_hora->copy()->addDay();
        Livewire::actingAs($this->recepcion)->test(Agenda::class)->call('mover', $tc->cita_id, $nueva->toIso8601String());
        $this->assertTrue($tc->fresh()->fecha_hora->equalTo($nueva));

        Livewire::actingAs($this->recepcion)->test(Agenda::class)->call('verCita', $tc->cita_id)->call('cambiarEstado', 'cancelada');
        $this->assertSame('cancelada', $tc->fresh()->estado);
        $this->get(route('teleconsulta.paciente', $tc->token))->assertSee('fue cancelada');
    }

    public function test_permisos(): void
    {
        $tc = $this->agendar();
        $otroMedico = User::factory()->create(); $otroMedico->assignRole('medico');
        Medico::create(['user_id' => $otroMedico->id, 'nombres' => 'Luis', 'apellidos' => 'Mora', 'especialidad_id' => $this->medico->especialidad_id]);

        // Otro médico no atiende ni registra la consulta de esta teleconsulta
        Livewire::actingAs($otroMedico)->test(DetalleTeleconsulta::class, ['teleconsulta' => $tc])
            ->set('confirmaVerbal', true)->call('registrarConsentimientoVerbal')->assertForbidden();
        $this->actingAs($otroMedico)->get(route('expedientes.crear', ['teleconsulta' => $tc->id]))->assertForbidden();

        $sec = User::factory()->create(); $sec->assignRole('secretaria');
        $this->actingAs($sec)->get(route('telemedicina'))->assertOk();
        Livewire::actingAs($sec)->test(DetalleTeleconsulta::class, ['teleconsulta' => $tc])->call('iniciar')->assertForbidden();

        $aud = User::factory()->create(); $aud->assignRole('auditor');
        $this->actingAs($aud)->get(route('telemedicina'))->assertForbidden();
    }

    public function test_enlace_manual_exige_https_y_whatsapp_sin_nombre_reservado(): void
    {
        app(AjustesService::class)->guardar(['telemedicina.plataforma' => 'manual']);
        $tc = $this->agendar();
        $this->assertNull($tc->sala_url);

        Livewire::actingAs($this->recepcion)->test(DetalleTeleconsulta::class, ['teleconsulta' => $tc])
            ->set('sala_url', 'http://zoom.us/j/123')->call('guardarSala')->assertHasErrors('sala_url')
            ->set('sala_url', 'https://zoom.us/j/123')->call('guardarSala')->assertHasNoErrors();
        $this->assertSame('https://zoom.us/j/123', $tc->fresh()->sala_url);

        $wa = rawurldecode(app(TelemedicinaService::class)->enlaceWhatsapp($tc->fresh()));
        $this->assertStringContainsString('Hola Ana', $wa);
        $this->assertStringContainsString($tc->token, $wa);
        $this->ana->update(['identidad_reservada' => true]);
        $this->assertStringNotContainsString('Ana', rawurldecode(app(TelemedicinaService::class)->enlaceWhatsapp($tc->fresh()->load('paciente'))));
    }
}
