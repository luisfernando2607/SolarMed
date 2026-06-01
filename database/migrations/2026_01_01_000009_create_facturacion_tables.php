<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('servicios_tarifario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 8, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura', 20)->unique();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->foreignId('turno_id')->nullable()->constrained('turnos')->nullOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->dateTime('fecha')->useCurrent();
            $table->decimal('subtotal', 8, 2)->default(0);
            $table->decimal('descuento', 8, 2)->default(0);
            $table->decimal('total', 8, 2)->default(0);
            $table->enum('forma_pago', ['efectivo','transferencia','tarjeta'])->default('efectivo');
            $table->string('referencia_pago', 100)->nullable();
            $table->enum('estado', ['pagada','anulada'])->default('pagada');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['fecha', 'medico_id']);
        });

        Schema::create('factura_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios_tarifario')->nullOnDelete();
            $table->string('descripcion', 200);
            $table->unsignedSmallInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 8, 2);
            $table->decimal('subtotal', 8, 2);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('factura_items');
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('servicios_tarifario');
    }
};
