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

## Funcionalidades Clave
1. **Identidad Visual 2.0:** Glassmorphism, temas personalizados y catálogo de fondos.
2. **Local Sync:** Zona horaria CDMX y auto-cierre de sesiones.
3. **Monitor Operativo:** Gestión de pedidos de Comida y Cena en tiempo real.
4. **Seguridad:** Roles y permisos granulares por Área.

---
**Desarrollado y Desplegado por Spincelaestream - 19 Marzo 2026**
> Este punto marca la evolución hacia la personalización total del usuario.
> - **Innovación:** Motor de atmósfera dinámica (Fondos personalizados), Sincronización horaria local, Cierre automático de sesiones.
> - **Diseño:** Glassmorphism inmersivo, Catálogo maestro de fondos, Selector de tema intuitivo.
> - **Regla:** Respetar la legibilidad institucional sobre cualquier fondo seleccionado.

## Major Updates (Thursday - Custom Themes & Local Sync)

### 1. Motor de Atmósfera Dinámica (Fondos)
- **Catálogo Maestro (Admin):** Los administradores ahora pueden gestionar un catálogo global de fondos desde la pestaña de Interfaz. Pueden subir imágenes personalizadas o usar las predefinidas de alta calidad.
- **Personalización por Usuario:** Cada usuario (sin importar su rol) tiene acceso a un nuevo botón de "Pincel" en el Header para elegir su fondo de pantalla preferido y modo de color (Claro/Oscuro/Sistema).
- **Glassmorphism Inmersivo:** El fondo seleccionado se aplica de forma fija en todo el sistema con un efecto de desenfoque suave y una capa de contraste para asegurar la legibilidad de las tarjetas y textos.

### 2. Ingeniería de Fecha y Tiempo (Local Sync)
- **Timezone Mexico City:** Sincronización total del servidor con el horario local para evitar desfases de "día siguiente" en sesiones nocturnas.
- **Auto-Cierre de Sesiones:** El sistema ahora cierra automáticamente cualquier sesión abierta de días anteriores al entrar al Dashboard, garantizando que el inicio siempre esté limpio para el nuevo día.
- **Local Date Picker:** El selector de fecha en el modal de activación ahora sugiere la fecha local del usuario en lugar de la UTC.

### 3. Refinamiento de UX en Dashboard
- **Etiquetas Dinámicas:** Las tarjetas de proveedores ahora muestran estados más precisos: "Gestionar Activos", "Ver Cerrados / Nuevo" e "Iniciar Sesión", eliminando la confusión sobre sesiones finalizadas.

## Current State
- **Estado:** "Zafiro Personalizado". El sistema es visualmente impresionante, adaptado al horario local y permite que cada usuario cree su propio entorno de trabajo.

## Files Modified (Cycle 2.0)
- `config/app.php` & `.env` (Timezone)
- `app/Http/Controllers/DashboardController.php` (Auto-close logic)
- `app/Http/Controllers/Admin/SystemSettingsController.php` (Theme & Catalog logic)
- `app/Models/User.php` (Theme settings cast)
- `resources/js/Layouts/AuthenticatedLayout.vue` (Global background & Modal integration)
- `resources/js/Pages/Admin/Settings/Interface.vue` (Catalog management)
- `resources/js/Pages/Admin/Partials/ThemeSelectorModal.vue` (New UI)
- `GEMINI.md`
