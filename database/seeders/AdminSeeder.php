<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder {
    public function run(): void {
        $admin = User::firstOrCreate(
            ['email' => 'admin@clinica.ec'],
            [
                'name'     => 'Dr. Jorge Bury',
                'password' => Hash::make('CambiarEsto123!'),
            ]
        );
        $admin->assignRole('admin');
    }
}
