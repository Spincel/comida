<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('group')->nullable(); // For UI grouping
            $table->timestamps();
        });

        // Seed initial permissions
        $permissions = [
            // User Management
            ['name' => 'Ver Usuarios', 'slug' => 'users.view', 'group' => 'Usuarios'],
            ['name' => 'Crear Usuarios', 'slug' => 'users.create', 'group' => 'Usuarios'],
            ['name' => 'Editar Usuarios', 'slug' => 'users.edit', 'group' => 'Usuarios'],
            ['name' => 'Eliminar Usuarios', 'slug' => 'users.delete', 'group' => 'Usuarios'],
            
            // Provider Management
            ['name' => 'Ver Proveedores', 'slug' => 'providers.view', 'group' => 'Proveedores'],
            ['name' => 'Gestionar Catálogo', 'slug' => 'menus.manage', 'group' => 'Proveedores'],
            
            // Session Management
            ['name' => 'Abrir/Cerrar Sesiones', 'slug' => 'sessions.manage', 'group' => 'Operación'],
            ['name' => 'Supervisar Pedidos', 'slug' => 'orders.monitor', 'group' => 'Operación'],
            
            // Reporting
            ['name' => 'Reportes Globales', 'slug' => 'reports.global', 'group' => 'Reportes'],
            ['name' => 'Reportes de Área', 'slug' => 'reports.area', 'group' => 'Reportes'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert($permission);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
