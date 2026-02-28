<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestingUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Asegurar que existan áreas
        $areaSistemas = Area::updateOrCreate(['name' => 'Sistemas']);
        $areaRH = Area::updateOrCreate(['name' => 'Recursos Humanos']);
        $areaAdquisiciones = Area::updateOrCreate(['name' => 'Adquisiciones']);

        // 2. Usuario de Adquisiciones (El que abre menús)
        User::updateOrCreate(
            ['email' => 'adquisiciones@example.com'],
            [
                'name' => 'Adquisiciones Master',
                'password' => Hash::make('password'),
                'role' => 'acquisitions_manager',
                'area_id' => $areaAdquisiciones->id,
            ]
        );

        // 3. Gerente de Área Sistemas (Encargado y Comensal)
        User::updateOrCreate(
            ['email' => 'gerente.sistemas@example.com'],
            [
                'name' => 'Juan Gerente Sistemas',
                'password' => Hash::make('password'),
                'role' => 'area_manager',
                'area_id' => $areaSistemas->id,
            ]
        );

        // 4. Comensal de Sistemas
        User::updateOrCreate(
            ['email' => 'comensal.sistemas@example.com'],
            [
                'name' => 'Pedro Comensal Sistemas',
                'password' => Hash::make('password'),
                'role' => 'diner',
                'area_id' => $areaSistemas->id,
            ]
        );

        // 5. Administrador General (RH ahora es Admin)
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Maria Administradora',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'area_id' => $areaRH->id,
            ]
        );

        // 6. Comensal de RH
        User::updateOrCreate(
            ['email' => 'comensal.rh@example.com'],
            [
                'name' => 'Lucia Comensal RH',
                'password' => Hash::make('password'),
                'role' => 'diner',
                'area_id' => $areaRH->id,
            ]
        );
    }
}
