<?php

namespace Tests\Feature\Clinica;

use App\Livewire\DetalleProducto;
use App\Livewire\ListaInventario;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\RegistroAcceso;
use App\Models\User;
use App\Services\EstadoSistemaService;
use App\Services\InventarioService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    private User $enfermera;
    private Producto $paracetamol;
    private InventarioService $inv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->enfermera = User::factory()->create(); $this->enfermera->assignRole('enfermeria');
        $this->actingAs($this->enfermera);
        $this->inv = app(InventarioService::class);
        $this->paracetamol = Producto::create(['nombre' => 'Paracetamol', 'tipo' => 'medicamento', 'concentracion' => '500 mg',
            'forma_farmaceutica' => 'tableta', 'unidad' => 'tableta', 'stock_minimo' => 20]);
    }

    private function lote(string $codigo, ?string $vence, int $cantidad): Lote
    {
        return $this->inv->entrada($this->paracetamol, ['codigo_lote' => $codigo, 'fecha_vencimiento' => $vence, 'cantidad' => $cantidad]);
    }

    public function test_salida_fefo_entre_lotes_y_nunca_usa_vencidos(): void
    {
        $vencido = $this->lote('V-1', now()->subDay()->toDateString(), 50);
        $tarde = $this->lote('L-TARDE', now()->addYear()->toDateString(), 30);
        $pronto = $this->lote('L-PRONTO', now()->addMonth()->toDateString(), 10);

        $this->assertSame(40, $this->inv->stockDisponible($this->paracetamol));

        $movs = $this->inv->salida($this->paracetamol, 15, 'uso_atencion');
        $this->assertCount(2, $movs);
        $this->assertSame(0, $pronto->fresh()->cantidad);   // primero el que vence antes
        $this->assertSame(25, $tarde->fresh()->cantidad);
        $this->assertSame(50, $vencido->fresh()->cantidad); // el vencido no se toca
        $this->assertSame([10, 5], $movs->pluck('cantidad')->all());
        $this->assertSame('salida', RegistroAcceso::latest('id')->value('accion'));
    }

    public function test_no_permite_stock_negativo(): void
    {
        $this->lote('V-1', now()->subDay()->toDateString(), 100);
        $this->lote('L-1', now()->addYear()->toDateString(), 5);

        try {
            $this->inv->salida($this->paracetamol, 6, 'uso_atencion');
            $this->fail('Debió rechazar la salida');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Solo hay 5', $e->errors()['cantidad'][0]);
        }
        $this->assertSame(5, Lote::where('codigo_lote', 'L-1')->value('cantidad'));
        $this->assertSame(2, MovimientoInventario::count()); // solo las dos entradas
    }

    public function test_entrada_suma_al_mismo_lote_ajuste_y_baja(): void
    {
        $vence = now()->addMonths(6)->toDateString();
        $lote = $this->lote('A-1', $vence, 10);
        $this->assertSame($lote->id, $this->lote('A-1', $vence, 5)->id);
        $this->assertSame(15, $lote->fresh()->cantidad);

        $ajuste = $this->inv->ajuste($lote, 12, 'Conteo mensual');
        $this->assertSame('conteo_menos', $ajuste->motivo);
        $this->assertSame(3, $ajuste->cantidad);
        $this->assertSame(12, $lote->fresh()->cantidad);
        $this->assertThrows(fn () => $this->inv->ajuste($lote, 12, 'Otra vez'), ValidationException::class);

        $baja = $this->inv->baja($lote, 'danado', 2, 'Blíster roto');
        $this->assertSame(10, $baja->saldo_lote_despues);
        $this->assertThrows(fn () => $this->inv->baja($lote, 'vencido', 50), ValidationException::class);
    }

    public function test_movimientos_son_inmutables(): void
    {
        $this->lote('A-1', null, 10);
        $m = MovimientoInventario::sole();
        $this->assertThrows(fn () => $m->update(['cantidad' => 99]), LogicException::class);
        $this->assertThrows(fn () => $m->delete(), LogicException::class);
    }

    public function test_alertas_en_panel_y_estado_del_sistema(): void
    {
        $this->lote('V-1', now()->subDay()->toDateString(), 3);
        $this->lote('P-1', now()->addDays(20)->toDateString(), 4);   // 4 < mínimo 20 y por vencer

        $this->assertSame(['stock_bajo' => 1, 'por_vencer' => 1, 'vencidos' => 1], $this->inv->alertas());
        $this->assertSame('error', app(EstadoSistemaService::class)->chequeos()['inventario']['estado']);

        $this->get(route('dashboard'))->assertSee('con stock bajo')->assertSee('lote vencido');
        Livewire::test(ListaInventario::class)->set('filtro', 'vencidos')->assertSee('Paracetamol')->assertSee('3 vencidas');
    }

    public function test_pantallas_y_formularios(): void
    {
        $this->get(route('inventario'))->assertOk()->assertSee('Insumos y medicamentos');

        Livewire::test(ListaInventario::class)->call('nuevoProducto')
            ->set('form.nombre', 'Guantes de nitrilo M')->set('form.tipo', 'insumo')->set('form.unidad', 'par')
            ->call('guardarProducto')->assertHasNoErrors();
        $guantes = Producto::where('nombre', 'Guantes de nitrilo M')->firstOrFail();

        $paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
        Livewire::test(DetalleProducto::class, ['producto' => $this->paracetamol])
            ->call('abrir', 'entrada')->set('entrada.codigo_lote', 'X1')->set('entrada.cantidad', 10)
            ->call('guardar')->assertHasErrors('entrada.fecha_vencimiento')       // medicamento exige vencimiento
            ->set('entrada.fecha_vencimiento', now()->addYear()->toDateString())->call('guardar')->assertHasNoErrors()
            ->call('abrir', 'salida')->set('salida.cantidad', 11)->call('guardar')->assertHasErrors('salida.cantidad')
            ->set('salida.cantidad', 4)->call('elegirPaciente', $paciente->id)->call('guardar')->assertHasNoErrors();

        $this->assertSame(6, $this->inv->stockDisponible($this->paracetamol));
        $this->assertSame($paciente->id, MovimientoInventario::where('tipo', 'salida')->value('paciente_id'));

        Livewire::test(DetalleProducto::class, ['producto' => $guantes])
            ->call('abrir', 'entrada')->set('entrada.codigo_lote', 'G1')->set('entrada.cantidad', 100)
            ->call('guardar')->assertHasNoErrors();
    }

    public function test_permisos(): void
    {
        $sec = User::factory()->create(); $sec->assignRole('secretaria');
        $this->actingAs($sec)->get(route('inventario'))->assertForbidden();

        $medico = User::factory()->create(); $medico->assignRole('medico');
        $this->actingAs($medico)->get(route('inventario.producto', $this->paracetamol))->assertOk()->assertDontSee('+ Entrada');
        Livewire::actingAs($medico)->test(DetalleProducto::class, ['producto' => $this->paracetamol])->call('abrir', 'entrada')->assertForbidden();
        Livewire::actingAs($medico)->test(ListaInventario::class)->call('nuevoProducto')->assertForbidden();
    }

    public function test_exportaciones_csv_protegidas(): void
    {
        $this->inv->entrada($this->paracetamol, ['codigo_lote' => '=HYPERLINK("x")', 'fecha_vencimiento' => now()->addYear()->toDateString(),
            'cantidad' => 5, 'proveedor' => '+malicioso']);

        $csv = $this->get(route('inventario.exportar'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'+malicioso", $csv);

        $kardex = $this->get(route('inventario.kardex', $this->paracetamol))->assertOk()->streamedContent();
        $this->assertStringContainsString('Compra', $kardex);
        $this->assertSame('exportar', RegistroAcceso::latest('id')->value('accion'));
    }
}
