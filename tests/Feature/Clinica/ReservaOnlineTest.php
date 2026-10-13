<?php

namespace Tests\Feature\Clinica;

use App\Livewire\PanelPendientes;
use App\Livewire\ReservaCita;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\RegistroAcceso;
use App\Models\User;
use App\Services\AjustesService;
use App\Services\ReservaOnlineService;
use App\Support\CedulaEcuador;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ReservaOnlineTest extends TestCase
{
    use RefreshDatabase;

    // CEDULA_1 y CEDULA_2 tienen dígito verificador correcto; CEDULA_3 solo se usa para pacientes creados por el personal
    private const CEDULA_1 = '1710034065';
    private const CEDULA_2 = '0926687856';
    private const CEDULA_3 = '0102030405';

    private Especialidad $esp;
    private Medico $medico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        // Lunes 10:00: los horarios de hoy desde las 12:00 (2 h de anticipación)
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00'));
        app(AjustesService::class)->guardar([
            'agenda.hora_inicio' => '08:00', 'agenda.hora_fin' => '13:00', 'agenda.duracion_defecto' => 30,
            'agenda.dias_laborables' => [1, 2, 3, 4, 5], 'agenda.reserva_dias' => 7, 'agenda.reserva_anticipacion_horas' => 2,
        ]);
        $this->esp = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G', 'motivos' => ['Consulta general', 'Control']]);
        $u = User::factory()->create(); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'Luis', 'apellidos' => 'Mora', 'especialidad_id' => $this->esp->id, 'activo' => true]);
        RateLimiter::clear('reserva-online:127.0.0.1');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function datos(array $extra = []): array
    {
        return $extra + [
            'motivo' => 'Consulta general', 'cedula' => self::CEDULA_1, 'nombres' => 'Ana', 'apellidos' => 'Pérez',
            'telefono' => '0991234567', 'email' => '', 'sexo' => 'femenino', 'fecha_nacimiento' => '1990-05-01',
        ];
    }

    private function reservar(string $fecha, string $hora, array $extra = [])
    {
        $c = Livewire::test(ReservaCita::class)
            ->call('elegirEspecialidad', $this->esp->id)
            ->call('elegirMedico', (string) $this->medico->id)
            ->call('elegirFecha', $fecha)
            ->call('elegirHora', $hora);
        foreach ($this->datos($extra) as $k => $v) {
            $c->set($k, $v);
        }

        return $c->call('reservar');
    }

    public function test_cedula_ecuatoriana(): void
    {
        $this->assertTrue(CedulaEcuador::valida(self::CEDULA_1));
        $this->assertTrue(CedulaEcuador::valida(self::CEDULA_2));
        $this->assertFalse(CedulaEcuador::valida('1710034064'));
        $this->assertFalse(CedulaEcuador::valida('2510034065'));
        $this->assertFalse(CedulaEcuador::valida('171003406'));
        $this->assertFalse(CedulaEcuador::valida('17100340a5'));
    }

    public function test_horarios_excluyen_pasado_ocupado_y_dias_no_laborables(): void
    {
        $s = app(ReservaOnlineService::class);

        // Hoy lunes: antes de las 12:00 no hay (anticipación de 2 h)
        $this->assertSame(['12:00', '12:30'], array_keys($s->horarios('2026-11-02', $this->esp->id)));
        // Sábado y domingo no son laborables
        $this->assertNotContains('2026-11-07', $s->fechas());
        $this->assertSame([], $s->horarios('2026-11-08', $this->esp->id));
        // Fuera de la ventana de 7 días
        $this->assertSame([], $s->horarios('2026-11-20', $this->esp->id));

        Cita::create(['paciente_id' => Paciente::create(['nombres' => 'X', 'apellidos' => 'Y', 'cedula' => self::CEDULA_3, 'sexo' => 'masculino'])->id,
            'medico_id' => $this->medico->id, 'especialidad_id' => $this->esp->id, 'tipo' => 'Control',
            'fecha_hora' => '2026-11-03 09:15', 'duracion_min' => 30, 'estado' => 'confirmada']);
        $martes = array_keys($s->horarios('2026-11-03', $this->esp->id));
        $this->assertNotContains('09:00', $martes);
        $this->assertNotContains('09:30', $martes);
        $this->assertContains('10:00', $martes);
        $this->assertSame(10, count($martes) + 2);
    }

    public function test_reserva_crea_cita_pendiente_y_paciente_nuevo(): void
    {
        $this->reservar('2026-11-03', '09:00')->assertHasNoErrors()->assertSet('paso', 5)->assertSee('Reserva recibida');

        $cita = Cita::sole();
        $this->assertSame('pendiente', $cita->estado);
        $this->assertSame('web', $cita->origen);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $cita->codigo_reserva);
        $this->assertSame('2026-11-03 09:00', $cita->fecha_hora->format('Y-m-d H:i'));
        $this->assertSame('Ana Pérez', $cita->paciente->nombre_completo);
        $this->assertSame('crear', RegistroAcceso::latest('id')->value('accion'));
        $this->assertNull(RegistroAcceso::latest('id')->value('user_id'));
    }

    public function test_paciente_existente_no_se_modifica_y_no_se_revela(): void
    {
        $p = Paciente::create(['nombres' => 'María', 'apellidos' => 'Villacís', 'cedula' => self::CEDULA_1, 'sexo' => 'femenino',
            'telefono' => '0987654321', 'email' => 'maria@correo.ec', 'alergias' => 'Penicilina']);

        $c = $this->reservar('2026-11-03', '09:00', ['nombres' => 'Otra', 'apellidos' => 'Persona', 'telefono' => '0990000000', 'email' => 'x@y.ec']);
        $c->assertHasNoErrors()->assertDontSee('María')->assertDontSee('Villacís')->assertDontSee('Penicilina')->assertDontSee('0987654321');

        $p->refresh();
        $this->assertSame('María', $p->nombres);
        $this->assertSame('0987654321', $p->telefono);
        $this->assertSame('maria@correo.ec', $p->email);
        $cita = Cita::sole();
        $this->assertSame($p->id, $cita->paciente_id);
        $this->assertStringContainsString('0990000000', $cita->notas);
        $this->assertStringContainsString('Otra Persona', $cita->notas);
    }

    public function test_horario_tomado_mientras_llena_los_datos(): void
    {
        $c = Livewire::test(ReservaCita::class)
            ->call('elegirEspecialidad', $this->esp->id)->call('elegirMedico', '')
            ->call('elegirFecha', '2026-11-03')->call('elegirHora', '09:00');
        foreach ($this->datos() as $k => $v) {
            $c->set($k, $v);
        }
        Cita::create(['paciente_id' => Paciente::create(['nombres' => 'X', 'apellidos' => 'Y', 'cedula' => self::CEDULA_3, 'sexo' => 'masculino'])->id,
            'medico_id' => $this->medico->id, 'especialidad_id' => $this->esp->id, 'tipo' => 'Control',
            'fecha_hora' => '2026-11-03 09:00', 'duracion_min' => 30, 'estado' => 'pendiente']);

        $c->call('reservar')->assertHasErrors('hora')->assertSet('paso', 3)->assertSee('acaba de ser reservado');
        $this->assertSame(1, Cita::count());
    }

    public function test_limites_por_cedula_ip_y_trampa(): void
    {
        $this->reservar('2026-11-03', '09:00')->assertHasNoErrors();
        $this->reservar('2026-11-03', '10:00')->assertHasNoErrors();
        $this->reservar('2026-11-04', '09:00')->assertHasErrors('cedula');
        $this->assertSame(2, Cita::count());

        $this->reservar('2026-11-04', '09:00', ['cedula' => self::CEDULA_2, 'sitio_web' => 'spam'])->assertHasErrors('cedula');
        $this->assertSame(2, Cita::count());

        RateLimiter::clear('reserva-online:127.0.0.1');
        foreach (range(1, ReservaCita::RESERVAS_POR_HORA) as $i) {
            RateLimiter::hit('reserva-online:127.0.0.1', 3600);
        }
        $this->reservar('2026-11-05', '09:00', ['cedula' => self::CEDULA_2])->assertHasErrors('cedula');
        $this->assertSame(2, Cita::count());
    }

    public function test_validaciones_de_datos(): void
    {
        $this->reservar('2026-11-03', '09:00', ['cedula' => '1710034064', 'telefono' => '022345678', 'motivo' => 'Inventado'])
            ->assertHasErrors(['cedula', 'telefono', 'motivo']);
        $this->assertSame(0, Cita::count());
    }

    public function test_desactivada_y_aviso_al_personal(): void
    {
        $this->get(route('agendar'))->assertOk()->assertSee('Agendar cita')->assertSee('Medicina General');
        $this->get(route('home'))->assertSee(route('agendar'));

        $this->reservar('2026-11-03', '09:00')->assertHasNoErrors();
        $sec = User::factory()->create(); $sec->assignRole('secretaria');
        Livewire::actingAs($sec)->test(PanelPendientes::class)->assertSee('1 cita en línea por confirmar');

        app(AjustesService::class)->guardar(['agenda.reserva_online' => false]);
        $this->get(route('agendar'))->assertOk()->assertSee('no está disponible');
        $this->get(route('home'))->assertDontSee(route('agendar'));
    }
}
