<?php

namespace Tests\Feature\Clinica;

use App\Livewire\Caja;
use App\Models\CierreCaja;
use App\Models\Especialidad;
use App\Models\Factura;
use App\Models\Medico;
use App\Models\NotaCredito;
use App\Models\Paciente;
use App\Models\RegistroAcceso;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class CierreCajaTest extends TestCase
{
    use RefreshDatabase;

    private User $recepcion;
    private User $admin;
    private Medico $medico;
    private Paciente $paciente;
    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->recepcion = User::factory()->create(['name' => 'Rosa Recepción']); $this->recepcion->assignRole('secretaria');
        $this->admin = User::factory()->create(); $this->admin->assignRole('admin');
        $esp = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G']);
        $u = User::factory()->create(); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'Luis', 'apellidos' => 'Mora', 'especialidad_id' => $esp->id]);
        $this->paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
    }

    private function factura(float $total, string $forma = 'efectivo', string $estado = 'pagada'): Factura
    {
        $this->n++;

        return Factura::create([
            'numero_factura' => '001-001-' . str_pad((string) $this->n, 9, '0', STR_PAD_LEFT),
            'paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->medico->especialidad_id,
            'user_id' => $this->recepcion->id, 'fecha' => now(), 'subtotal' => $total, 'descuento' => 0, 'total' => $total,
            'forma_pago' => $forma, 'estado' => $estado,
        ]);
    }

    private function cerrar(float $fondo, float $contado, string $obs = '')
    {
        return Livewire::actingAs($this->recepcion)->test(Caja::class)
            ->set('fondo_inicial', $fondo)->set('contado_efectivo', (string) $contado)->set('observaciones', $obs)
            ->call('cerrar');
    }

    public function test_totales_por_forma_de_pago_y_diferencia(): void
    {
        $this->factura(30);
        $this->factura(20);
        $this->factura(45, 'transferencia');
        $this->factura(15, 'tarjeta');
        $this->factura(99, 'efectivo', 'anulada');

        Livewire::actingAs($this->recepcion)->test(Caja::class)
            ->assertViewHas('periodo', fn ($p) => $p['cobrado'] === ['efectivo' => 50.0, 'transferencia' => 45.0, 'tarjeta' => 15.0]
                && $p['pagadas'] === 4 && $p['anuladas'] === 1);

        $this->cerrar(20, 68)->assertHasNoErrors();

        $c = CierreCaja::sole();
        $this->assertSame(70.0, $c->efectivo_esperado);   // 20 de fondo + 50 en efectivo
        $this->assertSame(-2.0, $c->diferencia);          // faltan 2 dólares
        $this->assertSame(110.0, $c->totalCobrado());
        $this->assertSame(4, $c->facturas_count);
        $this->assertSame(1, $c->anuladas_count);
        $this->assertSame(5, Factura::where('cierre_id', $c->id)->count());
        $this->assertSame('crear', RegistroAcceso::latest('id')->value('accion'));
    }

    public function test_cada_factura_entra_en_un_solo_cierre_y_las_devoluciones_se_restan(): void
    {
        $primera = $this->factura(40);
        $this->cerrar(0, 40);

        $this->factura(25);
        NotaCredito::create(['factura_id' => $primera->id, 'medico_id' => $this->medico->id, 'user_id' => $this->recepcion->id,
            'numero' => '001-001-000000001', 'fecha' => today(), 'motivo' => 'Devolución', 'subtotal' => 40, 'valor_modificacion' => 40]);

        Livewire::actingAs($this->recepcion)->test(Caja::class)
            ->assertViewHas('periodo', fn ($p) => $p['cobrado']['efectivo'] === 25.0 && $p['devoluciones']['efectivo'] === 40.0 && $p['efectivo_neto'] === -15.0);

        $this->cerrar(50, 35)->assertHasNoErrors();
        $segundo = CierreCaja::latest('id')->first();
        $this->assertSame(35.0, $segundo->efectivo_esperado);
        $this->assertSame(0.0, $segundo->diferencia);
        $this->assertSame(1, Factura::where('cierre_id', $segundo->id)->count());

        // Nada queda pendiente: un tercer cierre sin movimientos se rechaza
        $this->cerrar(0, 0)->assertHasErrors('contado_efectivo');
    }

    public function test_contador_de_billetes_y_monedas(): void
    {
        Livewire::actingAs($this->recepcion)->test(Caja::class)
            ->set('billetes.b20', 2)->set('billetes.b1', 3)->set('billetes.m25', 2)
            ->assertSet('contado_efectivo', '43.50');
    }

    public function test_inmutable_y_reapertura_con_constancia(): void
    {
        $this->factura(30);
        $this->cerrar(0, 30);
        $c = CierreCaja::sole();

        $this->assertThrows(fn () => $c->update(['contado_efectivo' => 999]), LogicException::class);
        $this->assertThrows(fn () => $c->delete(), LogicException::class);

        Livewire::actingAs($this->recepcion)->test(Caja::class)->call('pedirReapertura', $c->id)->assertForbidden();

        Livewire::actingAs($this->admin)->test(Caja::class)
            ->call('pedirReapertura', $c->id)->call('reabrir')->assertHasErrors('motivoReapertura')
            ->set('motivoReapertura', 'Forma de pago equivocada')->call('reabrir')->assertHasNoErrors();

        $c->refresh();
        $this->assertSame('reabierto', $c->estado);
        $this->assertSame(30.0, $c->contado_efectivo);
        $this->assertSame(1, count($c->detalle['facturas']));
        $this->assertSame(0, Factura::whereNotNull('cierre_id')->count());

        $this->cerrar(0, 30)->assertHasNoErrors();
        $this->assertSame(2, CierreCaja::count());
        $this->assertSame(1, CierreCaja::where('estado', 'cerrado')->count());
    }

    public function test_permisos_acta_y_exportacion(): void
    {
        $this->factura(30);
        $this->cerrar(0, 30);
        $c = CierreCaja::sole();

        $medico = $this->medico->user;
        Livewire::actingAs($medico)->test(Caja::class)->set('contado_efectivo', '1')->call('cerrar')->assertForbidden();

        $enf = User::factory()->create(); $enf->assignRole('enfermeria');
        $this->actingAs($enf)->get(route('caja'))->assertForbidden();
        $this->actingAs($enf)->get(route('caja.acta', $c))->assertForbidden();

        $this->actingAs($this->recepcion)->get(route('caja'))->assertOk()->assertSee('Cierre de caja');
        $pdf = $this->actingAs($this->recepcion)->get(route('caja.acta', $c))->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('Content-Type'));

        $csv = $this->actingAs($this->recepcion)->get(route('caja.exportar'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Rosa Recepción', $csv);
    }

    public function test_aviso_de_caja_sin_cerrar(): void
    {
        $f = $this->factura(30);
        $f->forceFill(['created_at' => now()->subDay()])->save();

        $this->actingAs($this->recepcion)->get(route('dashboard'))->assertSee('Caja sin cerrar desde');
    }
}
