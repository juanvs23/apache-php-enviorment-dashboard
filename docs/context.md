# Contexto del proyecto para IA

## Stack actual

- **Apache 2.4** con `mod_php` (NO PHP-FPM) y `mod_rewrite`
- **PHP 8.3** con `pdo_mysql`, `openssl`, `mbstring`, `session`, `json`, `gd`, `zip`, `intl`, `curl`, `xml`
- **MySQL 8.0** — base de datos `apache-dashboard` con tablas `levels`, `USERS`, `Project`, `permissions`, `level_permissions`
- **Bootstrap 5** en `assets/` (sin CDN)

## Arquitectura (Clean Architecture, Fase 6 aplicada)

```
index.php                    → orquestador delgado (usa ServiceContainer + AuthController)
dashboard-logic/
  Domain/                    → entidades puras (User, Level, Project, Permission, Email, LevelType)
    Entity/                  → User, Level, Project, Permission
    ValueObject/             → Email, LevelType
    Exception/               → DomainException
  Application/               → Use Cases (sin dependencias de framework)
    UseCase/Auth/            → LoginUseCase, LogoutUseCase
    UseCase/User/            → CreateUserUseCase, UpdateUserUseCase, DeleteUserUseCase, ListUsersUseCase
    UseCase/Project/         → SaveProjectUseCase, DeleteProjectUseCase, AssignProjectUseCase, ListProjectsForUserUseCase
    UseCase/Level/           → CreateLevelUseCase, UpdateLevelUseCase, DeleteLevelUseCase
    UseCase/Permission/      → CheckPermissionUseCase
    Repository/              → interfaces: UserRepositoryInterface, LevelRepositoryInterface, etc.
  Infrastructure/            → implementaciones concretas
    Persistence/             → MySQLUserRepository, MySQLLevelRepository, MySQLProjectRepository, MySQLPermissionRepository
    Filesystem/              → ProjectScanner (escanea directorios con user-data.txt)
    Session/                 → SessionManager (rate limiting basado en $_SESSION)
  Presentation/              → capa HTTP (controladores + router)
    Controller/              → AuthController, DashboardController, AdminController, ProfileController
    Router.php               → rutea requests a controladores
    ServiceContainer.php     → DI container con lazy initialization
  Database/                  → infraestructura compartida
    Connection.php           → Singleton PDO + auto-migrate
    Migration.php            → Schema DDL (CREATE TABLE IF NOT EXISTS)
    Seed.php                 → Datos iniciales idempotentes
  Tests/                     → PHPUnit (125 tests, 280 assertions)
    Unit/Domain/             → 52 tests
    Unit/Application/        → 56 tests
    Unit/Infrastructure/     → 17 tests
  views/                     → vistas PHP puras (partials dentro del shell HTML)
    components/              → management-header.php, management-footer.php
```

## Autenticación

- **Login**: `AuthController::login()` → `LoginUseCase::execute()` → `Email` ValueObject + `UserRepository::findByEmail()` + `User::authenticate()`
- **Cookie**: `project_user` almacena el email del usuario, 7 días (`COOKIE_EXPIRY`), path `/`
- **Rate limiting**: `SessionManager` (5 intentos máx, ventana 15 min). Se consulta en `index.php` antes del login y se incrementa en `AuthController`.
- **Levels**: `level_type` 0 = admin (todos los permisos), 1 = permisos por tabla `level_permissions`
- **Permisos RBAC**: función legacy `can()` en `auth.php`. Admin (type 0) tiene todos automáticamente.
- **8 permisos**: users.manage, users.edit_same_level, projects.manage, projects.view_all, projects.acept_login, server.view, badge.admin, profile.edit
- **Protección de nivel admin**: `UpdateLevelUseCase` y `DeleteLevelUseCase` rechazan modificar el nivel admin (type 0)

## Estado de la migración (Fase 6)

### Migrado a Use Cases ✅
| Operación | Antes | Ahora |
|-----------|-------|-------|
| Login | `attempt_login()` en `auth.php` | `AuthController::login()` → `LoginUseCase` |
| Logout | `do_logout()` en `auth.php` | `AuthController::logout()` |
| Rate limiting | `check_rate_limit()` en `rate-limiter.php` | `SessionManager::isRateLimited()` |
| Crear/editar/eliminar usuario | `process_user_action()` | 3 handlers en AdminController vía CreateUser/UpdateUser/DeleteUser Use Cases |
| Crear/editar/eliminar proyecto | `process_user_action()` | SaveProjectUseCase, DeleteProjectUseCase, AssignProjectUseCase |
| Crear/editar/eliminar nivel | `process_level_action()` | CreateLevelUseCase, UpdateLevelUseCase, DeleteLevelUseCase |
| Listar proyectos | `list_projects()` + PDO directo | `ProjectScanner::scan()` + `ListProjectsForUserUseCase` |
| Editar perfil | `profile.php` | `ProfileController` → `UpdateUserUseCase` |
| Escanear filesystem | `list_projects()` global | `ProjectScanner` inyectable |

### Pendiente (deuda técnica) 🔲

| # | Item | Impacto | Archivos afectados |
|---|------|---------|-------------------|
| 1 | **2 llamadas PDO directas en AdminController** | Alto — rompe la arquitectura | `AdminController::processUpdateUser()` (UPDATE level), `AdminController::processDeleteUser()` (UPDATE Project SET user_own) |
| 2 | **`check_auth()` y `refresh_auth_cookie()` legacy** en index.php y Router | Medio — usan cookie+DB directo, sin pasar por AuthContext | `index.php:26,61`, `Router.php:71,83` |
| 3 | **Vistas llaman `get_auth_user()` y `can()` directamente** | Medio — acoplamiento a funciones globales | `views/dashboard.php`, `views/profile.php`, `views/components/management-header.php` |
| 4 | **Lecturas legacy en AdminController** — 6 funciones globales para datos de vistas | Medio — devuelven arrays, vistas esperan ese formato | `AdminController::handleUsers()` (get_all_users, get_all_levels, get_all_projects, get_client_users), `AdminController::handleLevels()` (get_all_levels_with_perms, get_all_permissions) |
| 5 | **`auth.php` todavía requerido** — define `get_auth_user()`, `can()`, `check_auth()`, `refresh_auth_cookie()` | Medio — 4 funciones globales que deberían migrarse a un `AuthContext` servicio | `index.php:16` |
| 6 | **`user-management.php` y `level-management.php` requeridos** — definen funciones de lectura legacy | Bajo — solo las funciones de lectura se usan, las de escritura ya están migradas | `AdminController.php:78,130` |
| 7 | **`type_badge()` en DashboardController** — helper legacy desde `helpers.php` | Bajo — función de presentación, podría moverse a una vista o helper de UI | `DashboardController.php:60` |
| 8 | **Archivos legacy ya no usados** — `rate-limiter.php`, `projects.php`, `profile.php` | Bajo — no se cargan en index.php pero siguen en el repo | 3 archivos sueltos en `dashboard-logic/` |
| 9 | **Seed.php usa PDO directo** en vez de Use Cases | Bajo — aceptable para script de instalación | `dashboard-logic/Database/Seed.php` |
| 10 | **fix-admin.php usa PDO directo** — CLI tool recién creada | Bajo — aceptable para herramienta de mantenimiento | `fix-admin.php` |
| 11 | **Sin tests de Presentation** — 125 tests cubren Domain, Application, Infrastructure, pero no controladores ni vistas | Medio — testing gap | `Tests/` |
| 12 | **Password en output de fix-admin.php** — `Admin123` visible en consola | Bajo — CLI tool, solo admins la ejecutan | `fix-admin.php` |

## Proyectos

- Se detectan por archivo `user-data.txt` en cada subdirectorio
- `ProjectScanner` escanea filesystem (inyectable, reemplaza a `list_projects()`)
- `ListProjectsForUserUseCase` consulta DB vía `ProjectRepositoryInterface`
- **Admin**: ve todos los proyectos, siempre ve botones Acceder/WP Admin
- **Client**: solo ve proyectos con `user_own = su UUID`, botones solo si `acept_login = 1`

## Decisiones importantes

1. **No PHP-FPM**: el `.htaccess` usa `php_value auto_prepend_file`, exclusivo de mod_php
2. **UUIDs como CHAR(36)**: no se usan INT auto-increment
3. **bcrypt**: contraseñas en `VARCHAR(255)`
4. **Insert IGNORE**: seed y migraciones son idempotentes
5. **Rutas absolutas en .htaccess**: necesarias porque el CWD cambia por subdirectorio
6. **Rate limiter usa sesiones PHP** (`$_SESSION`) vía `SessionManager`
7. **El dashboard NO es para producción**: sin CSRF, expone credenciales en el card de Acciones
8. **RBAC**: admin (type 0) siempre tiene todos los permisos. Otros niveles consultan `level_permissions`
9. **Niveles protegidos**: admin no se puede editar ni eliminar — protegido en Use Cases (Application layer), no en UI
10. **Email usa `FILTER_VALIDATE_EMAIL`**: requiere TLD (`.com`, `.org`, etc.). `admin@admin` NO es válido
11. **Tests**: `vendor/bin/phpunit --testdox` desde `dashboard-logic/`. 125 tests, 280 assertions, 0 failures
12. **fix-admin.php**: script CLI idempotente para crear/resetear el admin `admin@admin.com` / `Admin123`

## Archivos clave

| Archivo | Función |
|---------|---------|
| `index.php` | Orquestador: rate limiting, login, logout, shell HTML, Router |
| `.htaccess` | `auto_prepend_file` + bloqueo de `dashboard-logic/` |
| `auth-check.php` | Auth para subdirectorios (raw PDO, no puede usar autoloader) |
| `auth.php` | ⚠️ Legacy: `get_auth_user()`, `can()`, `check_auth()`, `refresh_auth_cookie()` |
| `user-management.php` | ⚠️ Legacy: `get_all_users()`, `get_all_levels()`, `get_all_projects()`, `get_client_users()` |
| `level-management.php` | ⚠️ Legacy: `get_all_levels_with_perms()`, `get_all_permissions()` |
| `helpers.php` | `type_badge()`, `encriptar()`, `desencriptar()`, `get_os()` |
| `rate-limiter.php` | 🔴 Obsoleto — reemplazado por SessionManager |
| `projects.php` | 🔴 Obsoleto — reemplazado por ProjectScanner |
| `profile.php` | 🔴 Obsoleto — reemplazado por ProfileController |
| `fix-admin.php` | CLI tool: crea/resetea admin `admin@admin.com` / `Admin123` |
| `setup.sh` | Instalador automático multi-distro |
| `SETUP.md` | Guía manual para IA (macOS, Windows, etc.) |

## Gotchas

- `php_value auto_prepend_file` con ruta relativa se resuelve desde el CWD del script, no del `.htaccess` → **siempre usar ruta absoluta**
- Bootstrap tabs sin `show active` están ocultos aunque sean el único pane
- `auth-check.php` no puede usar `Connection::get()` porque corre como `auto_prepend_file` sin autoloader → usa `new PDO(...)` directo
- Las cookies con `SameSite=Lax` no se envían en `target="_blank"` → los links del dashboard usan `target="_blank"` pero la cookie se envía igual porque es same-site
- `INSERT IGNORE` no actualiza filas existentes → si un usuario admin ya existe con otro password, el seed NO lo sobreescribe
- `AuthController::class` en ServiceContainer necesita el `use` import porque el namespace de ServiceContainer es `Dashboard\Presentation`, no `Dashboard\Presentation\Controller`
- `filter_var(..., FILTER_VALIDATE_EMAIL)` rechaza emails sin TLD (ej: `admin@admin` no es válido, `admin@admin.com` sí)
