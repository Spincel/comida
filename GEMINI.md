# SICOA (Sistema de Control de Alimentación) - Spincelaestream

> **🚀 PROYECTO FINALIZADO Y DESPLEGADO 🚀**
> - **Marca:** Spincelaestream
> - **Servidor:** 172.30.4.132 (HestiaCP)
> - **Dominio:** [sicoa.aestream.xyz](https://sicoa.aestream.xyz)
> - **Entorno:** Producción (PHP 8.3, Laravel 12, MariaDB)

## Resumen Técnico del Despliegue
- **Corrección open_basedir:** Se liberó el acceso de PHP a la raíz del proyecto para permitir la carga de `vendor/`.
- **Nginx Config:** Document Root apuntando a `public_html/public`.
- **Base de Datos:** Migraciones sincronizadas y Seeder ejecutado con éxito.
- **Frontend:** Compilado con Vite (Visual Identity 2.0).

---

## Bitácora de Evolución y Estabilización (Viernes 20 de Marzo, 2026)

### 1. Intento de Rediseño Integral "SICOA ZAFIRO"
- **Objetivo:** Transformar la interfaz al estilo Glassmorphism Sapphire (basado en `cambio1.png`).
- **Cambios realizados:** Rediseño de Welcome, Login, Dashboard y Perfil. Implementación de motor de temas Claro/Oscuro adaptativo.
- **Incidentes:** Se presentaron errores de contraste en Tema Claro, advertencias de componentes no importados y duplicidad de etiquetas `<script setup>` que causaron fallos en la compilación inicial.

### 2. Fase de Estabilización y Reparación de Producción
Tras detectar un error crítico en producción (`Column not found: meal_type`) al intentar activar servicios, se priorizó la estabilidad sobre el diseño.

- **Reversión Local:** Se aplicó un `git reset --hard` al commit estable `c94415f`.
- **Reparación de BD:** Se creó la migración `2026_03_20_165608_fix_missing_meal_type_column.php` para forzar la creación de columnas faltantes en las tablas `provider_daily_statuses` y `orders`.
- **Sincronización de Servidor (HestiaCP):**
    - Se corrigieron permisos de carpeta: `sudo chown -R admin:admin ...`
    - Se forzó la descarga de código: `git reset --hard origin/main`
    - Se ejecutó la migración de emergencia: `php artisan migrate --force`
    - Se refrescó la configuración: `php artisan optimize:clear`

## Current State
- **Estado:** "Resiliencia Operativa". El sistema ha vuelto a su diseño estable probado en producción, con la base de datos reparada y funcional para todos los roles (Admin, Adquisiciones, Gerente y Comensal).

## Archivos de Reparación
- `database/migrations/2026_03_20_165608_fix_missing_meal_type_column.php` (Crucial para corregir Error 500 SQL).
- `GEMINI.md` (Actualizado con bitácora de emergencia).

---

## Actualización de Seguridad y Resiliencia (Lunes 23 de Marzo, 2026)

### 1. Sistema de Permisos Dinámicos (RBAC 2.0)
- **Objetivo:** Eliminar la dependencia de roles estáticos para permitir una gestión granular de accesos.
- **Cambios realizados:** 
    - Implementación de `CheckPermission` middleware y actualización de `HandleInertiaRequests`.
    - Reestructuración de `RolesAndPermissionsSeeder` con sistema de `slugs` y `groups`.
    - Adaptación del frontend con función `can()` para visibilidad dinámica de menús.
- **Resultado:** El rol de **Adquisiciones** ahora tiene acceso autorizado a la gestión de **Áreas y Usuarios**.

### 2. Implementación de Soft Deletes y Resiliencia en Reportes
- **Problema:** La eliminación física de usuarios causaba pérdida de datos en reportes históricos.
- **Solución:**
    - Activación de `SoftDeletes` en el modelo `User` y migración `2026_03_23_125823_add_soft_deletes_to_users_table.php`.
    - Actualización de `DashboardController` usando `withTrashed()` en historial y justificaciones.
- **Resultado:** Los **Gerentes de Área** pueden justificar pedidos de personal desactivado, garantizando integridad en los reportes de nómina.

### 3. Guía de Actualización en Servidor (HestiaCP)
Pasos seguros para desplegar en 172.30.4.132:
1. `sudo chown -R socrates:admin . && git pull origin main`
2. `php artisan migrate --force && php artisan optimize:clear`
3. `php artisan db:seed --class=RolesAndPermissionsSeeder --force`
4. `npm run build`
5. `sudo chown -R admin:admin . && sudo chmod -R 775 storage bootstrap/cache`

---
**Desarrollado y Protegido por Spincelaestream - 23 Marzo 2026**
