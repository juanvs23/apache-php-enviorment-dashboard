# Changelog

## 1.5.0 (2026-06-12)

### Multi-usuario en proyectos

- **`user_own` ahora es JSON**: `[{"userID":"uuid","user_name":"Juan","is_logeable":true}]`. Reemplaza el modelo de un solo usuario + flag global.
- **Columna `acept_login` eliminada** de Project. El login es por usuario, no por proyecto.
- **API endpoints** (`api.php`): search users, get/set project users, toggle login. Bypass de auth-check.
- **UI**: Select2-style multi-select con búsqueda AJAX. Chips con toggle 🔑/🔒. Modal "Asignar cliente".
- **Cliente sin `projects.acept_login`**: solo ve botones si su `is_logeable` es true en el JSON.
- **`findByUser`** usa `JSON_SEARCH` (compatible MySQL 8.0).
- **`ServiceDetector`**: lógica de detección de servicios centralizada (PostgreSQL, MySQL, pgAdmin, phpMyAdmin). Reemplaza código duplicado en server-info.php y endpoint.
- **Rediseño completo de cards**: gradiente, chips, botones translúcidos. Consistente en dashboard, admin, niveles.
- **Vista refactorizada**: DRY con `_user-card.php` y `_user-form-fields.php`. 524→299 líneas.
- **Seed versionado**: `SEED_ENV` (dev/staging/prod).
- **Estadísticas y Logs**: solo visibles con `server.view`.
- **Migraciones versionadas**: tabla `migrations` con control 001/002/003/004. FK obsoleta eliminada de 001.
- **`migrations/004-upgrade-from-v1.1.sql`**: migración completa para instalaciones existentes (CHAR(36)→JSON, auth_logs, permisos).

### Deuda técnica saldada — Fase 6 (12/12)

- **`helpers.php` eliminado** — `get_os()` inlineado en `server-info.php`, `type_badge()` ya existía como método privado en DashboardController, `encriptar()`/`desencriptar()` eran código muerto
- **Seed.php y fix-admin.php** — documentado que el PDO directo es intencional (circular dependency y emergency tool)
- **fix-admin.php** — password enmascarado en output (`Admin123` → `********`)

### Testing — ProjectCreator (33 tests nuevos)

- **212 tests totales, 506 assertions** (antes 179/434)
- **ProjectCreatorTest**: 33 tests — constructor, `createHtml()` vanilla + Vite, `detectProjectType`, 7 templates, GitHub error case, edge cases (chmod 0777, caracteres especiales)
- Roadmap crear-proyectos: Fase 7 completada ✅

### UX — crear proyectos desde el dashboard

- **Validación client-side onblur**: 3 modales (HTML, Laravel, WordPress) validan al salir del campo y al submit. Errores inline con `is-invalid`.
- **Auto-slug**: nombre del proyecto → directorio en tiempo real, respeta ediciones manuales
- **Loading spinner**: botón submit muestra `⟳ Creando...` y se deshabilita (previene double-submit)
- **Detección de DB existente**: `ProjectCreator::databaseExists()` + endpoint `/?check_db=name` (JSON, solo admin)
- **Validación de nombre de DB**: formato MySQL (`[a-zA-Z_]\w*`) onblur + async existence check. Server-side bloquea la creación si la DB ya existe (antes solo advertía).
- **JS extraído**: `assets/js/create-project-ux.js` — reutilizable, ya no inline en `index.php`
- Roadmap crear-proyectos: Fase 8 completada ✅ (8/8, 100%)

### Auto-save a MySQL

- Proyectos creados desde la UI se registran automáticamente en la tabla `Project` vía `SaveProjectUseCase::create()`
- Ya no es necesario crearlos manualmente desde Usuarios → Proyectos

### Seguridad y hardening

- **Logging de accesos**: tabla `auth_logs` + `AuthLogger`. Registra login exitoso, fallido y logout con IP, email y timestamp. Vista en `?users=1&tab=logs`.
- **phpinfo() protegido**: requiere `DEV_MODE=1` en `.env` + autenticación de admin. Sin ambas, denegado.
- **Hardening en README**: nueva sección con recomendaciones para VPS/staging (HTTPS, firewall, pgAdmin, credenciales, fail2ban, usuarios BD, puertos BD).
- **Staging checklist reestructurado**: separado en responsabilidad del proyecto vs. responsabilidad del desarrollador.
- **Vulnerabilidades documentadas**: 2 mitigadas en el proyecto, 5 vía hardening, 3 aceptadas para staging.
- **`migrations/002-auth-logs.sql`**: migración SQL para instalaciones existentes que no pasaron el auto-migrate.

### Documentación

- **README.md** — actualizado a v1.5.0: hardening, `DEV_MODE`, auth_logs, hardening, puertos BD
- **`.context.md`** — sincronizado: `DEV_MODE`, auth_logs, JS en `assets/js/`, roadmaps 100%
- **`docs/context.md`** — deuda técnica 12/12, tests 212/506, logging, phpinfo guard, `AuthLogger`, `auth_logs`
- **`docs/roadmap-crear-proyectos.md`** — ✅ 8/8 fases (100%)
- **`docs/staging-checklist.md`** — reestructurado con referencias al README
- **`docs/vulnerabilidades.md`** — reescrito: 2 mitigadas, 5 externas, 3 aceptadas
- **`docs/mejoras.md`** — UX crear proyectos ✅, cobertura de tests ✅

## 1.4.0 (2026-06-11)

### Crear proyectos desde el dashboard

- **3 tipos de proyecto**: HTML (vanilla/Vite), WordPress desde cero, clonar desde GitHub
- **HTML**: estructura `index.html` + `assets/css/` + `assets/js/` + `assets/images/`, `.gitignore`, `git init`
- **Vite.js**: checkbox opcional para `package.json`, `vite.config.js`, `src/`, hot reload
- **WordPress**: `wp core download` + `wp config create` + `wp core install`, `FS_METHOD direct`
- **Clonar GitHub**: `git clone` de repos públicos, auto-detección de tipo, `npm install` automático
- **Modales**: tabs Bootstrap, validación de campos, feedback con flash messages

### Gestión npm desde el dashboard

- **📦 Instalar**: `npm install` con cache en `/tmp`, `sudo -u {owner}`, detección de npm system-wide
- **🚀 Iniciar**: `nohup npm run dev &`, PID file, mata procesos previos al iniciar
- **⏹ Detener**: `kill` + limpiar `.pid`, confirmación antes de detener
- **Detector `has_node`/`has_pid`**: el card muestra el botón correcto según el estado
- **Proxy Vite**: Apache proxy pass a `127.0.0.1:5173` para rutas que no existen en disco

### Infraestructura y seguridad

- **`setup.sh` 10 pasos**: instalación condicional de Git, Node.js, Composer, WP-CLI, `unzip`, `curl`
- **`mod_proxy_http`**: activado automáticamente en `setup.sh`
- **`sudoers.d/dashboard-npm`**: `www-data` puede ejecutar `npm install` sin contraseña
- **auth-check bypass**: Google PageSpeed, Lighthouse, GTmetrix
- **Bloqueo crawlers**: 403 para bots de indexación + `robots.txt`
- **`robots.txt`**: `Disallow: /` para todos los crawlers

### Mejoras de código

- **`ProjectCreator`**: servicio con `createHtml()`, `createLaravel()`, `createWordpress()`, `createFromGithub()`
- **`LegacyReader`**: encapsula 6 funciones legacy de lectura para vistas
- **`AuthContext`**: reemplaza `check_auth()`, `refresh_auth_cookie()`, `get_auth_user()`, `can()` globales
- **DRY dashboard**: `$newProjectTypes` array + partial `_new-project-card.php`
- **SVG oficiales**: HTML5, Laravel, WordPress (simple-icons)
- **Delete project**: botón 🗑 en card header, handler con validación
- **`chmod -R 0777`**: todos los proyectos creados sin conflictos de permisos
- **6 archivos legacy eliminados**: `auth.php`, `user-management.php`, `level-management.php`, `profile.php`, `projects.php`, `rate-limiter.php`

### Testing

- **179 tests** (156 unitarios + 23 integración), 434 assertions, 0 failures
- **27 tests Presentation**: AuthController, AdminController
- **23 tests Integration**: LegacyReader, AuthContext

### Fixes

- AdminController: 100% libre de PDO directo
- `UpdateUserUseCase`: soporta cambio de nivel
- `DeleteUserUseCase`: desvincula proyectos antes de eliminar
- `admin@admin` → `admin@admin.com` (FILTER_VALIDATE_EMAIL)
- `fix-admin.php`: CLI tool para resetear admin
- Botón mostrar/ocultar contraseña en perfil y user-management

### RBAC — Permisos dinámicos

- **Tablas `permissions` y `level_permissions`**: sistema de permisos granular desacoplado del código
- **Helper `can()`**: verifica permisos con caché estática. Admin (type 0) tiene acceso total
- **8 permisos**: users.manage, users.edit_same_level, projects.manage, projects.view_all, projects.acept_login, server.view, badge.admin, profile.edit
- **9 puntos de código refactorizados**: todos los `level_type === 0` reemplazados por `can('permiso')`
- **UI de gestión de niveles**: `?users=1&tab=levels` — crear, editar, eliminar niveles con checkboxes de permisos
- **Layout reutilizable**: `management-header.php` + `management-footer.php` con auth check centralizado
- **Niveles y permisos por defecto**:
  - admin: todos los permisos
  - operator: users.manage + projects.* + server.view + profile.edit
  - client: profile.edit
  - revisor: projects.view_all + projects.acept_login + profile.edit (view-only)

### Seed y migraciones

- **Seed ampliado**: crea 4 niveles, 8 permisos, 3 usuarios (admin@admin.com, operator@test.com, revisor@test.com)
- **SQL migration idempotente**: segura para producción, verifica `information_schema` antes de modificar
- **`uk_level_name` UNIQUE KEY** en levels
- **Limpieza de duplicados** en migración

### Perfil de usuario

- **`?profile=1`**: editar email, nombre y contraseña propia (sin cambiar nivel)
- **Protección SQL injection**: queries con prepared statements en level-management

### Instalación

- **`setup.sh`**: detecta MariaDB y saltea MySQL, heredoc para `.env`, verbose con explicaciones
- **`migrations/001-initial-schema.sql`**: DDL manual + seed de niveles

## 1.2.0 (2026-06-08)

### Auth y base de datos

- **Migración MySQL**: reemplazo total de SQLite/AES por MySQL con tabla USERS y bcrypt
- **Auto-migrate**: `Connection::get()` ejecuta `Migration::apply()` al conectar por primera vez
- **Seed data**: `seed.php` crea nivel admin y usuario `admin@admin.com / Admin123`
- **Tabla Project**: `project_name`, `user_own` (NULL permite sin asignar), `acept_login` (TINYINT)
- **Registro de `acept_login`** en `Migration.php`

### Gestión de usuarios

- **CRUD completo**: crear, editar (modal), eliminar usuarios con niveles (admin/client)
- **Asignación de proyectos**: cada proyecto se asigna a un usuario cliente desde la UI
- **Filtro por usuario**: clientes solo ven proyectos asignados (`user_own = su UUID`)
- **Control de acceso**: botones Acceder y WP Admin solo visibles si `acept_login = 1`

### Instalación y setup

- **`setup.sh`**: instalador multi-distro (Ubuntu, Debian, RHEL, Fedora, Arch, openSUSE)
  - Auto-detecta versión de PHP, package manager, servicio Apache
  - Instala Apache + PHP + MySQL + 12 extensiones para WP/Laravel
  - Configura `.env` interactivo, base de datos, VirtualHost, `.htaccess`
  - Detecta MariaDB y saltea MySQL si ya está corriendo en 3306
  - Heredoc para `.env` (inmune a caracteres especiales)
  - Verbose: explica cada paso con `→`
- **`SETUP.md`**: guía de instalación manual para IA — macOS, Windows (XAMPP, Laragon, WSL2, nativo), Linux nicho

### Documentación

- **README.md** reescrito: requisitos reales, distros soportadas, pasos de instalación, `.env` actualizado, esquema DB, niveles de acceso, estructura de archivos, troubleshooting del setup.sh, significado de íconos (`[✓] [!] [✗]`)
- **`.env.example`** actualizado: eliminados `DASHBOARD_KEY`/`DASHBOARD_CLAVE` obsoletos, agregados `DB_DRIVER`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- **`docs/mejoras.md`** actualizado: marcados items completados, nueva sección Base de datos

### Diseño

- **Dark theme**: fondo gradiente, cards con hover box-shadow, scrollbar personalizado
- **Cards en grid**: `row-cols-1 row-cols-md-2 row-cols-xl-3`, responsive
- **Modales**: crear usuario, crear proyecto, editar proyecto (reemplazan tabs/accordions)
- **Campos en línea individual**: cada campo ocupa su propia línea en cards y modales

### Infraestructura

- **PHP limits**: `memory_limit = 2048M`, `upload_max_filesize = 512M`, `post_max_size = 256M`
- **`.htaccess` portable**: ruta absoluta documentada con ⚠️ para cambiar en deploy
- **`.gitignore`**: excepciones para `setup.sh`, `seed.php`, `SETUP.md`
- **`docs/context.md`**: creado con particularidades del proyecto para IA

## 1.1.0 (2026-06-04)

### Features

- **Card Acciones en dashboard**: nueva sección con estado de servicios (PostgreSQL, MySQL), acceso rápido (phpinfo, phpMyAdmin, pgAdmin4) y claves de acceso configurables desde `.env`
- **Detección de servicios**: PostgreSQL vía `pg_isready`, MySQL/MariaDB vía `pgrep mysqld`, pgAdmin4 vía HTTP HEAD, phpMyAdmin vía conf de Apache o URL personalizada
- **Soporte `PMA_URL` en `.env`**: si se define, el botón de phpMyAdmin se activa y apunta a esa URL sin verificación HTTP
- **Claves de acceso dinámicas**: `PGA_EMAIL`, `PGA_PASS`, `DB_USER`, `DB_PASS`, `MYSQL_USER`, `MYSQL_PASS`, `PMA_USER`, `PMA_PASS` se leen del `.env` y se muestran en el card. Si faltan, muestran "Falta"

### Infraestructura

- **PostgreSQL 18.4** instalado desde apt.postgresql.org, autenticación md5 para local
- **pgAdmin4 9.15** instalado en `/opt/pgadmin4` (virtualenv system-wide), sirviendo bajo Apache vía mod_wsgi en `/pgadmin4/`
- Usuario pgAdmin: `admin@localhost.com`, usuario PostgreSQL: `pgadmin` (superuser)
- Autenticación local de PostgreSQL configurada a md5

## 1.0.0 (2026-06-04)

### Features

- **Dashboard con autenticación**: login con contraseña, sesión persistente por cookie (7 días), rate limiting (5 intentos / 15 min)
- **Listado automático de proyectos**: escanea `user-data.txt` en cada subdirectorio y muestra tarjetas con tipo, usuario y contraseña
- **Detectión de WordPress**: los proyectos con `wp-config.php` muestran badge azul y botones de acceso
- **Auto-login WordPress**: botón "Acceder" que bootstrapa WordPress, busca el primer admin y genera sesión sin pasar por `wp-login.php`
- **Protección de proyectos**: vía `php_value auto_prepend_file` + `auth-check.php`, todos los PHP requests en subdirectorios requieren cookie del dashboard
- **Información del servidor**: PHP, SO, disco, hostname en acordeón colapsable
- **Badges por tipo**: WordPress (primary), phpMyAdmin (warning), Laravel (danger), Symfony (info), static (secondary)
- **Mostrar/ocultar contraseña**: botón toggle en el campo de login

### Arquitectura

- Separación en capas siguiendo Clean Architecture:
  - `dashboard-logic/helpers.php` — funciones puras sin estado
  - `dashboard-logic/auth.php` — autenticación
  - `dashboard-logic/rate-limiter.php` — control de intentos
  - `dashboard-logic/projects.php` — listado de proyectos
  - `dashboard-logic/views/` — presentación (dashboard, login, componentes)
- `index.php` como orquestador delgado (~45 líneas)
- Credenciales centralizadas en `.env` con loader compartido (`env-loader.php`)
- `.htaccess` con reglas de bloqueo para archivos sensibles

### Configuración

- `.env.example` incluido como template de credenciales
- `.gitignore` que excluye proyectos, `.env` real, y archivos del sistema

### Assets

- Bootstrap 5 incluido en `assets/` (CSS + JS completos con source maps)
