<?php

namespace Tests\Feature\Clinica;

use App\Livewire\CrearReceta;
use App\Models\Cie10Code;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Receta;
use App\Models\RecetaItem;
use App\Models\Turno;
use App\Models\User;
use App\Services\AjustesService;
use App\Services\RecetaService;
use App\Support\NumeroALetras;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecetaAcessTest extends TestCase
{
    use RefreshDatabase;

    private Medico $medico;
    private Medico $otro;
    private Paciente $paciente;
    private Turno $turno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $esp = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G']);
        $u = User::factory()->create(); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $esp->id,
            'colegiatura' => 'ACESS-0912345', 'telefono' => '0998887777']);
        $u2 = User::factory()->create(); $u2->assignRole('medico');
        $this->otro = Medico::create(['user_id' => $u2->id, 'nombres' => 'Luis', 'apellidos' => 'Mora', 'especialidad_id' => $esp->id]);
        $this->paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino',
            'fecha_nacimiento' => now()->subYears(2)->subMonths(3), 'alergias' => '']);
        Cie10Code::create(['codigo' => 'J02.9', 'descripcion' => 'Faringitis aguda, no especificada', 'categoria' => 'J02', 'capitulo' => 'X']);
        app(AjustesService::class)->guardar(['establecimiento.canton' => 'Guayaquil']);

        $this->turno = Turno::create(['numero_turno' => 1, 'prefijo' => 'G', 'especialidad_id' => $esp->id, 'paciente_id' => $this->paciente->id,
            'nombre_temporal' => 'Ana Pérez', 'cedula' => '0102030405', 'estado' => 'en_atencion', 'fecha' => today(), 'hora_registro' => now()]);
        ExpedienteConsulta::create(['paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $esp->id,
            'turno_id' => $this->turno->id, 'tipo_consulta' => 'general', 'fecha' => now(), 'motivo_consulta' => 'Dolor de garganta',
            'diagnostico' => 'Faringitis aguda', 'codigo_cie10' => 'J02.9']);
    }

    private function item(array $extra = []): array
    {
        return $extra + ['dci' => 'Amoxicilina', 'forma_farmaceutica' => 'Suspensión oral', 'concentracion' => '250 mg/5 ml', 'via' => 'Oral',
            'cantidad' => 2, 'dosis' => '5 ml', 'frecuencia' => 'Cada 8 horas', 'duracion' => '7 días', 'indicacion' => null, 'antimicrobiano' => true];
    }

    private function emitir(Medico $medico, array $items = []): Receta
    {
        return app(RecetaService::class)->emitir([
            'paciente_id' => $this->paciente->id, 'medico_id' => $medico->id, 'fecha' => today(), 'ciudad' => 'Guayaquil',
            'diagnostico' => 'Faringitis aguda', 'codigo_cie10' => 'J02.9', 'alergias' => 'No refiere',
        ], $items ?: [$this->item()]);
    }

    public function test_cantidad_en_letras(): void
    {
        $casos = [0 => 'cero', 1 => 'uno', 15 => 'quince', 16 => 'dieciséis', 21 => 'veintiuno', 22 => 'veintidós', 30 => 'treinta',
            31 => 'treinta y uno', 99 => 'noventa y nueve', 100 => 'cien', 101 => 'ciento uno', 115 => 'ciento quince', 200 => 'doscientos',
            555 => 'quinientos cincuenta y cinco', 999 => 'novecientos noventa y nueve', 1000 => 'mil', 1001 => 'mil uno',
            2021 => 'dos mil veintiuno', 21000 => 'veintiún mil', 31000 => 'treinta y un mil', 9999 => 'nueve mil novecientos noventa y nueve'];
        foreach ($casos as $n => $texto) {
            $this->assertSame($texto, NumeroALetras::convertir($n), "Número {$n}");
        }
        $this->assertSame('30 (treinta)', NumeroALetras::conNumero(30));
    }

    public function test_edad_en_anios_y_meses_para_menores_de_cinco(): void
    {
        $hoy = Carbon::parse('2026-10-12');
        $this->assertSame('2 años y 3 meses', Receta::edadTexto(Carbon::parse('2024-07-01'), $hoy));
        $this->assertSame('1 año y 1 mes', Receta::edadTexto(Carbon::parse('2025-09-05'), $hoy));
        $this->assertSame('4 meses', Receta::edadTexto(Carbon::parse('2026-06-01'), $hoy));
        $this->assertSame('30 años', Receta::edadTexto(Carbon::parse('1996-01-01'), $hoy));
        $this->assertNull(Receta::edadTexto(null, $hoy));
    }

    public function test_numeracion_secuencial_y_vigencia(): void
    {
        $a = $this->emitir($this->medico);
        $b = $this->emitir($this->otro);
        $this->assertSame(1, $a->numero);
        $this->assertSame(2, $b->numero);
        $this->assertSame('000002', $b->numeroFormateado());
        $this->assertSame(3, $a->vigencia_dias);
        $this->assertSame(today()->addDays(3)->toDateString(), $a->validaHasta()->toDateString());
        // Copia en texto para las pantallas que listan recetas
        $this->assertSame('Amoxicilina 250 mg/5 ml', $a->medicamentos[0]['medicamento']);
    }

    public function test_crear_desde_la_atencion_con_datos_prellenados(): void
    {
        $c = Livewire::actingAs($this->medico->user)->test(CrearReceta::class, ['turno' => $this->turno])
            ->assertSet('diagnostico', 'Faringitis aguda')
            ->assertSet('codigo_cie10', 'J02.9')
            ->assertSet('alergias', 'No refiere')
            ->assertSet('ciudad', 'Guayaquil');

        // Campos obligatorios del Art. 5
        $c->call('guardar')->assertHasErrors(['items.0.dci', 'items.0.concentracion', 'items.0.forma_farmaceutica', 'items.0.cantidad',
            'items.0.dosis', 'items.0.frecuencia', 'items.0.duracion']);

        foreach ($this->item() as $campo => $valor) {
            $c->set("items.0.{$campo}", $valor);
        }
        $c->set('codigo_cie10', 'XYZ')->call('guardar')->assertHasErrors('codigo_cie10');
        $c->set('codigo_cie10', 'J02.9')->set('signos_alarma', 'Fiebre mayor a 39 °C')->call('guardar')->assertHasNoErrors();

        $receta = Receta::sole();
        $this->assertSame(1, $receta->numero);
        $this->assertCount(1, $receta->items);
        $this->assertTrue($receta->items[0]->antimicrobiano);
        $this->assertNotNull($receta->consulta_id);
    }

    public function test_aviso_por_siglas_o_concentracion_en_el_dci(): void
    {
        $c = Livewire::actingAs($this->medico->user)->test(CrearReceta::class, ['turno' => $this->turno]);
        $c->set('items.0.dci', 'ASA');
        $this->assertArrayHasKey(0, $c->instance()->avisos);
        $c->set('items.0.dci', 'Ibuprofeno 400 mg');
        $this->assertStringContainsString('concentración', $c->instance()->avisos[0]);
        $c->set('items.0.dci', 'Ácido acetilsalicílico');
        $this->assertSame([], $c->instance()->avisos);
    }

    public function test_sugerencias_solo_con_los_medicamentos_propios(): void
    {
        $this->emitir($this->medico, [$this->item(), $this->item()]);
        $ajena = $this->emitir($this->otro, [$this->item(['dci' => 'Losartán', 'concentracion' => '50 mg', 'forma_farmaceutica' => 'Tableta'])]);

        $c = Livewire::actingAs($this->medico->user)->test(CrearReceta::class, ['turno' => $this->turno]);
        $sugeridos = $c->instance()->sugeridos;
        $this->assertCount(1, $sugeridos);
        $this->assertSame('Amoxicilina', $sugeridos[0]['dci']);

        // No puede copiar un medicamento recetado por otro médico
        $c->call('usarSugerido', 0, $ajena->items[0]->id)->assertSet('items.0.dci', '');
        $c->call('usarSugerido', 0, $sugeridos[0]['id'])->assertSet('items.0.dci', 'Amoxicilina')->assertSet('items.0.concentracion', '250 mg/5 ml');
    }

    public function test_pdf_con_los_datos_del_articulo_5(): void
    {
        $receta = $this->emitir($this->medico, [$this->item(['cantidad' => 30, 'indicacion' => 'Agitar antes de usar'])]);
        $receta->update(['signos_alarma' => 'Dificultad para respirar', 'recomendaciones' => 'Abundantes líquidos']);
        $html = view('pdf.receta', ['receta' => $receta->fresh()->load('paciente', 'medico.especialidad', 'consulta', 'items')])->render();

        foreach (['CONFIDENCIAL', 'N.º 000001', 'Guayaquil, ' . today()->format('d/m/Y'), 'Válida hasta', today()->addDays(3)->format('d/m/Y'),
            'Pérez Ana', '2 años y 3 meses', 'J02.9', 'No refiere', 'Amoxicilina', '250 mg/5 ml', 'Suspensión oral', 'Oral', '30 (treinta)',
            '5 ml', 'Cada 8 horas', '7 días', 'Registro ACESS: ACESS-0912345', 'INDICACIONES', 'Dificultad para respirar', 'Abundantes líquidos',
            '0998887777', 'Agitar antes de usar'] as $texto) {
            $this->assertStringContainsString($texto, $html, "Falta: {$texto}");
        }

        $this->actingAs($this->medico->user)->get(route('recetas.pdf', $receta))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_receta_anterior_se_sigue_imprimiendo(): void
    {
        $legacy = Receta::create(['paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'fecha' => today(),
            'medicamentos' => [['medicamento' => 'Paracetamol', 'presentacion' => 'Jarabe', 'dosis' => '5 ml']]]);
        $this->assertFalse($legacy->estructurada());
        $this->assertSame('Paracetamol', $legacy->resumen());

        $html = view('pdf.receta', ['receta' => $legacy->load('paciente', 'medico.especialidad', 'consulta', 'items')])->render();
        $this->assertStringContainsString('Paracetamol', $html);
        $this->assertStringContainsString('Rp/', $html);

        $this->actingAs($this->medico->user)->get(route('recetas.pdf', $legacy))->assertOk();
        $this->actingAs($this->medico->user)->get(route('pacientes.ver', $this->paciente))->assertOk()->assertSee('Paracetamol');
    }

    public function test_prescriptor_registra_su_registro_acess_y_telefono(): void
    {
        $admin = User::factory()->create(); $admin->assignRole('admin');
        Livewire::actingAs($admin)->test(\App\Livewire\GestionUsuarios::class)
            ->call('editar', $this->otro->user_id)
            ->set('colegiatura', 'ACESS-777')->set('telefono_medico', '0991112222')
            ->call('guardar')->assertHasNoErrors();

        $this->assertSame('ACESS-777', $this->otro->fresh()->colegiatura);
        $this->assertSame('0991112222', $this->otro->fresh()->telefono);
    }
}
