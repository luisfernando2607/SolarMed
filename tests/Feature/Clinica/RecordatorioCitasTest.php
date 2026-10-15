<?php

namespace Tests\Feature\Clinica;

use App\Livewire\Agenda;
use App\Mail\RecordatorioCitaMail;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RecordatorioCitasTest extends TestCase
{
    use RefreshDatabase;

    private Medico $medico;

    protected function setUp(): void
    {
        parent::setUp();
        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $this->medico = Medico::create([
            'user_id' => User::factory()->create()->id, 'nombres' => 'Carlos', 'apellidos' => 'García',
            'especialidad_id' => $esp->id, 'telefono' => '042345678', 'direccion_matriz' => 'Av. 9 de Octubre 100',
        ]);
    }

    private function cita(?string $email, $fecha, string $estado = 'pendiente'): Cita
    {
        $paciente = Paciente::create([
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => (string) random_int(1000000000, 1999999999),
            'sexo' => 'femenino', 'email' => $email,
        ]);
        return Cita::create([
            'paciente_id' => $paciente->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->medico->especialidad_id,
            'tipo' => 'Control prenatal', 'fecha_hora' => $fecha, 'duracion_min' => 20, 'estado' => $estado,
        ]);
    }

    public function test_envia_solo_a_citas_de_manana_activas_con_correo_y_una_sola_vez(): void
    {
        Mail::fake();
        $manana = today()->addDay()->setTime(10, 0);

        $ok = $this->cita('ana@example.com', $manana);
        $this->cita('confirmada@example.com', $manana, 'confirmada');
        $this->cita('cancelada@example.com', $manana, 'cancelada');
        $this->cita(null, $manana);
        $this->cita('pasado@example.com', today()->addDays(2)->setTime(10, 0));

        $this->artisan('citas:recordatorios')->expectsOutput('Recordatorios enviados: 2 por correo, 0 por WhatsApp')->assertSuccessful();
        Mail::assertQueued(RecordatorioCitaMail::class, 2);
        Mail::assertQueued(RecordatorioCitaMail::class, fn ($m) => $m->hasTo('ana@example.com'));
        $this->assertNotNull($ok->fresh()->recordatorio_enviado_at);

        $this->artisan('citas:recordatorios')->expectsOutput('Recordatorios enviados: 0 por correo, 0 por WhatsApp');
        Mail::assertQueued(RecordatorioCitaMail::class, 2);
    }

    public function test_el_correo_incluye_fecha_hora_medico_y_telefono(): void
    {
        $cita = $this->cita('ana@example.com', today()->addDay()->setTime(10, 30));
        $html = (new RecordatorioCitaMail($cita))->render();

        $this->assertStringContainsString('10:30', $html);
        $this->assertStringContainsString('Dr. Carlos García', $html);
        $this->assertStringContainsString('042345678', $html);
        $this->assertStringContainsString('Av. 9 de Octubre 100', $html);
    }

    public function test_reprogramar_a_otro_dia_vuelve_a_enviar_recordatorio(): void
    {
        $this->seed(RolesSeeder::class);
        $secretaria = User::factory()->create();
        $secretaria->assignRole('secretaria');
        $cita = $this->cita('ana@example.com', today()->addDay()->setTime(10, 0));
        $cita->update(['recordatorio_enviado_at' => now()]);

        $c = Livewire::actingAs($secretaria)->test(Agenda::class);
        $c->instance()->mover($cita->id, today()->addDay()->setTime(11, 0)->toIso8601String());
        $this->assertNotNull($cita->fresh()->recordatorio_enviado_at, 'Mismo día: no se reenvía');

        $c->instance()->mover($cita->id, today()->addDays(3)->setTime(11, 0)->toIso8601String());
        $this->assertNull($cita->fresh()->recordatorio_enviado_at);
    }
}
