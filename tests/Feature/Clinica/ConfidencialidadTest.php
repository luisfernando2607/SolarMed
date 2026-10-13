<?php

namespace Tests\Feature\Clinica;

use App\Livewire\Agenda;
use App\Livewire\IncidentesSeguridad;
use App\Livewire\PanelPendientes;
use App\Livewire\SolicitudesInformacion;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\IncidenteSeguridad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Receta;
use App\Models\RegistroAcceso;
use App\Models\SolicitudInformacion;
use App\Models\Turno;
use App\Models\User;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ConfidencialidadTest extends TestCase
{
    use RefreshDatabase;

    private Medico $medico;
    private Especialidad $esp;
    private Paciente $reservado;
    private User $auditor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->esp = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G']);
        $u = User::factory()->create(); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'Luis', 'apellidos' => 'Mora', 'especialidad_id' => $this->esp->id]);
        $this->reservado = Paciente::create(['nombres' => 'Rosa', 'apellidos' => 'Quintero', 'cedula' => '0102030405', 'sexo' => 'femenino',
            'telefono' => '0991234567', 'identidad_reservada' => true]);
        $this->auditor = User::factory()->create(); $this->auditor->assignRole('auditor');
    }

    public function test_pdfs_clinicos_llevan_confidencial_y_ocultan_el_nombre_reservado(): void
    {
        $receta = Receta::create(['paciente_id' => $this->reservado->id, 'medico_id' => $this->medico->id, 'fecha' => today(), 'medicamentos' => [['medicamento' => 'Isoniazida']]]);
        $html = view('pdf.receta', ['receta' => $receta->load('paciente', 'medico.especialidad', 'consulta')])->render();

        // La receta se dispensa en farmacia: lleva los nombres completos (ACESS-2023-0030 Art. 5 b.i) y la leyenda CONFIDENCIAL
        $this->assertStringContainsString('CONFIDENCIAL', $html);
        $this->assertStringContainsString('Rosa Quintero', $html);

        $historial = view('pdf.historial-paciente', ['paciente' => $this->reservado->load('consultas')])->render();
        $this->assertStringContainsString('CONFIDENCIAL', $historial);
        $this->assertStringNotContainsString('Quintero', $historial);
    }

    public function test_paciente_sin_reserva_se_ve_con_su_nombre(): void
    {
        $ana = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0911111111', 'sexo' => 'femenino']);
        $receta = Receta::create(['paciente_id' => $ana->id, 'medico_id' => $this->medico->id, 'fecha' => today(), 'medicamentos' => [['medicamento' => 'Paracetamol']]]);
        $html = view('pdf.receta', ['receta' => $receta->load('paciente', 'medico.especialidad', 'consulta')])->render();

        $this->assertStringContainsString('Ana Pérez', $html);
        $this->assertStringContainsString('CONFIDENCIAL', $html);
    }

    public function test_sala_de_espera_agenda_y_whatsapp_no_muestran_el_nombre_reservado(): void
    {
        Turno::create(['numero_turno' => 1, 'prefijo' => 'G', 'especialidad_id' => $this->esp->id, 'paciente_id' => $this->reservado->id,
            'nombre_temporal' => 'Rosa Quintero', 'cedula' => '0102030405', 'motivo' => 'Control', 'estado' => 'esperando', 'fecha' => today(), 'hora_registro' => now()]);
        $cita = Cita::create(['paciente_id' => $this->reservado->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->esp->id,
            'tipo' => 'Control', 'fecha_hora' => today()->setTime(15, 0), 'estado' => 'confirmada']);

        $this->actingAs($this->medico->user)->get(route('sala-espera'))
            ->assertOk()->assertSee('C.I. 0102030405')->assertDontSee('Quintero');

        $eventos = Livewire::actingAs($this->medico->user)->test(Agenda::class)
            ->instance()->eventos(today()->startOfWeek()->toIso8601String(), today()->endOfWeek()->addDay()->toIso8601String());
        $this->assertStringContainsString('C.I. 0102030405', $eventos[0]['title']);
        $this->assertStringNotContainsString('Quintero', $eventos[0]['title']);

        $mensaje = app(WhatsappService::class)->mensaje($cita->load('paciente', 'medico'));
        $this->assertStringNotContainsString('Rosa', $mensaje);
        $this->assertStringContainsString('******0405', $mensaje);
    }

    public function test_plazo_de_48_horas_para_copias_y_15_dias_para_derechos(): void
    {
        $fecha = Carbon::parse('2026-10-01 09:00');
        $this->assertEquals(Carbon::parse('2026-10-03 09:00'), SolicitudInformacion::calcularFechaLimite('copia_hc', $fecha));
        $this->assertEquals(Carbon::parse('2026-10-16 09:00'), SolicitudInformacion::calcularFechaLimite('rectificacion', $fecha));
        $this->assertEquals(Carbon::parse('2026-10-16 09:00'), SolicitudInformacion::calcularFechaLimite('acceso', $fecha));
    }

    public function test_registrar_solicitud_con_respaldo_y_marcar_vencidas(): void
    {
        Storage::fake('private');

        Livewire::actingAs($this->auditor)->test(SolicitudesInformacion::class)
            ->call('nueva')
            ->call('elegirPaciente', $this->reservado->id)
            ->assertSet('solicitante_cedula', '0102030405')
            ->set('tipo', 'copia_hc')
            ->set('fecha_solicitud', now()->subDays(3)->format('Y-m-d\TH:i'))
            ->set('documento', UploadedFile::fake()->create('solicitud.pdf', 20, 'application/pdf'))
            ->call('guardar')
            ->assertHasNoErrors();

        $s = SolicitudInformacion::sole();
        $this->assertTrue($s->vencida());
        Storage::disk('private')->assertExists($s->documento_ruta);
        $this->assertSame('solicitud', RegistroAcceso::where('accion', 'crear')->value('recurso'));

        Livewire::actingAs($this->auditor)->test(SolicitudesInformacion::class)
            ->assertSee('Fuera de plazo')
            ->call('atender', $s->id)
            ->set('resultado', 'rechazada')
            ->call('responder')
            ->assertHasErrors('respuesta')
            ->set('respuesta', 'Falta copia de cédula del representante')
            ->call('responder')
            ->assertHasNoErrors();

        $this->assertSame('rechazada', $s->fresh()->estado);
        $this->assertNotNull($s->fresh()->fecha_respuesta);
    }

    public function test_entrega_para_investigacion_exige_entidad_y_razon(): void
    {
        Livewire::actingAs($this->auditor)->test(SolicitudesInformacion::class)
            ->call('nueva')
            ->call('elegirPaciente', $this->reservado->id)
            ->set('tipo', 'uso_investigacion')
            ->set('relacion', 'investigador')
            ->set('solicitante_nombres', 'Dra. Paula Vera')
            ->set('solicitante_cedula', '0922222222')
            ->call('guardar')
            ->assertHasErrors(['entidad', 'motivo']);
    }

    public function test_permisos_de_solicitudes_e_incidentes(): void
    {
        $sec = User::factory()->create(); $sec->assignRole('secretaria');
        $this->actingAs($sec)->get(route('auditoria.solicitudes'))->assertForbidden();
        $this->actingAs($sec)->get(route('auditoria.incidentes'))->assertForbidden();

        // El médico edita pacientes: puede registrar solicitudes, pero no ve incidentes
        $this->actingAs($this->medico->user)->get(route('auditoria.solicitudes'))->assertOk();
        $this->actingAs($this->medico->user)->get(route('auditoria.incidentes'))->assertForbidden();

        $this->actingAs($this->auditor)->get(route('auditoria.incidentes'))->assertOk();
    }

    public function test_incidente_con_plazo_de_notificacion_de_5_dias(): void
    {
        Livewire::actingAs($this->auditor)->test(IncidentesSeguridad::class)
            ->call('nuevo')
            ->set('fecha_deteccion', now()->subDays(6)->format('Y-m-d\TH:i'))
            ->set('descripcion', 'Computador de recepción robado con sesión abierta')
            ->set('datos_afectados', 'Agenda de citas con nombres y teléfonos')
            ->set('pacientes_afectados', 40)
            ->set('severidad', 'alta')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Venció');

        $i = IncidenteSeguridad::sole();
        $this->assertTrue($i->notificacionVencida());

        Livewire::actingAs($this->auditor)->test(IncidentesSeguridad::class)
            ->call('editar', $i->id)
            ->set('notificado_autoridad_at', now()->format('Y-m-d\TH:i'))
            ->call('guardar');
        $this->assertSame('notificado', $i->fresh()->estado);
        $this->assertFalse($i->fresh()->notificacionVencida());

        $medico = $this->medico->user;
        Livewire::actingAs($medico)->test(IncidentesSeguridad::class)->call('nuevo')->assertForbidden();
    }

    public function test_panel_avisa_plazos_al_auditor(): void
    {
        SolicitudInformacion::create(['paciente_id' => $this->reservado->id, 'tipo' => 'acceso', 'solicitante_nombres' => 'Rosa', 'solicitante_cedula' => '0102030405',
            'relacion' => 'titular', 'fecha_solicitud' => now()->subDays(20), 'fecha_limite' => now()->subDays(5), 'estado' => 'pendiente']);

        Livewire::actingAs($this->auditor)->test(PanelPendientes::class)
            ->assertSee('1 solicitud pendiente')
            ->assertSee('1 fuera de plazo');
    }

    public function test_historia_activa_y_pasiva(): void
    {
        $viejo = Paciente::create(['nombres' => 'Pedro', 'apellidos' => 'Luna', 'cedula' => '0933333333', 'sexo' => 'masculino']);
        $viejo->forceFill(['created_at' => now()->subYears(8)])->save();
        ExpedienteConsulta::create(['paciente_id' => $viejo->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->esp->id,
            'tipo_consulta' => 'general', 'fecha' => now()->subYears(6), 'motivo_consulta' => 'Gripe', 'diagnostico' => 'Rinofaringitis']);

        $this->assertTrue($viejo->archivoPasivo());
        $this->assertFalse($this->reservado->archivoPasivo());

        $this->actingAs($this->medico->user)->get(route('pacientes.ver', $viejo))->assertSee('HC pasiva');
        $this->actingAs($this->medico->user)->get(route('pacientes.ver', $this->reservado))->assertSee('HC activa')->assertSee('Identidad reservada');
    }
}
