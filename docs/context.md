# Contexto del proyecto para IA

## Stack actual

- **Apache 2.4** con `mod_php` (NO PHP-FPM) y `mod_rewrite`
- **PHP 8.3** con `pdo_mysql`, `openssl`, `mbstring`, `session`, `json`, `gd`, `zip`, `intl`, `curl`, `xml`
- **MySQL 8.0** — base de datos `apache-dashboard` con tablas `levels`, `USERS`, `Project`, `permissions`, `level_permissions`
- **Bootstrap 5** en `assets/` (sin CDN)

## Arquitectura

- `index.php` — orquestador delgado, rutea a vistas
- `dashboard-logic/` — capa de lógica (auth, proyectos, usuarios, rate limiter)
  - `Database/` — Connection (Singleton PDO), Migration (DDL), Seed
  - `views/` — vistas PHP puras con componentes
  - `Tests/` — estructura de PHPUnit (sin tests implementados aún)
- Autoload PSR-4: namespace `Dashboard\` → `dashboard-logic/`
- `.env` cargado por `env-loader.php` (línea por línea, sin parseo complejo)

## Autenticación

- **Login**: email + contraseña contra tabla `USERS`, `password_verify()` con bcrypt
- **Cookie**: `base64(userID UUID)`, 7 días, se valida en cada request
- **Levels**: `level_type` 0 = admin (acceso total), 1 = permisos por tabla
- **Permisos RBAC**: helper `can('permiso')` en auth.php. Tablas `permissions` + `level_permissions`. Admin (type 0) tiene todos los permisos automáticamente. Los demás niveles consultan la tabla.
- **8 permisos**: users.manage, users.edit_same_level, projects.manage, projects.view_all, projects.acept_login, server.view, badge.admin, profile.edit
- **Gestión de niveles**: `?users=1&tab=levels` (solo admin). Layout compartido en `views/components/management-*.php`

## Proyectos

- Se detectan por archivo `user-data.txt` en cada subdirectorio
- `list_projects()` en `projects.php` escanea filesystem
- `get_all_projects()` en `user-management.php` consulta tabla `Project`
- Matching entre filesystem y DB: `strtolower(dir) == strtolower(project_name)`
- **Admin**: ve todos los proyectos, siempre ve botones Acceder/WP Admin
- **Client**: solo ve proyectos con `user_own = su UUID`, botones solo si `acept_login = 1`

## Decisiones importantes

1. **No PHP-FPM**: el `.htaccess` usa `php_value auto_prepend_file`, exclusivo de mod_php
2. **UUIDs como CHAR(36)**: no se usan INT auto-increment
3. **bcrypt**: contraseñas en `VARCHAR(255)`
4. **Insert IGNORE**: seed y migraciones son idempotentes
5. **Rutas absolutas en .htaccess**: necesarias porque el CWD cambia por subdirectorio
6. **No sesiones PHP para auth**: solo cookie + DB lookup
7. **Rate limiter usa sesiones PHP** (`$_SESSION['login_attempts']`)
8. **El dashboard NO es para producción**: sin CSRF, expone credenciales en el card de Acciones
9. **RBAC**: admin (type 0) siempre tiene todos los permisos vía `can()`. Otros niveles consultan `level_permissions`
10. **Niveles protegidos**: admin no se puede editar ni eliminar desde la UI. Client/operator/revisor son editables

## Archivos clave

| Archivo | Función |
|---------|---------|
| `index.php` | Orquestador principal |
| `.htaccess` | `auto_prepend_file` + bloqueo de `dashboard-logic/` |
| `auth-check.php` | Auth para subdirectorios (raw PDO) |
| `auth.php` | Login, logout, get_auth_user (con Connection) |
| `user-management.php` | CRUD usuarios + asignación proyectos |
| `projects.php` | `list_projects()` desde filesystem |
| `Connection.php` | Singleton PDO + auto-migrate |
| `Migration.php` | Schema DDL (`CREATE TABLE IF NOT EXISTS`) |
| `Seed.php` | Datos iniciales: 4 niveles, 8 permisos, 3 usuarios |
| `level-management.php` | CRUD de niveles y permisos (solo admin) |
| `profile.php` | Edición de perfil propio |
| `setup.sh` | Instalador automático multi-distro |
| `SETUP.md` | Guía manual para IA (macOS, Windows, etc.) |

## Extensiones PHP y para qué sirven

| Extensión | Quién la necesita |
|-----------|-------------------|
| `pdo_mysql` | Conexión a MySQL (todos) |
| `gd`, `imagick` | Imágenes (WordPress) |
| `zip` | Plugins/themes (WordPress), Composer (Laravel) |
| `intl` | Internacionalización (WordPress, Laravel) |
| `curl` | HTTP requests (WordPress updates, Laravel) |
| `xml` | XML parsing (WordPress, Laravel) |
| `mbstring` | UTF-8 (WordPress, Laravel) |
| `bcmath` | Cálculos precisos (Laravel) |
| `openssl` | `random_bytes()` para UUIDs (dashboard) |
| `session` | Rate limiter (dashboard) |

## Gotchas

- `php_value auto_prepend_file` con ruta relativa se resuelve desde el CWD del script, no del `.htaccess` → **siempre usar ruta absoluta**
- Bootstrap tabs sin `show active` están ocultos aunque sean el único pane
- `auth-check.php` no puede usar `Connection::get()` porque corre como `auto_prepend_file` sin autoloader → usa `new PDO(...)` directo
- Las cookies con `SameSite=Lax` no se envían en `target="_blank"` → los links del dashboard usan `target="_blank"` pero la cookie se envía igual porque es same-site
- El seed NO crea el usuario `cliente@test.com` — es solo de prueba, se crea manualmente
- `Project.user_own` acepta NULL (cambiado de NOT NULL en la migración)
