# Dev Dashboard

Panel de administración centralizado para entornos de **desarrollo y staging** con múltiples proyectos PHP (WordPress, Laravel, etc.).

> ⚠️ **Solo para entornos de desarrollo y staging.** Este sistema no está diseñado
> para producción. Expone credenciales, carece de CSRF y confía en la red local/privada.

> ⚠️ **Requiere Apache con mod_php.** No funciona con nginx ni PHP-FPM.
> El sistema depende de `php_value auto_prepend_file` en `.htaccess` y de
> `mod_rewrite` por directorio — características exclusivas de Apache.

## Características

- **Login MySQL** con rate limiting (5 intentos / 15 min) y bcrypt
- **RBAC con 8 permisos dinámicos** — niveles admin, operator, revisor, client con permisos granulares
- **Gestión de usuarios** con CRUD desde el dashboard y perfil propio
- **Crear proyectos desde la UI** — HTML (vanilla/Vite), WordPress, Laravel, clonar desde GitHub
- **Gestión npm integrada** — instalar dependencias, iniciar/detener dev server, proxy Vite automático
- **Listado automático** de proyectos basado en archivos `user-data.txt`
- **Asignación de proyectos** — cada usuario ve solo sus proyectos asignados
- **Auto-login a WordPress** — un click sin pasar por `wp-login.php`
- **Protección de subdirectorios** — requieren cookie del dashboard vía `auth-check.php`
- Información del servidor (PHP, SO, disco, servicios)
- Badges por tipo de proyecto (WordPress, Laravel, Symfony, phpMyAdmin, static)
- Sesión persistente por cookie (7 días)
- Arquitectura limpia (Clean Architecture) con 212 tests PHPUnit

## Requisitos

### Distros soportadas

El script `setup.sh` instala automáticamente todo lo necesario en:

| Distro | Package Manager | Soporte |
|--------|:---:|:---:|
| **Ubuntu** (22.04 / 24.04 LTS) | `apt` | ✅ Completo |
| **Debian** (12+) | `apt` | ✅ Completo |
| **RHEL / Rocky Linux / AlmaLinux** (9+) | `dnf` | ✅ Requiere repo Remi |
| **Fedora** (40+) | `dnf` | ✅ Completo |
| **openSUSE** (Tumbleweed) | `zypper` | ✅ Completo |
| **Arch Linux** | `pacman` | ✅ Completo |

> **No soportado:** Alpine Linux (usa `musl` en vez de `glibc` y `apk` en vez del package manager tradicional). No es un target común en VPS de hosting web.

### Versiones de software

| Requisito | Versión | Notas |
|-----------|---------|-------|
| Apache | 2.4+ | Con **mod_php** (NO PHP-FPM) y `mod_rewrite` |
| PHP | 8.0+ | |
| MySQL | 8.0+ | Puerto 3306 por defecto |
| Composer | 2.x | Solo para dev (PHPUnit) |
| Bootstrap | 5.x | Incluido en `assets/` |

**Opcionales** (para crear proyectos desde el dashboard):

| Herramienta | Para |
|-------------|------|
| Git | Clonar repositorios desde GitHub |
| Node.js + npm | Proyectos con Vite.js, gestión npm |
| Composer | Crear proyectos Laravel |
| WP-CLI | Crear proyectos WordPress |

> ⚠️ **Importante**: el `.htaccess` usa `php_value auto_prepend_file`. Esta directiva
> **solo funciona con `mod_php`** (Apache + PHP como módulo). Si usás PHP-FPM, el
> `auto_prepend_file` debe configurarse en el VirtualHost con `php_admin_value`.

**Configuración mínima de Apache para el VirtualHost:**

```apache
<VirtualHost *:80>
    DocumentRoot /ruta/al/proyecto
    <Directory /ruta/al/proyecto>
        AllowOverride All     # Necesario para que .htaccess funcione
        Require all granted
    </Directory>
</VirtualHost>
```

**Extensiones PHP requeridas:**

| Extensión | Uso |
|-----------|-----|
| `pdo_mysql` | Conexión a MySQL |
| `openssl` | `random_bytes()` y bcrypt |
| `mbstring` | Manejo de strings multibyte |
| `session` | Rate limiter y PHP sessions |
| `json` | Codificación de respuestas |
| `gd` | Manipulación de imágenes (WordPress) |
| `zip` | Extracción de archivos (Composer, WP-CLI) |
| `intl` | Internacionalización (Laravel) |
| `curl` | Descargas HTTP (WP-CLI, GitHub) |
| `xml` | Parsing XML (WordPress, Laravel) |

## Instalación

### Automática (recomendado)

```bash
git clone <repo-url> /ruta/al/document-root
cd /ruta/al/document-root
./setup.sh
```

El script instala TODO lo necesario: Apache, PHP 8.0+, MySQL, extensiones, módulos, crea la base de datos, configura `.env`, actualiza `.htaccess` y siembra los datos iniciales.

#### ¿Qué hace `setup.sh` paso a paso?

| Paso | Acción |
|:---:|---|
| 1 | Detecta la distro (Ubuntu, Debian, Fedora, etc.) y el package manager (`apt`, `dnf`, etc.) |
| 2 | Si no hay PHP 8.0+, lo instala junto con Apache, MySQL y las 10 extensiones |
| 3 | Instala Git y Node.js (condicional — solo si no existen) |
| 4 | Activa `mod_rewrite`, `mod_php` y `mod_proxy_http` en Apache |
| 5 | Verifica una a una las extensiones PHP necesarias |
| 6 | Crea `.env` pidiéndote las credenciales de MySQL de forma interactiva |
| 7 | Crea la base de datos y el usuario MySQL (si no es root) |
| 8 | Actualiza el `.htaccess` con la ruta absoluta real de `auth-check.php` |
| 9 | Ejecuta el seed (crea niveles, permisos y usuarios por defecto) |
| 10 | Configura el VirtualHost de Apache (`DocumentRoot` + `AllowOverride All`) |
| 11 | Configura sudoers para que www-data pueda ejecutar npm |
| 12 | Reinicia Apache y muestra el resumen final |

#### Significado de los íconos

| Ícono | Significado |
|:---:|---|
| `[✓]` | Todo OK — el paso se completó o ya estaba listo |
| `[!]` | Advertencia — algo falta y el script lo está instalando/configurando |
| `[✗]` | Error — algo falló y necesita intervención manual |

Si al finalizar ves `✅ Dashboard instalado`, todo salió bien. Si hay `[✗]`,
leé la sección de troubleshooting abajo.

#### Troubleshooting de `setup.sh`

| Problema | Causa probable | Solución |
|----------|---------------|----------|
| `[✗] Apache no está instalado` | El paso 1 falló o se salteó | Instalar manualmente: `sudo apt install apache2` |
| `[✗] Extensión pdo_mysql no encontrada` | Falta `php8.x-mysql` | `sudo apt install php8.3-mysql` |
| `[!] Activando mod_rewrite...` y no aparece `[✓]` | `a2enmod` falló | `sudo a2enmod rewrite && sudo systemctl restart apache2` |
| Error `could not find driver` | PDO MySQL no cargado | Descomentar `extension=pdo_mysql` en `php.ini` |
| Error 500 en el dashboard | `.htaccess` o `auto_prepend_file` mal | Verificar ruta en `.htaccess`: debe ser absoluta |
| `sudo: a terminal is required` | sudo pide contraseña y no hay terminal | Ejecutar con `sudo -S` o configurar NOPASSWD |
| No se conecta a MySQL | Credenciales incorrectas o MySQL no iniciado | `sudo systemctl start mysql` y verificar `.env` |
| Apache no carga después del script | Conflicto de puertos | `sudo netstat -tlnp \| grep :80` |

¿Estás en macOS, Windows o una distro no soportada? Leé [`SETUP.md`](SETUP.md) — una guía paso a paso diseñada para que vos (o una IA) puedan instalar el dashboard manualmente en cualquier sistema.

### Manual

```bash
# 1. Clonar el repo en el DocumentRoot de Apache
git clone <repo-url> /ruta/al/document-root

# 2. Crear base de datos MySQL
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS \`apache-dashboard\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Configurar .env
cp .env.example .env
# Editar .env con las credenciales reales (ver sección Configuración)

# 4. Instalar dependencias de desarrollo (opcional, solo para tests)
cd /ruta/al/document-root/dashboard-logic && composer install

# 5. Verificar Apache
# Asegurate que el VirtualHost tenga:
#   DocumentRoot /ruta/al/document-root
#   <Directory /ruta/al/document-root>
#       AllowOverride All
#       Require all granted
#   </Directory>

# 6. ⚠️ Actualizar ruta absoluta en .htaccess
# Editar .htaccess y cambiar la línea:
#   php_value auto_prepend_file /ruta/real/del/proyecto/dashboard-logic/auth-check.php

# 7. Reiniciar Apache y acceder al dashboard
sudo systemctl restart apache2
# Abrir http://localhost en el navegador
```

### Primer acceso

El sistema crea automáticamente las tablas al conectar por primera vez (auto-migrate).
Para seedear datos iniciales, ejecutá el seed desde el autoloader:

```bash
php -r "require 'dashboard-logic/bootstrap.php'; \Dashboard\Database\Seed::run(\Dashboard\Database\Connection::get());"
```

Esto crea los niveles (admin, operator, revisor, client), los 8 permisos del sistema, y usuarios de prueba con contraseñas por defecto.

> ⚠️ **Cambiá las contraseñas por defecto después del primer login.**
> Creá tu propio usuario admin desde el dashboard (Usuarios → Crear Usuario)
> y luego eliminá o cambiá la clave de los usuarios de prueba.

### Servicios adicionales

```bash
# phpMyAdmin
sudo bash setup-phpmyadmin.sh

# PostgreSQL + pgAdmin4
sudo bash setup-postgres.sh

# Todo junto
sudo bash setup-services-all.sh
```

Guía completa para IAs en [`SETUP-SERVICES.md`](SETUP-SERVICES.md).

### Crear proyectos desde el dashboard

Los administradores pueden crear proyectos directamente desde la interfaz web:

1. Andá a la pestaña **🆕 Nuevo Proyecto** (solo visible para admin)
2. Elegí el tipo: **HTML** (vanilla o Vite.js), **WordPress**, **Laravel**, o **Clonar desde GitHub**
3. Completá los datos y clickeá **Crear**

> 💡 Cada proyecto se crea con `git init`, `.gitignore`, `chmod -R 0777`, y `user-data.txt` listo para el dashboard.

#### Tipos de proyecto

| Tipo | Requiere | Descripción |
|---|---|---|
| 🌐 HTML vanilla | Nada | `index.html` + `assets/css/` + `assets/js/` |
| ⚡ HTML + Vite.js | Node.js + npm | `package.json`, `vite.config.js`, `src/` |
| 📝 WordPress | WP-CLI + MySQL | `wp core download` + `wp config create` + `wp core install` |
| 🔷 Laravel | Composer + MySQL | `composer create-project laravel/laravel` |
| 📥 Clonar GitHub | Git | `git clone` + auto-detección de tipo + `npm install` automático |

### Gestionar proyectos con Node.js

Si un proyecto tiene `package.json`, el dashboard muestra botones para administrarlo:

| Botón | Acción |
|---|---|
| 📦 Instalar | Ejecuta `npm install` en el proyecto |
| 🚀 Iniciar | Levanta el dev server de Vite en segundo plano |
| ⏹ Detener | Apaga el dev server |
| 🔗 Dev | Abre el proyecto por el proxy de Apache (no por puerto 5173) |

> 💡 Al iniciar el dev server, Apache actúa como proxy. Podés acceder desde `http://localhost/mi-proyecto/` sin cambiar de puerto.

### Eliminar proyectos

Cada tarjeta de proyecto tiene un botón 🗑 en el header. Solo visible para administradores. Borra el directorio completo.

### Agregar proyectos manualmente

Cada subdirectorio necesita un archivo `user-data.txt`:

```bash
mkdir -p mi-proyecto
cat > mi-proyecto/user-data.txt <<EOF
user: admin
password: secreto
type: wordpress
EOF
```

Luego desde el dashboard > Usuarios > Proyectos, creá el proyecto en la DB y asignalo a un usuario.

## Configuración

### `.env`

| Variable | Descripción | Default | Obligatorio |
|----------|-------------|---------|:-----------:|
| `DB_DRIVER` | Driver PDO | `mysql` | ❌ |
| `DB_HOST` | Host de MySQL | `localhost` | ❌ |
| `DB_PORT` | Puerto de MySQL | `3306` | ❌ |
| `DB_NAME` | Nombre de la base de datos | `apache-dashboard` | ❌ |
| `DB_USER` | Usuario de MySQL | — | ✅ |
| `DB_PASS` | Contraseña de MySQL | — | ✅ |
| `PMA_URL` | URL de phpMyAdmin (opcional, activa el botón) | — | ❌ |

**Claves de acceso** (se muestran en el dashboard — solo informativas):

| Variable | Descripción |
|----------|-------------|
| `PGA_EMAIL` | Email de pgAdmin4 |
| `PGA_PASS` | Contraseña de pgAdmin4 |
| `MYSQL_USER` | Usuario de MySQL |
| `MYSQL_PASS` | Contraseña de MySQL |
| `PMA_USER` | Usuario de phpMyAdmin |
| `PMA_PASS` | Contraseña de phpMyAdmin |

> ⚠️ **NUNCA** commitees el `.env` real. El repo incluye `.env.example` como template.

### Estructura de la base de datos

El sistema usa **auto-migrate**: al conectar por primera vez a MySQL, `Connection::get()` llama a `Migration::apply()`, que ejecuta los `CREATE TABLE IF NOT EXISTS`. Si las tablas ya existen, no las toca.

Esto significa que:

- **No necesitás correr migraciones manualmente** — al abrir el dashboard se crea todo solo
- **Si agregás una columna nueva** a `Migration.php`, como la tabla ya existe, `IF NOT EXISTS` no la crea — en ese caso ejecutá un `ALTER TABLE` manual o recreá la DB
- **El seed** solo se corre una vez para poblar datos iniciales (niveles, permisos y usuarios)

**Tablas creadas:**

```
levels          — niveles de usuario
  ├── levelsID CHAR(36) PK
  ├── level_name VARCHAR(255) UNIQUE
  └── level_type TINYINT (0=admin con todos los permisos)

permissions     — catálogo de permisos (8 permisos)
  ├── id INT PK AUTO_INCREMENT
  ├── perm_key VARCHAR(64) UNIQUE
  └── perm_label VARCHAR(128)

level_permissions — permisos asignados a cada nivel
  ├── levelID CHAR(36) FK → levels
  └── perm_id INT FK → permissions

USERS           — usuarios del sistema
  ├── userID CHAR(36) PK
  ├── email VARCHAR(255) UNIQUE
  ├── name VARCHAR(255) NULL
  ├── pass VARCHAR(255) — bcrypt
  └── level CHAR(36) FK → levels.levelsID

Project         — proyectos y asignaciones
  ├── id CHAR(36) PK
  ├── project_name TEXT
  ├── user_own CHAR(36) NULL FK → USERS.userID
  └── acept_login TINYINT(1) DEFAULT 0

auth_logs       — registro de accesos (login/logout)
  ├── id INT PK AUTO_INCREMENT
  ├── email VARCHAR(255)
  ├── action VARCHAR(20) — login_success | login_failed | logout
  ├── ip_address VARCHAR(45)
  ├── user_agent VARCHAR(512) NULL
  └── created_at TIMESTAMP
```

### `user-data.txt`

Por proyecto. Si existe, el proyecto aparece en el dashboard.

```
user: admin              ← se muestra en la tarjeta
password: pass123        ← se muestra en la tarjeta
type: wordpress          ← define el badge (wordpress, laravel, symfony, phpmyadmin, static)
```

## Estructura del proyecto

```
/
├── index.php                           # Orquestador principal (ruteo + shell HTML)
├── fix-admin.php                       # CLI: resetear usuario admin
├── .env / .env.example                 # Credenciales (NO trackeado)
├── .htaccess                           # Reglas Apache + auto_prepend_file
├── auth-check.php                      # Auth para subdirectorios
├── robots.txt                          # Bloqueo de crawlers
├── assets/                             # Bootstrap 5 (CSS + JS)
├── dashboard-logic/
│   ├── bootstrap.php                   # Autoload PSR-4 + sesión + constantes
│   ├── env-loader.php                  # Carga .env en $_ENV
│   ├── Domain/
│   │   ├── Entity/                     # User, Level, Project, Permission
│   │   ├── ValueObject/                # Email, LevelType
│   │   └── Exception/                  # DomainException
│   ├── Application/
│   │   ├── UseCase/                    # 13 Use Cases (Auth, User, Project, Level, Permission)
│   │   └── Repository/                # Interfaces de repositorio
│   ├── Infrastructure/
│   │   ├── Persistence/               # Repositorios MySQL (User, Level, Project, Permission)
│   │   ├── Filesystem/                # ProjectScanner, ProjectCreator
│   │   ├── Session/                   # SessionManager (rate limiting)
│   │   └── Auth/                      # AuthContext, LegacyReader
│   ├── Presentation/
│   │   ├── Controller/                # AuthController, DashboardController, AdminController, ProfileController
│   │   ├── Router.php                 # Ruteo de requests
│   │   └── ServiceContainer.php       # DI container con lazy init
│   ├── Database/
│   │   ├── Connection.php             # Singleton PDO + auto-migrate
│   │   ├── Migration.php              # Schema DDL
│   │   └── Seed.php                   # Datos iniciales idempotentes
│   ├── Tests/                         # PHPUnit (212 tests, 506 assertions)
│   └── views/                         # Vistas PHP puras
│       ├── dashboard.php
│       ├── login.php
│       └── components/                # server-info, project-card, management-header/footer
├── views/                             # Vistas compartidas (partials)
├── docs/                              # Documentación y roadmaps
├── migrations/                        # Migraciones SQL manuales
├── README.md
└── CHANGELOG.md
```

## Flujo de autenticación

```
Request a /proyecto/wp-admin/
  → .htaccess (AllowOverride)
  → auto_prepend_file → auth-check.php
    ├─ ¿Es index.php, assets/ o crawler conocido (Lighthouse)? → permite
    ├─ ¿Cookie project_user válida? → AuthContext verifica contra DB → permite
    └─ No → redirige a /index.php?redirect=...

Request a /
  → index.php → ServiceContainer → AuthController
    ├─ Login: LoginUseCase → Email ValueObject + UserRepository + bcrypt
    ├─ Cookie: project_user con email del usuario, 7 días
    └─ Cada request: AuthContext valida cookie, carga usuario, verifica permisos vía can()
```

**Niveles y permisos (RBAC):**

| Nivel | Permisos |
|---|---|
| admin | Todos (8/8) |
| operator | users.manage, projects.*, server.view, profile.edit |
| revisor | projects.view_all, projects.acept_login, profile.edit (solo lectura) |
| client | profile.edit |

Los permisos son dinámicos — se gestionan desde **Usuarios → Niveles y Permisos** en el dashboard.

## Auto-login WordPress

Cada proyecto WordPress detectado (tiene `wp-config.php`) y con `acept_login=1` muestra:

- **Acceder** — auto-login al sitio WordPress sin pasar por `wp-login.php`
- **WP Admin** — acceso directo al panel `/wp-admin/`

El auto-login busca el primer administrador en la DB de WordPress y genera una cookie de sesión.

## Seguridad

- El `.htaccess` bloquea acceso directo a `.env`, `dashboard-logic/`, y archivos sensibles
- Las contraseñas se almacenan con bcrypt en la tabla USERS
- La cookie `project_user` se valida contra la DB en cada request vía `AuthContext`
- Rate limiting en login (5 intentos, 15 min de bloqueo, por sesión PHP)
- Las redirecciones post-login validan que sean internas (empiecen con `/`)
- `robots.txt` con `Disallow: /` + bloqueo de crawlers vía `.htaccess` (403)
- `auth-check.php` permite bypass para Google PageSpeed, Lighthouse y GTmetrix
- Permisos RBAC dinámicos: 8 permisos granulares gestionables desde la UI
- Protección de nivel admin: no se puede editar ni eliminar desde la UI

## Hardening para VPS / staging

Si el dashboard se expone a internet (incluso en staging), estas medidas son responsabilidad de quien despliega. El proyecto no las aplica automáticamente porque son específicas del entorno.

### 🔴 Recomendado antes de exponer

| Medida | Guía |
|---|---|
| **HTTPS** (Let's Encrypt) | `certbot --apache -d tudominio.com`. Las cookies y contraseñas viajan cifradas. |
| **Firewall** (ufw) | Solo puertos 22, 80, 443. `ufw default deny incoming && ufw allow ssh/http/https && ufw enable` |
| **Restringir pgAdmin4** | Agregar `AuthType Basic` + `htpasswd` en el `Location /pgadmin4` del VirtualHost. O limitar por IP. |
| **Credenciales fuertes** | Cambiar TODOS los valores dummy del `.env` por contraseñas generadas. Nada de `Admin123`. |

### 🟡 Recomendaciones adicionales

| Medida | Guía |
|---|---|
| **Usuarios de BD dedicados** | Crear usuarios MySQL con permisos mínimos por aplicación. No usar `root` para todo. |
| **Rate limiting por IP** (Fail2ban) | Responsabilidad del desarrollador. Instalar fail2ban en el servidor y configurar una jail para el dashboard. Los intentos fallidos ya se registran en `auth_logs` para monitoreo. |
| **Permisos de archivos** | En producción: `chown root:www-data` + `chmod 755`. En staging colaborativo mantener 0777. |
| **Cerrar puertos de BD** | `bind-address = 127.0.0.1` en `my.cnf` y `postgresql.conf`. MySQL y PostgreSQL solo deberían escuchar en localhost. |

### 📋 Checklist completa

Ver [`docs/staging-checklist.md`](docs/staging-checklist.md) para el detalle completo con comandos.

## Licencia

Uso interno — entorno de desarrollo.
