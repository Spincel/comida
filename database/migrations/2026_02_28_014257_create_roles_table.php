<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed initial roles
        $roles = [
            ['name' => 'Administrador General', 'slug' => 'admin', 'description' => 'Acceso total al sistema y configuraciones'],
            ['name' => 'Gerente de Adquisiciones', 'slug' => 'acquisitions_manager', 'description' => 'Gestión de proveedores, menús y sesiones'],
            ['name' => 'Gerente de Área', 'slug' => 'area_manager', 'description' => 'Control de pedidos de su propio equipo'],
            ['name' => 'Comensal', 'slug' => 'diner', 'description' => 'Realización de pedidos personales'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert($role);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
