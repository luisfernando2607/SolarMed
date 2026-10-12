<?php

namespace Tests\Feature\Clinica;

use App\Exceptions\TurnoException;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Services\TurnoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TurnoServiceTest extends TestCase
{
    use RefreshDatabase;

    private Especialidad $especialidad;

    protected function setUp(): void
    {
        parent::setUp();
        $this->especialidad = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general']);
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino',
            'telefono' => '0999999999', 'especialidad_id' => $this->especialidad->id, 'motivo' => 'consulta_general',
        ], $extra);
    }

    public function test_formulario_publico_no_sobrescribe_datos_de_paciente_existente(): void
    {
        $paciente = Paciente::create([
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino',
            'telefono' => '0988888888', 'alergias' => 'Penicilina',
        ]);

        app(TurnoService::class)->registrarDesdeQR($this->datos([
            'nombres' => 'Impostor', 'telefono' => '0911111111', 'alergias' => '', 'ciudad' => 'Cuenca',
        ]));

        $paciente->refresh();
        $this->assertSame('Ana', $paciente->nombres);
        $this->assertSame('0988888888', $paciente->telefono);
        $this->assertSame('Penicilina', $paciente->alergias);
        $this->assertSame('Cuenca', $paciente->ciudad); // los campos vacíos sí se completan
    }

    public function test_recepcion_si_actualiza_datos_del_paciente(): void
    {
        Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino', 'telefono' => '0988888888']);

        app(TurnoService::class)->registrarDesdeQR($this->datos(['telefono' => '0911111111']), sobrescribirPaciente: true);

        $this->assertSame('0911111111', Paciente::first()->telefono);
    }

    public function test_numeracion_y_limite_diario(): void
    {
        $svc = app(TurnoService::class);
        $this->assertSame(1, $svc->registrarDesdeQR($this->datos())->numero_turno);
        $this->assertSame(2, $svc->registrarDesdeQR($this->datos())->numero_turno);
        $this->assertSame(3, $svc->registrarDesdeQR($this->datos())->numero_turno);

        $this->expectException(TurnoException::class);
        $svc->registrarDesdeQR($this->datos());
    }
}
