<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cie10_codes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique()->index();
            $table->string('descripcion', 500);
            $table->string('categoria', 10)->nullable()->index();
            $table->string('categoria_descripcion', 500)->nullable();
            $table->string('capitulo', 5)->nullable();
            $table->string('capitulo_descripcion', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cie10_codes');
    }
};
