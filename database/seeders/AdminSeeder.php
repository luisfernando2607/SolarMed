<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class AdminSeeder extends Seeder {
    public function run(): void {
        if (User::where('email', 'admin@clinica.ec')->exists()) {
            User::where('email', 'admin@clinica.ec')->first()->assignRole('admin');
            return;
        }

        // Nunca una contraseña fija en el código (el repositorio es público)
        $password = env('ADMIN_PASSWORD') ?: Str::password(16);

        $admin = User::create([
            'name'     => 'Administrador',
            'email'    => 'admin@clinica.ec',
            'password' => Hash::make($password),
        ]);
        $admin->assignRole('admin');

        if (!env('ADMIN_PASSWORD')) {
            $this->command?->warn("Usuario admin@clinica.ec creado con la contraseña: {$password}");
            $this->command?->warn('Guárdela y cámbiela desde Perfil al ingresar.');
        }
    }
}
