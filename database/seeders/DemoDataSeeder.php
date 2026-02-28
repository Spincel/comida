<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\DailyMenu;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Acquisitions User
        User::create([
            'name' => 'Usuario de Adquisiciones',
            'email' => 'adquisiciones@example.com',
            'password' => Hash::make('password'),
            'role' => 'acquisitions_manager',
        ]);

        // 2. Create an Area
        $area = Area::create([
            'name' => 'Gerencia de TI',
        ]);

        // 3. Create an Area Manager
        $manager = User::create([
            'name' => 'Gerente de Área',
            'email' => 'gerente@example.com',
            'password' => Hash::make('password'),
            'role' => 'area_manager',
            'area_id' => $area->id,
        ]);

        // Assign the manager to the area
        $area->manager_id = $manager->id;
        $area->save();

        // 4. Create a Diner
        User::create([
            'name' => 'Comensal Uno',
            'email' => 'comensal@example.com',
            'password' => Hash::make('password'),
            'role' => 'diner',
            'area_id' => $area->id,
        ]);

        // 5. Create a Provider
        $provider = Provider::create([
            'name' => 'Comida Rápida S.A.',
            'contact_person' => 'Juan Pérez',
            'contact_phone' => '5512345678',
            'contact_email' => 'juan.perez@comidarapida.com',
            'delivery_time_window' => '12:00 - 13:00',
        ]);

        // 6. Create a sample DailyMenu for the provider
        DailyMenu::create([
            'name' => 'Pollo frito con papas',
            'description' => 'Delicioso pollo frito acompañado de crujientes papas.',
            'available_on' => now()->toDateString(),
            'status' => 'published',
            'provider_id' => $provider->id,
        ]);
    }
}
