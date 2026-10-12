<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expediente_consulta_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('expediente_consultas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->json('datos_anteriores');
            $table->string('motivo', 500);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['consulta_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expediente_consulta_versiones');
    }
};
