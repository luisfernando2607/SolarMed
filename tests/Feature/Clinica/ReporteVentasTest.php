<?php

namespace Tests\Feature\Clinica;

use App\Livewire\ReporteVentas;
use App\Models\Especialidad;
use App\Models\Factura;
use App\Models\Medico;
use App\Models\NotaCredito;
use App\Models\Paciente;
use App\Models\ServicioTarifario;
use App\Models\User;
use App\Services\FacturaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReporteVentasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
        $servicio = ServicioTarifario::create(['especialidad_id' => $esp->id, 'nombre' => 'Consulta', 'precio' => 40, 'activo' => true]);
        $medicos = collect(['0912345678001', '0998765432001'])->map(fn ($ruc, $i) => Medico::create([
            'user_id' => User::factory()->create()->id, 'nombres' => "Médico{$i}", 'apellidos' => 'Test', 'especialidad_id' => $esp->id,
            'ruc' => $ruc, 'razon_social' => "MEDICO {$i}", 'direccion_matriz' => 'Guayaquil',
        ]));

        $crear = fn (Medico $m, string $fecha, string $pago = 'efectivo', float $desc = 0) => app(FacturaService::class)->crear([
            'paciente_id' => $paciente->id, 'medico_id' => $m->id, 'especialidad_id' => $esp->id, 'user_id' => $this->admin->id,
            'fecha' => $fecha, 'forma_pago' => $pago, 'descuento' => $desc,
        ], [['servicio_id' => $servicio->id, 'cantidad' => 1]]);

        // Septiembre 2026, médico 0
        $crear($medicos[0], '2026-09-03')->update(['estado_sri' => 'autorizado']);
        $crear($medicos[0], '2026-09-10', 'transferencia', 10)->update(['estado_sri' => 'autorizado']);
        $crear($medicos[0], '2026-09-12')->update(['estado' => 'anulada']); // anulada antes de enviar al SRI
        $conNc = $crear($medicos[0], '2026-09-20');
        $conNc->update(['estado_sri' => 'autorizado', 'estado' => 'anulada']);
        NotaCredito::create([
            'factura_id' => $conNc->id, 'medico_id' => $medicos[0]->id, 'user_id' => $this->admin->id, 'numero' => '001-001-000000001',
            'ruc_emisor' => '0912345678001', 'fecha' => '2026-09-25', 'motivo' => 'Error', 'subtotal' => 40, 'valor_modificacion' => 40,
            'estado_sri' => 'autorizado',
        ]);
        // Médico 1 y otro mes
        $crear($medicos[1], '2026-09-15')->update(['estado_sri' => 'autorizado']);
        $crear($medicos[0], '2026-10-01')->update(['estado_sri' => 'autorizado']);
    }

    public function test_resumen_por_ruc_y_mes(): void
    {
        $c = Livewire::actingAs($this->admin)->test(ReporteVentas::class)
            ->set('mes', '2026-09')
            ->set('ruc', '0912345678001');

        $resumen = $c->viewData('resumen');
        $this->assertSame(3, $resumen['facturas']);           // la anulada sin SRI no cuenta
        $this->assertSame(1, $resumen['anuladas_sin_sri']);
        $this->assertEqualsWithDelta(110.0, $resumen['facturado'], 0.001);  // 40 + 30 + 40
        $this->assertEqualsWithDelta(40.0, $resumen['notas'], 0.001);
        $this->assertEqualsWithDelta(70.0, $resumen['neto'], 0.001);
        $this->assertEqualsWithDelta(40.0, $resumen['por_forma_pago']['efectivo'], 0.001);
        $this->assertEqualsWithDelta(30.0, $resumen['por_forma_pago']['transferencia'], 0.001);

        $c->set('ruc', '');
        $this->assertSame(4, $c->viewData('resumen')['facturas']);
    }

    public function test_exporta_csv_para_excel(): void
    {
        $c = Livewire::actingAs($this->admin)->test(ReporteVentas::class)
            ->set('mes', '2026-09')
            ->set('ruc', '0912345678001')
            ->call('exportar')
            ->assertFileDownloaded('ventas_2026-09_0912345678001.csv');

        $csv = base64_decode($c->effects['download']['content']);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lineas = array_values(array_filter(explode("\n", trim(substr($csv, 3)))));
        $this->assertCount(1 + 4 + 1, $lineas); // encabezado + 4 facturas + 1 NC
        $this->assertStringContainsString('Factura;0912345678001;10/09/2026;001-001-000000002;;"Ana Pérez";0102030405;30,00;10,00;0,00;30,00;transferencia', $lineas[2]);
        $this->assertStringContainsString('"Nota de crédito";0912345678001;25/09/2026;001-001-000000001;001-001-000000004', $lineas[5]);
        $this->assertStringContainsString(';-40,00;', $lineas[5]);
    }

    public function test_solo_admin(): void
    {
        $medico = User::factory()->create();
        $medico->assignRole('medico');
        $this->actingAs($medico)->get(route('reportes.ventas'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('reportes.ventas'))->assertOk();
    }
}
