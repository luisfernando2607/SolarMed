<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        // Recepcionista
        $recepcionista = User::firstOrCreate(
            ['email' => 'recepcion@clinica.ec'],
            [
                'name' => 'Ana Rodríguez',
                'password' => Hash::make('recepcion123'),
            ]
        );
        $recepcionista->assignRole('secretaria');

        // Médico general
        $medico1 = User::firstOrCreate(
            ['email' => 'dr.garcia@clinica.ec'],
            [
                'name' => 'Dr. Carlos García',
                'password' => Hash::make('medico123'),
            ]
        );
        $medico1->assignRole('medico');

        // Médico especialista (Dr. Bury - Ginecología)
        $medico2 = User::firstOrCreate(
            ['email' => 'dr.bury@clinica.ec'],
            [
                'name' => 'Sistema Médico',
                'password' => Hash::make('medico123'),
            ]
        );
        $medico2->assignRole('medico');

        // Crear registros en tabla medicos si no existen
        $generalId = DB::table('especialidades')->where('codigo', 'general')->value('id');
        $ginecoId = DB::table('especialidades')->where('codigo', 'ginecologia')->value('id');

        // Usar upsert: si ya existe (user_id + email), actualiza; si no, inserta
        // Insertar médicos individualmente para evitar conflictos de columnas
        if (!DB::table('medicos')->where('user_id', $medico1->id)->exists()) {
            DB::table('medicos')->insert([
                'user_id' => $medico1->id,
                'nombres' => 'Carlos',
                'apellidos' => 'García',
                'especialidad_id' => $generalId,
                'telefono' => '0991111111',
                'email' => 'dr.garcia@clinica.ec',
                'activo' => 1,
            ]);
        }

        if (!DB::table('medicos')->where('user_id', $medico2->id)->exists()) {
            DB::table('medicos')->insert([
                'user_id' => $medico2->id,
                'nombres' => 'Jorge',
                'apellidos' => 'Bury',
                'especialidad_id' => $ginecoId,
                'colegiatura' => 'MSP-12345',
                'telefono' => '0992222222',
                'email' => 'dr.bury@clinica.ec',
                'activo' => 1,
            ]);
        }
    }
}
