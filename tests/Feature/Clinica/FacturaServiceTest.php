<?php

namespace Tests\Feature\Clinica;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\ServicioTarifario;
use App\Models\User;
use App\Services\FacturaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FacturaServiceTest extends TestCase
{
    use RefreshDatabase;

    private array $cabecera;
    private ServicioTarifario $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $especialidad = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general']);
        $user = User::factory()->create();
        $medico = Medico::create([
            'user_id' => $user->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $especialidad->id,
        ]);
        $paciente = Paciente::create([
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino',
        ]);
        $this->servicio = ServicioTarifario::create([
            'especialidad_id' => $especialidad->id, 'nombre' => 'Consulta general', 'precio' => 25, 'activo' => true,
        ]);

        $this->cabecera = [
            'paciente_id' => $paciente->id,
            'medico_id' => $medico->id,
            'especialidad_id' => $especialidad->id,
            'user_id' => $user->id,
            'fecha' => now(),
            'forma_pago' => 'efectivo',
        ];
    }

    public function test_usa_el_precio_del_tarifario_aunque_el_cliente_envie_otro(): void
    {
        $factura = app(FacturaService::class)->crear($this->cabecera, [[
            'servicio_id' => $this->servicio->id,
            'descripcion' => 'Consulta general',
            'cantidad' => 2,
            'precio_unitario' => 0.01,
            'subtotal' => 0.02,
        ]]);

        $this->assertEquals(50.00, (float) $factura->subtotal);
        $this->assertEquals(50.00, (float) $factura->total);
        $this->assertEquals(25.00, (float) $factura->items->first()->precio_unitario);
        $this->assertSame('001-001-000000001', $factura->numero_factura);
    }

    public function test_rechaza_descuento_mayor_al_subtotal(): void
    {
        $this->expectException(ValidationException::class);

        app(FacturaService::class)->crear(
            $this->cabecera + ['descuento' => 100],
            [['servicio_id' => $this->servicio->id, 'cantidad' => 1]]
        );
    }

    public function test_numeracion_secuencial(): void
    {
        $items = [['servicio_id' => $this->servicio->id, 'cantidad' => 1]];
        app(FacturaService::class)->crear($this->cabecera, $items);
        $segunda = app(FacturaService::class)->crear($this->cabecera, $items);

        $this->assertSame('001-001-000000002', $segunda->numero_factura);
    }
}
