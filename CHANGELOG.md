# Changelog

## 1.2.0 (2026-06-08)

### Auth y base de datos

- **Migración MySQL**: reemplazo total de SQLite/AES por MySQL con tabla USERS y bcrypt
- **Auto-migrate**: `Connection::get()` ejecuta `Migration::apply()` al conectar por primera vez
- **Seed data**: `seed.php` crea nivel admin y usuario `admin@admin / Admin123`
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
