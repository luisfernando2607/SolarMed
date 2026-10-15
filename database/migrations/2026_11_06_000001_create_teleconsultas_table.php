<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telemedicina (Norma Técnica de Telesalud, MSP, R.O. 2.º Supl. 153 del 28-oct-2025):
 * - teleconsultas: agenda, sala de video, autorización/consentimiento (num. 7.2.2), trazabilidad (7.1.1.1)
 *   y efectividad de la conexión (indicadores, 7.3.4).
 * - modalidad presencial/telemedicina en citas y en la historia clínica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teleconsultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('cita_id')->nullable()->unique()->constrained('citas')->nullOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('expediente_consultas')->nullOnDelete();
            $table->dateTime('fecha_hora');
            $table->unsignedSmallInteger('duracion_min')->default(20);
            $table->string('plataforma', 20);            // jitsi | manual
            $table->string('sala_url', 500)->nullable();
            $table->string('token', 64)->unique();       // enlace del paciente
            $table->string('estado', 20)->default('programada'); // programada | en_curso | finalizada | cancelada | no_asistio
            // 7.2.2: autorización para el uso de medios telemáticos y consentimiento para el tratamiento de datos
            $table->dateTime('autorizacion_telematica_at')->nullable();
            $table->dateTime('consentimiento_datos_at')->nullable();
            $table->string('consentimiento_medio', 20)->nullable();   // enlace | verbal
            $table->string('consentimiento_ip', 45)->nullable();
            $table->string('consentimiento_representante', 150)->nullable(); // si acepta el representante legal
            $table->foreignId('consentimiento_registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('inicio_at')->nullable();
            $table->dateTime('fin_at')->nullable();
            $table->string('resultado_conexion', 20)->nullable();     // efectiva | parcial | fallida (7.3.4)
            $table->text('observaciones_tecnicas')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['fecha_hora', 'estado']);
            $table->index(['medico_id', 'fecha_hora']);
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->string('modalidad', 20)->default('presencial')->after('tipo');
        });

        Schema::table('expediente_consultas', function (Blueprint $table) {
            $table->string('modalidad', 20)->nullable()->default('presencial')->after('tipo_consulta');
        });
    }

    public function down(): void
    {
        Schema::table('expediente_consultas', fn (Blueprint $table) => $table->dropColumn('modalidad'));
        Schema::table('citas', fn (Blueprint $table) => $table->dropColumn('modalidad'));
        Schema::dropIfExists('teleconsultas');
    }
};
