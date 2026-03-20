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
        // 1. Definir todos los Permisos del Sistema Spincelaestream
        $permissions = [
            // Gestión de Menús y Proveedores
            ['name' => 'manage_providers', 'description' => 'Crear, editar y eliminar proveedores'],
            ['name' => 'manage_menus', 'description' => 'Gestionar platillos y menús diarios'],
            ['name' => 'activate_sessions', 'description' => 'Abrir y cerrar sesiones de comida'],
            
            // Gestión de Pedidos
            ['name' => 'place_orders', 'description' => 'Realizar pedidos propios'],
            ['name' => 'authorize_team_orders', 'description' => 'Autorizar pedidos de su área/equipo'],
            ['name' => 'view_all_orders', 'description' => 'Ver todos los pedidos del sistema'],
            
            // Reportes y Analíticas
            ['name' => 'view_area_reports', 'description' => 'Ver reportes de su dependencia'],
            ['name' => 'view_global_reports', 'description' => 'Ver reportes de toda la institución'],
            
            // Administración del Sistema
            ['name' => 'manage_users', 'description' => 'Gestionar usuarios y sus áreas'],
            ['name' => 'manage_roles_permissions', 'description' => 'Modificar niveles de seguridad y permisos'],
            ['name' => 'manage_system_settings', 'description' => 'Cambiar branding, logos y configuración global'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['name' => $p['name']], $p);
        }

        // 2. Definir Roles y Asignar Permisos (Spincelaestream Robust Model)
        
        // ADMIN: Acceso Total
        $adminRole = Role::updateOrCreate(['name' => 'admin', 'display_name' => 'Administrador General']);
        $adminRole->permissions()->sync(Permission::all());

        // ACQUISITIONS: Lógica de Abastecimiento
        $acqRole = Role::updateOrCreate(['name' => 'acquisitions_manager', 'display_name' => 'Gerente de Adquisiciones']);
        $acqRole->permissions()->sync(Permission::whereIn('name', [
            'manage_providers', 'manage_menus', 'activate_sessions', 'view_all_orders', 'view_global_reports'
        ])->get());

        // AREA MANAGER: Control de Dependencia
        $managerRole = Role::updateOrCreate(['name' => 'area_manager', 'display_name' => 'Gerente de Área']);
        $managerRole->permissions()->sync(Permission::whereIn('name', [
            'place_orders', 'authorize_team_orders', 'view_area_reports'
        ])->get());

        // DINER: Usuario Final
        $dinerRole = Role::updateOrCreate(['name' => 'diner', 'display_name' => 'Comensal']);
        $dinerRole->permissions()->sync(Permission::whereIn('name', [
            'place_orders'
        ])->get());
    }
}
