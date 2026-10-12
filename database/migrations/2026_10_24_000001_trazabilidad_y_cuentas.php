<?php

use App\Models\RegistroAcceso;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - registro_accesos.hash: cadena SHA-256 (cada registro sella al anterior) para detectar alteraciones.
 * - users: cambio de contraseña obligatorio (AM 5216-A Art. 15: claves de acceso personales) y
 *   aceptación del acuerdo de confidencialidad (Disposición General Segunda).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_accesos', function (Blueprint $table) {
            $table->char('hash', 64)->nullable()->after('user_agent');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('debe_cambiar_password')->default(false)->after('password');
            $table->timestamp('password_cambiado_at')->nullable()->after('debe_cambiar_password');
            $table->timestamp('confidencialidad_aceptada_at')->nullable()->after('password_cambiado_at');
            $table->string('confidencialidad_ip', 45)->nullable()->after('confidencialidad_aceptada_at');
        });

        // Las cuentas existentes comparten contraseñas iniciales: cada persona define la suya al entrar
        DB::table('users')->update(['debe_cambiar_password' => true]);

        // Sella los registros que ya existían, en orden
        $anterior = null;
        DB::table('registro_accesos')->orderBy('id')->chunk(500, function ($filas) use (&$anterior) {
            foreach ($filas as $fila) {
                $anterior = RegistroAcceso::calcularHash($anterior, (array) $fila);
                DB::table('registro_accesos')->where('id', $fila->id)->update(['hash' => $anterior]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('registro_accesos', fn (Blueprint $table) => $table->dropColumn('hash'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn([
            'debe_cambiar_password', 'password_cambiado_at', 'confidencialidad_aceptada_at', 'confidencialidad_ip',
        ]));
    }
};
