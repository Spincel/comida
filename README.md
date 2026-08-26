# 🏛️ SICOA - Sistema de Control de Alimentación
### H. Congreso del Estado de Nayarit • XXXIV Legislatura

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)
![Inertia.js](https://img.shields.io/badge/Inertia.js-Modern_Monolith-9553E9?style=for-the-badge)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-Institucional-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

**SICOA (Sistema de Control de Alimentación)** es una plataforma institucional diseñada y construida para la gestión integral, auditoría, control de raciones y logística de comedor institucional del **H. Congreso del Estado de Nayarit**.

Permite la orquestación en tiempo real entre la **Dirección de Adquisiciones**, los **Gerentes de Área**, los **Comensales/Personal** y los **Proveedores de Alimentos (Banqueteros)**, con una interfaz Bento Grid inspirada en la identidad visual oficial del Congreso (Vino Tinto `#78182A`, Oro Ceremonial `#C5A059` y Verde Nayarit `#166534`).

---

## 👥 Usuarios y Credenciales por Defecto (Seeders)

Al ejecutar las migraciones y seeders del sistema (`php artisan migrate --seed`), se generan los siguientes usuarios y roles predeterminados con la contraseña inicial **`password`**:

| Rol / Perfil | Nombre | Correo Electrónico | Contraseña | Área Asignada | Alcance y Funcionalidades |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Administrador General** | Maria Administradora | `admin@example.com` | `password` | Recursos Humanos | Control total del sistema, configuración visual, gestión de usuarios, roles, respaldos SQL y bitácora de auditoría. |
| **Gerente de Adquisiciones** | Adquisiciones Master | `adquisiciones@example.com` | `password` | Adquisiciones | Apertura y cierre de turnos de comida, asignación de proveedores/banqueteros, habilitación de áreas y monitor en tiempo real de raciones. |
| **Gerente de Área** | Juan Gerente Sistemas | `gerente.sistemas@example.com` | `password` | Sistemas | Autorización y captura de platillos para el personal de su área, firma y envío consolidado a cocina, captura de justificaciones. |
| **Comensal (Sistemas)** | Pedro Comensal Sistemas | `comensal.sistemas@example.com` | `password` | Sistemas | Selección individual de platillos autorizados, preferencias culinarias y calificación del servicio. |
| **Comensal (RH)** | Lucia Comensal RH | `comensal.rh@example.com` | `password` | Recursos Humanos | Selección individual de platillos autorizados para el área de RH. |

> [!TIP]
> Puedes cambiar o personalizar las contraseñas de estos usuarios una vez instalado el sistema desde el módulo de **Mi Perfil** o desde la administración de usuarios.

---

## 📋 Requisitos del Servidor

Para instalar SICOA en un nuevo servidor (Ubuntu, Debian, AlmaLinux, VPS o Servidor Dedicado) se requieren los siguientes componentes:

* **Sistema Operativo:** Linux (Ubuntu 22.04 / 24.04 LTS recomendado, Debian 12 o RHEL/AlmaLinux 9).
* **PHP:** Versión `8.2` o `8.3` (con PHP-FPM).
* **Extensiones PHP obligatorias:** `php-bcmath`, `php-ctype`, `php-curl`, `php-dom`, `php-fileinfo`, `php-gd`, `php-json`, `php-mbstring`, `php-mysql`, `php-openssl`, `php-tokenizer`, `php-xml`, `php-zip`.
* **Gestor de paquetes PHP:** Composer `2.x`.
* **Base de Datos:** MySQL `8.0+` o MariaDB `10.6+`.
* **Node.js & NPM:** Node.js `18.x` / `20.x` LTS y NPM `9+`.
* **Servidor Web:** Nginx (Recomendado) o Apache con módulo `mod_rewrite`.

---

## 🚀 Guía de Instalación Paso a Paso (Nuevo Servidor)

### 1. Clonar el repositorio
Ingresa al directorio de aplicaciones de tu servidor web y clona el proyecto:

```bash
cd /var/www
git clone https://github.com/Spincel/comida.git sicoa
cd sicoa
```

---

### 2. Configurar el archivo de entorno (`.env`)
Copia la plantilla de entorno y configura la conexión a la base de datos y la URL del sistema:

```bash
cp .env.example .env
nano .env
```

Asegúrate de configurar correctamente los siguientes parámetros en tu archivo `.env`:

```ini
APP_NAME="SICOA - Congreso de Nayarit"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tu-dominio-o-ip.gob.mx

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sicoa_db
DB_USERNAME=sicoa_user
DB_PASSWORD=tu_password_seguro

# Zona Horaria Local (Importante para registro exacto de turnos)
APP_TIMEZONE="America/Mazatlan"
```

---

### 3. Instalar dependencias de PHP (Composer)
```bash
composer install --no-dev --optimize-autoloader
```

---

### 4. Generar la llave de la aplicación y crear enlace simbólico
```bash
php artisan key:generate
php artisan storage:link
```

---

### 5. Configurar permisos de almacenamiento y caché
Asigna los permisos correctos al usuario del servidor web (`www-data` para Ubuntu/Debian o `nginx`/`apache` en RHEL):

```bash
sudo chown -R www-data:www-data /var/www/sicoa
sudo chmod -R 775 /var/www/sicoa/storage /var/www/sicoa/bootstrap/cache
```

---

### 6. Ejecutar migraciones y datos iniciales (Seeders)
Crea la estructura de tablas y precarga los roles, permisos y usuarios iniciales:

```bash
php artisan migrate --seed --force
```

---

### 7. Instalar dependencias de Node.js y compilar el Frontend (Vite)
```bash
npm install
npm run build
```

---

### 8. Optimizar la aplicación para Producción
Ejecuta los comandos de caché de Laravel para máxima velocidad de respuesta:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🌐 Configuración del Servidor Web

### Opción A: Configuración con Nginx (Recomendado)

Crea un archivo de configuración en `/etc/nginx/sites-available/sicoa.conf`:

```nginx
server {
    listen 80;
    server_name sicoa.congresonayarit.gob.mx; # Reemplaza por tu dominio o IP
    root /var/www/sicoa/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;
    charset utf-8;

    # Tamaño máximo de subida para documentos/organigramas/fotos
    client_max_body_size 25M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; # Verifica tu versión de PHP-FPM
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Habilita el sitio y reinicia Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/sicoa.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

*(Opcional con Certbot para HTTPS):*
```bash
sudo certbot --nginx -d sicoa.congresonayarit.gob.mx
```

---

### Opción B: Configuración en HestiaCP / cPanel

1. **Document Root:** Configurar la ruta raíz apuntando a la subcarpeta `/public` (ej: `/home/usuario/web/dominio.gob.mx/public_html/public`).
2. **`open_basedir`:** Si se utiliza PHP-FPM con `open_basedir`, asegurarse de que incluya la raíz del proyecto para permitir lectura del directorio raíz y `/storage`.
3. **Permisos:** Otorgar permisos de escritura a `storage/` y `bootstrap/cache/`.

---

## 🛠️ Mantenimiento y Respaldos

* **Respaldos de Base de Datos:**
  El sistema incluye un módulo de respaldo SQL en vivo desde la interfaz web en:
  `Menú Herramientas` ➔ `Mantenimiento BD` (`/admin/utilities/data`), donde el Administrador puede descargar un respaldo completo en archivo `.sql` con un solo clic o restaurarlo mediante importación directa.
* **Importación Masiva de Estructura Orgánica:**
  Se dispone de la función de escaneo automático de organigramas y directorio web oficial de funcionarios desde el módulo de Usuarios (`/users`).

---

## 🏛️ Créditos y Licencia
Desarrollado para el **H. Congreso del Estado de Nayarit • XXXIV Legislatura**.
Todos los derechos reservados.
