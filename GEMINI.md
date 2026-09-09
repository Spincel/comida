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

---

## Actualización de Calidad y Analíticas (Miércoles 3 de Junio, 2026)

### 1. Sistema de Calificación de Servicio (Star Rating)
- **Objetivo:** Permitir que los Gerentes de Área y Administradores califiquen la calidad del servicio de los proveedores después de cada sesión.
- **Cambios realizados:** 
    - Nueva migración para añadir columnas `rating` en `orders`, `provider_daily_statuses` y `area_session_statuses`.
    - Implementación de lógica de promediado automático en `DashboardController@rateSession`.
    - Interfaz de estrellas interactiva en el Dashboard.
- **Resultado:** Retroalimentación directa sobre la calidad de los alimentos por cada dependencia.

### 2. Panel de Analíticas y Estadísticas (Dashboard 2.5)
- **Objetivo:** Visualizar el comportamiento de consumo y participación por área.
- **Cambios realizados:** 
    - Nueva vista de "Estadísticas" en el panel lateral de Gerentes.
    - Gráficos de barras para participación semanal.
    - Top 3 de platillos más solicitados por área.
    - Resumen dinámico de consumo del día.
- **Resultado:** Mejora en la toma de decisiones basada en datos históricos de consumo.

---
**Desarrollado y Protegido por Spincelaestream - 3 Junio 2026**

---

## Refinamiento de UX y Corrección de Accesos (Miércoles 3 de Junio, 2026 - Sesión 2)

### 1. Navegación Global Estándar
- **Objetivo:** Mejorar la usabilidad permitiendo a los usuarios regresar fácilmente a la pantalla anterior.
- **Cambios realizados:** Implementación de botón universal "Atrás" en `AuthenticatedLayout.vue` con soporte para historial de navegador y fallback al Dashboard.
- **Resultado:** Navegación más fluida en todos los módulos del sistema.

### 2. Estabilización de Permisos y Calificaciones
- **Objetivo:** Resolver el error 403 en el módulo de reportes para adquisiciones y arreglar el sistema de estrellas.
- **Cambios realizados:** 
    - Ampliación de middleware de roles en `web.php` para incluir `acquisitions_manager` en rutas de reportes y calificaciones.
    - Filtrado dinámico de sesiones en `DashboardController` para asegurar que solo sesiones activas con ID válido sean calificables.
    - Corrección de visibilidad de reportes para el rol de Adquisiciones.
- **Resultado:** Acceso autorizado completo para el personal de Adquisiciones y sistema de feedback operativo.

---
**Desarrollado y Protegido por Spincelaestream - 3 Junio 2026**

---

## Optimización de IA y Corrección de Cierre de Sesiones (Miércoles 26 de Agosto, 2026)

### 1. Modernización de Escaneo de Menús con Gemini AI
- **Objetivo:** Resolver el error 503 por modelo obsoleto/saturado (`gemini-flash-latest`).
- **Cambios realizados:**
    - Actualización del endpoint a `gemini-3.6-flash`.
    - Implementación de lista de modelos candidatos con fallback dinámico (`gemini-3.7-flash`, `gemini-3.5-flash-lite`).
    - Configuración de la clave `GEMINI_API_KEY` en el entorno local.
- **Resultado:** Escaneo y extracción automática de platillos operativo y resiliente a saturaciones.

### 2. Corrección en el Flujo de Finalización de Turnos (Monitor de Pedidos)
- **Problema:** Al finalizar una sesión desde el Dashboard de Adquisiciones, el sistema cerraba el turno y redirigía al Monitor (`Summary.vue`), pero en esta vista el botón rojo "Finalizar Comida" seguía mostrándose y parpadeando, obligando al usuario a volver a finalizarla o generando confusión sobre el estado real.
- **Solución:**
    - `DashboardController@showOrderSummary`: Ahora envía el estado real de la sesión (`sessionStatus` y objeto `session`).
    - `Summary.vue`: Se condicionó la visibilidad del botón "Finalizar Comida" únicamente si la sesión está abierta (`sessionStatus === 'open'`).
    - Se añadió indicador de estado `✓ Turno Finalizado` cuando la sesión ya está cerrada.
    - Se añadió botón `Volver al Panel` para facilitar el retorno directo al Dashboard.
    - El refresco automático por polling (15s) ahora se suspende automáticamente si el turno ya fue finalizado.
- **Resultado:** Flujo intuitivo donde al confirmar la finalización, la comida queda cerrada de inmediato y el resumen refleja claramente el estado completado sin botones duplicados.

### 3. Corrección de Visibilidad Global de Sesiones Abiertas (Adquisiciones / Administrador)
- **Problema:** Al tener un usuario con rol `acquisitions_manager` o `admin` asignado a un `area_id`, la variable `$props['openSessions']` era sobreescrita incondicionalmente por `$myAreaSessions` (filtrada solo por su área local). Si el turno se abría para otras áreas distintas a la del usuario de Adquisiciones, el Dashboard de Adquisiciones mostraba `Estado Operativo: Pasivo` y `Sin turnos activos`, ocultando los botones de finalizar y los accesos al monitor.
- **Solución:**
    - `DashboardController@index`: Se restringió la sobreescritura de `$props['openSessions']` únicamente a los roles `area_manager` y `diner`. Para `acquisitions_manager` y `admin` se mantiene siempre la lista completa `$openSessionsDetailed` con todas las sesiones activas en el sistema.
- **Resultado:** El usuario de Adquisiciones y Administradores visualizan de forma inmediata y en tiempo real todas las sesiones abiertas (Cena, Desayuno, Comida), sus temporizadores y sus botones de finalización / monitoreo independientemente de las áreas habilitadas.

### 4. Sincronización Dinámica en Tiempo Real de Áreas en Turnos Activos
- **Problema:** Cuando un turno ya estaba abierto (por ejemplo, Desayuno con Recursos Humanos), al hacer clic en otra área (ej. Sistemas) en el panel de Control Operativo, la selección solo se modificaba en el estado local de Vue y no se guardaba en el backend ni habilitaba el menú a la nueva área. De igual manera, al desmarcar un área activa, no se cancelaban los pedidos ni se revocaban los permisos de esa área en tiempo real.
- **Solución:**
    - `Dashboard.vue`: Se conectaron los eventos de clic en las áreas (`toggleBentoArea`, `selectAllAreas`, `deselectAllAreas`) para que, cuando el turno seleccionado ya se encuentre abierto (`currentActiveBentoSession`), envíen automáticamente una petición `PATCH` a `dashboard.sessions.updateAreas` en segundo plano con `preserveScroll: true`.
    - `DashboardController@updateSessionAreas`:
        - Soporte completo para arrays vacíos `[]` al deseleccionar todas las áreas.
        - **Áreas agregadas:** Se auto-autorizan los gerentes de las nuevas áreas y se resuelven conflictos eliminando dichas áreas de otros turnos superpuestos.
        - **Áreas eliminadas:** Se eliminan las autorizaciones de sesión (`SessionAuthorization`) de los usuarios del área removida y se marcan sus pedidos como cancelados (`status = 'cancelled'`) para esa sesión.
    - `DashboardController@activateProvider`: Se armonizó para limpiar autorizaciones y cancelar pedidos cuando se reconfigura un turno existente con áreas removidas.
- **Resultado:** Habilitación y deshabilitación inmediata de áreas con solo un clic. Al activar un área nueva, su personal puede pedir de inmediato; al desactivarla, se limpia la sesión y se cancelan los pedidos automáticamente.

### 5. Corrección en Activación Inicial de Turnos ('conflict_resolution')
- **Problema:** Al hacer clic en "Iniciar Buffet & Turno" para abrir un turno que aún no estaba activo, el backend rechazaba la petición con un error de validación `422 (The selected conflict resolution is invalid)` porque `Dashboard.vue` enviaba `conflict_resolution: 'replace'`, pero las reglas de validación en `activateMenu` solo aceptaban `merge,substitute,restart`.
- **Solución:**
    - `DashboardController@activateMenu`: Se añadió `'replace'` a las reglas de validación de `conflict_resolution` y se incluyó en la lógica de resolución de conflictos de turnos.
    - `Dashboard.vue`: Se añadió manejo de errores visuales (`onError`) y estado de carga (`isSyncingAreas`) en `submitBentoActivation`.
- **Resultado:** La apertura inicial de turnos (Desayuno, Comida, Cena) funciona correctamente al primer clic con retroalimentación visual clara.

### 6. Enlace Compartido de Pedido Directo para Comensales de Área con Justificación
- **Problema:** En el panel del Gerente de Área, la asignación de pedidos requería que el gerente ingresara manualmente los platillos y justificaciones de cada miembro de su equipo, lo cual resultaba lento cuando los comensales se encontraban en diferentes oficinas o deseaban seleccionar sus propios alimentos y motivos de estancia.
- **Solución:**
    - **Botón "Copiar Link" en Cabecera del Turno:**
        - En `Dashboard.vue` (cabecera del apartado de *Asignar Platillos / Habilitar Personal*), se integró el botón **`Copiar Link`** con soporte para API moderna de portapapeles (`navigator.clipboard`) y respaldo `execCommand`, mostrando confirmación instantánea (`¡Link Copiado!`).
        - El enlace generado apunta a `/pedido/{session_id}/{area_id}`.
    - **Nueva Vista de Pedido Directo / Móvil (`resources/js/Pages/Public/AreaOrder.vue`):**
        - Diseño moderno y responsivo (optimizado para smartphones y escritorios), con soporte para modo claro y oscuro.
        - **Paso 1 (Identificación):** Los comensales ven la lista de integrantes de su área con buscador rápido para seleccionar su nombre. Si ya contaban con un pedido previo, se les muestra el platillo seleccionado y se les permite modificarlo.
        - **Paso 2 (Selección de Platillo):** Catálogo interactivo con los platillos publicados del proveedor activo en ese turno, más un campo opcional para notas/preferencias (ej. *Sin cebolla*, *Salsa aparte*).
        - **Paso 3 (Justificación Obligatoria):** Área de texto con chips de sugerencias rápidas (*Guardia de turno*, *Mantenimiento de servidores*, *Soporte a sesión*, etc.) para justificar el motivo de su estancia laboral, guardándose directamente en `activity_performed`.
        - **Paso 4 (Confirmación):** Envío validado con pantalla de éxito y opción de registrar el pedido de otro compañero en dispositivos compartidos.
    - **Controlador y Rutas (`OrderController.php` & `routes/web.php`):**
        - Rutas públicas `GET /pedido/{session}/{area}` y `POST /pedido/{session}/{area}`.
        - Métodos `publicAreaOrder` y `storePublicAreaOrder` con auto-autorización en `SessionAuthorization` y creación/actualización de pedidos con validación de seguridad de área y turno abierto.
- **Resultado:** Los gerentes pueden compartir el enlace por WhatsApp, correo o chat grupal, permitiendo que cada comensal elija su platillo y justifique su estancia en segundos desde su propio teléfono o computadora.

### 7. Optimización de Flujo Secuencial en Pedido Público (Envío Inmediato de Platillo + Justificación)
- **Problema:** En el flujo inicial, la pantalla pública requería llenar el platillo y la justificación obligatoria al mismo tiempo antes de enviar. El usuario solicitó que al seleccionar a la persona aparezcan los platillos con sus observaciones y en ese momento se pueda enviar el pedido (para que el gerente lo vea de inmediato en tiempo real) y que posteriormente, antes de cerrar, el comensal pueda ingresar su motivo/justificación.
- **Solución:**
    - `OrderController@storePublicAreaOrder`: Se hizo el campo `activity_performed` opcional (`nullable`) en la validación inicial del platillo, permitiendo guardar el pedido con estado `submitted_by_user` y preservando o actualizando la justificación en cualquier momento.
    - `AreaOrder.vue`:
        - **Flujo progresivo en 3 fases:**
            1. **Fase 1 (Persona):** El comensal toca su nombre y la interfaz revela automáticamente la sección de platillos.
            2. **Fase 2 (Platillo & Observaciones):** El comensal elige su platillo, ingresa notas especiales si las tiene y presiona el botón **"Enviar Pedido al Gerente"**. Al enviarse, el pedido se guarda en la base de datos y se notifica con un badge verde (*"✓ Pedido Visible para tu Gerente"*).
            3. **Fase 3 (Justificación):** La sección de justificación se activa y resalta automáticamente para que el comensal ingrese su motivo de estancia (o use las sugerencias rápidas) y presione **"Guardar Justificación"** antes de salir.
- **Resultado:** Experiencia rápida y ágil donde el pedido de comida queda registrado y visible para el Gerente de Área de inmediato, dando oportunidad al comensal de detallar su justificación sin trabas.

### 8. Menú de Herramientas en Barra Superior y Pestañas de Vista Rápida (Control Operativo vs Gestión Comedor)
- **Problema:** 
    1. La caja de "Herramientas" (accesos a reportes, proveedores, usuarios, etc.) ocupaba un bloque estático muy grande en la esquina inferior derecha del Dashboard, desbalanceando la cuadrícula Bento.
    2. El usuario de Adquisiciones/Admin tenía que hacer scroll hasta el fondo de la página para llegar al "Panel Local" y gestionar la comida de su propia área asignada.
- **Solución:**
    - **Menú de Herramientas en el Navbar (`AuthenticatedLayout.vue`):**
        - Se integró un botón desplegable con icono de sistema (`Squares2X2Icon`) antes del botón de cambio de tema en la barra superior.
        - Despliega organizadamente todos los accesos: *Historial Global, Reportes, Estadísticas, Proveedores, Áreas, Usuarios y Configuración del Sistema*.
    - **Pestañas de Vista Rápida (`Dashboard.vue`):**
        - Se implementó una barra superior con selector de pestañas para Adquisiciones y Administradores:
            1. **`🌐 Control Operativo Global`**: Muestra exclusivamente el panel Bento para aperturar, monitorear y cerrar turnos del servicio.
            2. **`🍽️ Gestión Comedor: [Nombre del Área]`**: Muestra de forma directa y en la parte superior el panel de asignación de platillos, plantilla y justificaciones del área del usuario (ej. *ADQUISICIONES*), eliminando la necesidad de hacer scroll.
        - Se retiró la caja redundante de herramientas del cuerpo del Dashboard.
- **Resultado:** Interfaz limpia, profesional y balanceada, donde las herramientas globales están siempre a un clic en el encabezado y la gestión de alimentos del área es accesible al instante.

### 9. Importación Inteligente de Plantilla y Áreas mediante IA (OCR & Document Intelligence)
- **Problema:** El Administrador necesitaba una vía rápida para dar de alta masivamente a los empleados y asignarles sus respectivas áreas de trabajo a partir de listas de nómina, organigramas, oficios, archivos PDF o fotos de plantillas de personal sin tener que capturar usuario por usuario manualmente.
- **Solución:**
    - **Controlador y Endpoints Backend (`UserController.php` & `routes/web.php`):**
        - Se creó `POST /users/scan-document` (`UserController@scanDocument`): Recibe documentos o imágenes (PDF, PNG, JPG, Excel, Word, CSV, TXT) y utiliza Gemini AI (`gemini-3.6-flash`, `gemini-3.7-flash`, etc.) con un prompt especializado para extraer: nombre(s), apellido paterno, apellido materno, área o departamento, puesto, número de empleado, correo y rol sugerido. Mapea coincidencias con las áreas ya existentes en el catálogo.
        - Se creó `POST /users/batch-import` (`UserController@batchImport`): Procesa la lista de usuarios confirmada dentro de una transacción DB. Si un área detectada no existe en la base de datos, la crea automáticamente. Si el usuario ya existe, actualiza su adscripción y número de empleado; si es nuevo, genera automáticamente su nombre de usuario único, correo y contraseña por defecto.
    - **Interfaz de Usuario y Modal Inteligente (`ScanUsersModal.vue` & `Admin/Users/Index.vue`):**
        - Se agregó el botón **"✨ Importar con IA"** en la cabecera del módulo de Usuarios.
        - **Flujo en 3 pasos:**
            1. **Subida y Dropzone:** Soporte para arrastrar fotos, PDFs y hojas de cálculo.
            2. **Análisis en tiempo real con IA:** Barra de progreso y mensajes de estado mientras la IA interpreta el documento.
            3. **Tabla Interactiva de Revisión Previa:** Permite verificar/editar nombres, asignar áreas (existentes o creación automática), definir contraseña general por defecto, filtrar y seleccionar a quiénes importar antes de confirmar.
- **Resultado:** Reducción drástica del tiempo de captura de personal, permitiendo que una plantilla completa con decenas de personas y departamentos se digitalice y registre en la base de datos en menos de un minuto.

### 10. Extracción e Importación Directa desde Directorios Web Institucionales (`/users/scan-url`)
- **Problema:** Además de subir archivos locales, se requería la posibilidad de sincronizar e importar a los funcionarios y sus áreas directamente ingresando el enlace web oficial del Congreso (ej. `https://congresonayarit.gob.mx/directorio-de-funcionarios/`).
- **Solución:**
    - **Endpoint Backend (`UserController@scanUrl` & `routes/web.php`):**
        - Se creó la ruta `POST /users/scan-url`.
        - Descarga el contenido HTML del enlace proporcionado, elimina estilos/scripts y procesa el texto estructurado con Gemini AI (`gemini-3.5-flash`, `gemini-3.6-flash`, etc.).
        - Limpia automáticamente los grados académicos (*Lic., Ing., Mtro., Dip., L.C.*) y extrae: nombre(s), apellido paterno, materno, área o dirección oficial, cargo/puesto, rol (*Gerente de Área*) y el correo institucional exacto.
    - **Pestañas de Selección en Modal (`ScanUsersModal.vue`):**
        - Se incorporó un conmutador de origen en el modal:
            1. **`📁 Subir Archivo`**: Para organigramas en imagen, PDFs o nóminas.
            2. **`🌐 Enlace Web / URL`**: Con botón de sugerencia rápida para el *Directorio Oficial del Congreso del Estado de Nayarit*.
        - Al extraer la información, se muestra la tabla interactiva de revisión previa con todos los funcionarios y sus correos listos para confirmar con un solo clic.
- **Resultado:** Integración directa con el portal web institucional del Congreso, permitiendo poblar la base de datos con funcionarios reales, sus áreas de adscripción y correos oficiales en segundos.

### 11. Identidad Visual Institucional del H. Congreso de Nayarit (Vino Tinto & Oro Ceremonial)
- **Solicitud:** Implementar una imagen y paleta de colores institucional acorde a la identidad oficial del H. Congreso del Estado de Nayarit (XXXIV Legislatura) tanto en Modo Claro como en Modo Oscuro, mejorando colores, degradados, contrastes y sombras sin alterar la estructura ni el orden de los componentes.
- **Punto de Respaldo Seguro:** Se creó el tag Git `checkpoint-institucional-base` previo a los cambios para permitir retornar en cualquier momento.
- **Solución Implementada:**
    - **Configuración de Tailwind (`tailwind.config.js` & `app.css`):**
        - Se integraron las escalas cromáticas oficiales:
            - **`tinto` (Vino Tinto / Guinda Legislativo):** Tonos desde `#fdf2f4` hasta `#78182a` / `#5a1420` (Poder Legislativo y solemnidad).
            - **`oro` (Dorado Ceremonial / Escudo):** Tonos desde `#fbf9f1` hasta `#c5a059` / `#ad8342`.
            - **`nayarit` (Verde Escudo Nayarit):** `#166534` para estados activos y confirmaciones.
        - Se crearon utilidades de sombras elegantes: `shadow-tinto-sm`, `shadow-tinto`, `shadow-oro-sm` y degradados `.bg-congreso-gradient`.
    - **Adaptación en Componentes Principales:**
        - **Barra de Navegación Global (`AuthenticatedLayout.vue`):** Logo institucional en Vino Tinto y Oro, indicador de "Hora Local Nayarit", badges y menú de herramientas estilizados. Fondo claro marfil suave (`#faf9f6`) y oscuro obsidiana (`#0b0f19`).
        - **Panel Operativo Bento (`Dashboard.vue`):** Pestañas de vista rápida ("Control Operativo Global" en tinto/oro y "Gestión Comedor" en verde institucional), botones de turno (Desayuno, Comida, Cena), selector de áreas y botón de "Iniciar Buffet & Turno" con gradiente Vino Tinto y bisel dorado.
        - **Módulo de Usuarios & Modal IA (`Users/Index.vue` & `ScanUsersModal.vue`):** Botones de importación con IA en Tinto/Oro, botón de nuevo registro en Oro cálido, tablas con badges de roles institucionales.
        - **Portal de Pedidos para Personal (`AreaOrder.vue`):** Banner superior con sello republicano Vino Tinto, acentos dorados y estados activos en Verde Nayarit.
### 12. Dinamismo, Micro-interacciones e Iconografía Gastronómica Viva
- **Solicitud:** Incorporar dinamismo al sistema mediante efectos interactivos, animaciones elásticas, iconografía contextual y respuestas visuales vivas al seleccionar alimentos, iniciar turnos y autorizar raciones.
- **Solución Implementada:**
    - **Animaciones Keyframe & Utilidades (`app.css`):**
        - `.animate-radar`: Efecto de onda concéntrica / radar expansivo para turnos activos en servicio.
        - `.animate-steam`: Efecto de vapor ascendente sobre turnos de comida y desayuno.
        - `.animate-pop`: Rebote elástico `cubic-bezier(0.34, 1.56, 0.64, 1)` al seleccionar platillos o integrantes.
        - `.shine-effect`: Destello luminoso en bisel para botones y tarjetas seleccionadas.
        - `.selected-glow-tinto`, `.selected-glow-oro`, `.selected-glow-verde`: Resplandores de alta fidelidad.
    - **Motor de Iconografía Gastronómica Automática (`getDishEmoji`):**
        - Identifica automáticamente el platillo (ej. 🍳 huevos/chilaquiles, 🥩 carnes/cortes, 🍗 pollo, 🐟 pescados/mariscos, 🌮 tacos/antojitos, 🍝 pastas, 🥗 ensaladas, 🍲 caldos/sopas, 🥪 sándwiches, ☕ café/bebidas, 🍰 postres) y renderiza su icono visual dinámico.
    - **Paneles Vivos:**
        - **Turnos:** Desayuno (🍳), Comida (🍲) y Cena (🌙) con vapor e iluminación al activarse.
        - **Tarjetas de Comensales y Menú:** Micro-animación de entrada, badge animado de asignación y checks elásticos.
        - **Modal y Portal de Pedidos:** Catálogo interactivo con escala al tacto/clic y feedback visual en tiempo real.
- **Resultado:** Interfaz viva, dinámica e intuitiva que trasciende el texto plano, permitiendo una experiencia de usuario moderna, fluida y visualmente enriquecida.

---
**Desarrollado y Protegido por Spincelaestream - 26 Agosto 2026**

---

## Control Maestro de Inteligencia Artificial (Miércoles 9 de Septiembre, 2026)

### 13. Configuración Dinámica de Clave API de Gemini y Visibilidad Condicional de Escaneo con IA
- **Solicitud:** Permitir que el Administrador configure directamente la clave API de Google Gemini desde el panel de control del sistema, y que si dicha clave está configurada se habilite el uso de IA; en caso contrario (vacía o ausente), que se oculten automáticamente las funciones y botones de escaneo con IA en todo el sistema para evitar fallos.
- **Cambios Realizados:**
    - **Base de Datos:** Migración `2026_09_09_142000_add_gemini_api_key_to_system_settings_table.php` para almacenar y persistir `gemini_api_key` en la tabla `system_settings`.
    - **Modelo `SystemSetting`:** Métodos estáticos `getGeminiApiKey()` y `hasGeminiApiKey()` con soporte de fallback y respeto a claves vaciadas voluntariamente.
    - **Seguridad en Middleware (`HandleInertiaRequests`):** Comparte la variable booleana global `hasAi` y filtra la clave en texto plano para que únicamente usuarios con permisos de administración (`system.settings`) puedan visualizarla o editarla.
    - **Panel de Configuración de Interfaz (`Admin/Settings/Interface.vue`):**
        - Pestaña dedicada **"Inteligencia Artificial"** con selector Bento, iconos temáticos y banner de estado (`🟢 IA Activa y Operativa` vs `⚪ IA Deshabilitada / Oculta`).
        - Campo con selector para ocultar/mostrar clave, botón para guardar, botón para deshabilitar/borrar y botón de prueba rápida **"⚡ Probar Conexión"**.
        - Endpoint de verificación `/admin/settings/test-gemini` que valida la clave en vivo contra los servidores de Google Gemini.
    - **Visibilidad Condicional en Frontend:**
        - **Proveedores (`Admin/Providers/Index.vue`):** El botón "Escaneo IA de Menú" ahora se condiciona con `v-if="$page.props.system?.hasAi"`.
        - **Menús Diarios (`Admin/DailyMenus/Index.vue`):** El botón "IA Menú" / Importar con IA se condiciona con `v-if="$page.props.system?.hasAi"`.
        - **Usuarios (`Admin/Users/Index.vue`):** El botón "Importar con IA" se condiciona con `v-if="$page.props.system?.hasAi"`.
    - **Controladores Backend:** `DailyMenuController` y `UserController` ahora leen la clave dinámicamente desde `SystemSetting::getGeminiApiKey()`.
- **Resultado:** Autonomía total para el Administrador para activar, cambiar o desactivar la Inteligencia Artificial sin tocar archivos de servidor, manteniendo una interfaz limpia y libre de errores para todos los usuarios.

---
**Desarrollado y Protegido por Spincelaestream - 9 Septiembre 2026**
