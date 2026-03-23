<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Definir todos los Permisos del Sistema Spincelaestream (Enhanced with Slugs & Groups)
        $permissions = [
            // Usuarios
            ['name' => 'Ver Usuarios', 'slug' => 'users.view', 'group' => 'Usuarios'],
            ['name' => 'Gestionar Usuarios', 'slug' => 'users.manage', 'group' => 'Usuarios'],
            
            // Áreas
            ['name' => 'Ver Áreas', 'slug' => 'areas.view', 'group' => 'Áreas'],
            ['name' => 'Gestionar Áreas', 'slug' => 'areas.manage', 'group' => 'Áreas'],

            // Gestión de Menús y Proveedores
            ['name' => 'Gestionar Proveedores', 'slug' => 'providers.manage', 'group' => 'Proveedores'],
            ['name' => 'Gestionar Menús', 'slug' => 'menus.manage', 'group' => 'Proveedores'],
            
            // Operación
            ['name' => 'Gestionar Sesiones', 'slug' => 'sessions.manage', 'group' => 'Operación'],
            ['name' => 'Monitorear Pedidos', 'slug' => 'orders.monitor', 'group' => 'Operación'],
            ['name' => 'Autorizar Pedidos de Equipo', 'slug' => 'orders.authorize_team', 'group' => 'Operación'],
            
            // Reportes
            ['name' => 'Reportes de Área', 'slug' => 'reports.area', 'group' => 'Reportes'],
            ['name' => 'Reportes Globales', 'slug' => 'reports.global', 'group' => 'Reportes'],
            
            // Administración del Sistema
            ['name' => 'Configuración del Sistema', 'slug' => 'system.settings', 'group' => 'Sistema'],
            ['name' => 'Gestionar Roles y Permisos', 'slug' => 'security.manage', 'group' => 'Sistema'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // 2. Definir Roles y Asignar Permisos (Spincelaestream Robust Model)
        
        // ADMIN: Acceso Total
        $adminRole = Role::updateOrCreate(['slug' => 'admin'], ['name' => 'Administrador General']);
        $adminRole->permissions()->sync(Permission::all());

        // ACQUISITIONS: Lógica de Abastecimiento
        $acqRole = Role::updateOrCreate(['slug' => 'acquisitions_manager'], ['name' => 'Gerente de Adquisiciones']);
        $acqRole->permissions()->sync(Permission::whereIn('slug', [
            'providers.manage', 'menus.manage', 'sessions.manage', 'orders.monitor', 'reports.global', 'areas.view', 'areas.manage'
        ])->get());

        // AREA MANAGER: Control de Dependencia
        $managerRole = Role::updateOrCreate(['slug' => 'area_manager'], ['name' => 'Gerente de Área']);
        $managerRole->permissions()->sync(Permission::whereIn('slug', [
            'orders.authorize_team', 'reports.area'
        ])->get());

        // DINER: Usuario Final
        $dinerRole = Role::updateOrCreate(['slug' => 'diner'], ['name' => 'Comensal']);
        $dinerRole->permissions()->sync(Permission::whereIn('slug', [
            // No custom permissions needed if defaults are used
        ])->get());
    }
}
