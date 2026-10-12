<?php

namespace Tests\Feature\Clinica;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use App\Services\AjustesService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordatorioWhatsappTest extends TestCase
{
    use RefreshDatabase;

    public function test_normaliza_celulares_ecuatorianos(): void
    {
        $this->assertSame('593991234567', WhatsappService::normalizarTelefono('0991234567'));
        $this->assertSame('593991234567', WhatsappService::normalizarTelefono('099 123 4567'));
        $this->assertSame('593991234567', WhatsappService::normalizarTelefono('991234567'));
        $this->assertSame('593991234567', WhatsappService::normalizarTelefono('+593 99 123 4567'));
        $this->assertSame('593991234567', WhatsappService::normalizarTelefono('593991234567'));
        $this->assertSame('593991234567', WhatsappService::normalizarTelefono('+593 0991234567'));
    }

    public function test_descarta_fijos_y_numeros_invalidos(): void
    {
        $this->assertNull(WhatsappService::normalizarTelefono(null));
        $this->assertNull(WhatsappService::normalizarTelefono(''));
        $this->assertNull(WhatsappService::normalizarTelefono('044619253'));   // fijo de Guayaquil
        $this->assertNull(WhatsappService::normalizarTelefono('09912345'));    // incompleto
        $this->assertNull(WhatsappService::normalizarTelefono('09912345678')); // un dígito de más
    }

    public function test_arma_el_mensaje_con_la_plantilla_configurada(): void
    {
        app(AjustesService::class)->guardar([
            'marca.nombre' => 'Clínica Sol',
            'recordatorios.whatsapp_plantilla' => '{paciente}|{fecha}|{hora}|{medico}|{clinica}',
        ]);
        $cita = $this->cita('0991234567');

        $this->assertSame(
            'Ana Pérez|lunes 2 de noviembre|09:30|' . $cita->medico->nombre_completo . '|Clínica Sol',
            app(WhatsappService::class)->mensaje($cita)
        );
    }

    public function test_el_enlace_usa_wa_me_con_el_mensaje_codificado(): void
    {
        $cita = $this->cita('099-123-4567');

        $enlace = app(WhatsappService::class)->enlaceRecordatorio($cita);

        $this->assertStringStartsWith('https://wa.me/593991234567?text=', $enlace);
        $this->assertStringContainsString(rawurlencode('Ana Pérez'), $enlace);
        $this->assertStringNotContainsString(' ', $enlace);
    }

    public function test_sin_celular_valido_no_hay_enlace(): void
    {
        $this->assertNull(app(WhatsappService::class)->enlaceRecordatorio($this->cita('042345678')));
    }

    private function cita(string $telefono): Cita
    {
        $paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino', 'telefono' => $telefono]);
        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $medico = Medico::create(['user_id' => User::factory()->create()->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $esp->id]);

        return Cita::create([
            'paciente_id' => $paciente->id,
            'medico_id' => $medico->id,
            'especialidad_id' => $esp->id,
            'tipo' => 'Control prenatal',
            'fecha_hora' => '2026-11-02 09:30:00',
            'duracion_min' => 20,
            'estado' => 'pendiente',
        ])->load('paciente', 'medico');
    }
}
