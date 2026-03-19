# 🍱 SICOA - Sistema de Control de Alimentación

![Spincelaestream](https://img.shields.io/badge/Brand-Spincelaestream-blue?style=for-the-badge&logo=appveyor)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)
![Inertia.js](https://img.shields.io/badge/Inertia.js-Modern_Monolith-9553E9?style=for-the-badge)

SICOA es una plataforma integral para la gestión de comedores industriales y servicios de alimentación institucional. Diseñado bajo la filosofía de **Spincelaestream**, combina eficiencia tecnológica con una estética premium para optimizar la logística entre Proveedores, Áreas solicitantes y Administración.

## 🌟 Características Principales

* **Identidad Visual 2.0:** Motor de temas personalizados, catálogo de fondos dinámicos y diseño basado en *Glassmorphism* para una experiencia de usuario inmersiva y elegante.
* **Gestión de Pedidos en Tiempo Real:** Monitor interactivo para controlar solicitudes de "Comida" y "Cena", con feedback visual instantáneo.
* **Sincronización Horaria (Local Sync):** Operación estricta bajo la zona horaria local (CDMX) para evitar desfases en registros nocturnos.
* **Seguridad y Cierre Automático:** Detección de sesiones de días anteriores y cierre automático para garantizar un inicio de operaciones limpio cada día.
* **Control de Proveedores:** Activación por fecha y tipo de comida, con soporte para múltiples áreas.

## 🛠️ Stack Tecnológico

* **Backend:** PHP 8.3 / Laravel 12
* **Frontend:** Vue.js 3 (Composition API) / Inertia.js / TailwindCSS
* **Base de Datos:** MariaDB / MySQL
* **Servidor Ideal:** Linux (Ubuntu/Debian) con Nginx y PHP-FPM

---

## 🚀 Guía de Despliegue en HestiaCP (Producción)

Esta sección documenta la configuración específica necesaria para desplegar SICOA en un entorno **HestiaCP** bajo el dominio `sicoa.aestream.xyz`.

### 1. Clonar el repositorio y configurar el entorno
```bash
cd /home/admin/web/sicoa.aestream.xyz/public_html
rm -rf *
git clone <URL_DEL_REPOSITORIO> .
cp .env.example .env
# Configurar DB_DATABASE, DB_USERNAME, DB_PASSWORD en el .env
# Asegurar que APP_ENV=production y APP_URL sea la correcta
```

### 2. Instalación de Dependencias
```bash
# Ignorar chequeos estrictos de versión si Hestia usa PHP 8.3
composer config platform-check false
composer install --ignore-platform-reqs --optimize-autoloader --no-dev

# Frontend
npm install
npm run build
```

### 3. Migraciones y Datos Base
```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder
php artisan storage:link
```

### 4. Configuración Crítica de HestiaCP / Nginx
Para que Laravel funcione correctamente en HestiaCP, se deben aplicar las siguientes configuraciones:

**A. Liberar a PHP de la restricción `open_basedir`:**
PHP necesita acceso a toda la carpeta del proyecto, no solo a `public`.
Editar el archivo `/etc/php/8.3/fpm/pool.d/sicoa.aestream.xyz.conf` y asegurarse de que la ruta base no termine en `/public`:
```ini
# CORRECTO
php_admin_value[open_basedir] = /home/admin/.../public_html:/tmp...
# INCORRECTO (Hestia lo pone así por defecto si usas Custom Document Root)
# php_admin_value[open_basedir] = /home/admin/.../public_html/public:/tmp...
```
*Reiniciar PHP después del cambio:* `systemctl restart php8.3-fpm`

**B. Ajustar el Document Root de Nginx:**
El punto de entrada debe ser la carpeta `public` de Laravel.
En los archivos `/home/admin/conf/web/sicoa.aestream.xyz/nginx.conf` y `nginx.ssl.conf`:
```nginx
root /home/admin/web/sicoa.aestream.xyz/public_html/public;
```
*Asegurarse de que no diga `/public/public` por error del panel.*
*Reiniciar Nginx:* `systemctl restart nginx`

**C. Permisos de Carpetas:**
El usuario del panel debe tener permisos de escritura en estas carpetas:
```bash
chown -R admin:admin /home/admin/web/sicoa.aestream.xyz/public_html
chmod -R 775 storage bootstrap/cache
```

## 🔐 Accesos por Defecto (Tras Seeder)
* **URL:** `https://sicoa.aestream.xyz`
* **Email:** `admin@admin.com`
* **Contraseña:** `admin123` (Generada manualmente) o `password` (Por defecto del seeder)

---
*Desarrollado y mantenido por el equipo de ingeniería de **Spincelaestream**.*
