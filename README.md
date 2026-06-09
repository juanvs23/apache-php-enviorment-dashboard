# Dev Dashboard

Panel de administración centralizado para entornos de desarrollo con múltiples proyectos PHP (WordPress, Laravel, etc.).

## Características

- **Login MySQL** con rate limiting (5 intentos / 15 min) y bcrypt
- **Gestión de usuarios** con niveles admin/cliente y CRUD desde el dashboard
- **Listado automático** de proyectos basado en archivos `user-data.txt`
- **Asignación de proyectos** — cada usuario cliente ve solo sus proyectos
- **Control de acceso** — flag `acept_login` por proyecto para habilitar/deshabilitar botones
- **Auto-login a WordPress** — un click sin pasar por `wp-login.php`
- **Protección de subdirectorios** — requieren cookie del dashboard vía `auth-check.php`
- Información del servidor (PHP, SO, disco, servicios)
- Badges por tipo de proyecto (WordPress, Laravel, Symfony, phpMyAdmin, static)
- Sesión persistente por cookie (7 días)

## Requisitos

| Requisito | Versión | Notas |
|-----------|---------|-------|
| Apache | 2.4+ | Con **mod_php** (NO PHP-FPM) y `mod_rewrite` |
| PHP | 8.0+ | |
| MySQL | 8.0+ | Puerto 3306 por defecto |
| Composer | 2.x | Solo para dev (PHPUnit) |
| Bootstrap | 5.x | Incluido en `assets/` |

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
| `openssl` | `random_bytes()` para UUIDs |
| `mbstring` | Manejo de strings |
| `session` | Rate limiter y PHP sessions |
| `json` | Codificación de respuestas |

## Instalación

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
Para seedear datos iniciales, ejecutá `seed.php`:

```bash
php /ruta/al/proyecto/seed.php
```

Esto crea:

| Usuario | Email | Contraseña | Nivel |
|---------|-------|------------|-------|
| Admin | `admin@admin` | `Sinal14.` | admin |
| Cliente | `cliente@test.com` | `cliente123` | client |

### Agregar proyectos

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
- **`seed.php`** solo se corre una vez para poblar datos iniciales (usuarios admin y cliente)

**Tablas creadas:**

```
levels          — niveles de usuario (admin, client, etc.)
  ├── levelsID CHAR(36) PK
  ├── level_name VARCHAR(255)
  └── level_type TINYINT (0=admin, 1=client)

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
├── index.php                           # Orquestador principal
├── seed.php                            # Seeder de datos iniciales
├── .env                                # Credenciales (NO trackeado)
├── .env.example                        # Template de credenciales
├── .htaccess                           # Reglas Apache + auto_prepend_file
├── assets/                             # Bootstrap 5
│   ├── css/
│   └── js/
├── dashboard-logic/
│   ├── bootstrap.php                   # Session, autoload PSR-4, constantes
│   ├── env-loader.php                  # Carga .env en $_ENV
│   ├── helpers.php                     # Crypto, get_os, type_badge
│   ├── rate-limiter.php                # Control de intentos de login
│   ├── auth.php                        # Login MySQL, cookies, sesiones
│   ├── auth-check.php                  # Prepend de auth para subdirectorios
│   ├── projects.php                    # Listado de proyectos (filesystem)
│   ├── user-management.php             # CRUD usuarios + asignación proyectos
│   ├── wp-auto-login.php               # Auto-login WordPress
│   ├── composer.json                   # PHPUnit (dev)
│   ├── Database/
│   │   ├── Connection.php              # Singleton PDO (auto-migrate)
│   │   ├── Migration.php               # Schema DDL
│   │   └── Seed.php                    # Datos iniciales
│   ├── Tests/
│   │   └── bootstrap.php               # Bootstrap de PHPUnit
│   └── views/
│       ├── dashboard.php               # Vista principal autenticada
│       ├── login.php                   # Formulario de login
│       ├── user-management.php         # Gestión de usuarios y proyectos
│       └── components/
│           ├── server-info.php         # Info del servidor
│           └── project-card.php        # Tarjeta de proyecto
├── docs/
│   ├── mejoras.md
│   └── staging-checklist.md
├── README.md
├── CHANGELOG.md
└── .gitignore
```

## Flujo de autenticación

```
Request a /twilight/wp-admin/
  → .htaccess (AllowOverride)
  → auto_prepend_file → dashboard-logic/auth-check.php
    ├─ ¿Es index.php, wp-auto-login o assets/? → permite
    ├─ ¿Cookie project_user válida? → permite, carga WordPress
    └─ No → redirige a /index.php?redirect=...

Request a /
  → index.php → auth.php
    ├─ Login: email + password → query USERS, password_verify()
    ├─ Cookie: base64(userID UUID), 7 días
    └─ Cada request: lookup por UUID en USERS, obtiene nivel y permisos
```

**Niveles de acceso:**

| Nivel | Ve todos los proyectos | Ve botones Acceder/WP Admin | Accede a gestión de usuarios |
|-------|:---:|:---:|:---:|
| admin (`level_type=0`) | ✅ | ✅ siempre | ✅ |
| client (`level_type=1`) | ❌ solo asignados | ❌ solo si `acept_login=1` | ❌ |

## Auto-login WordPress

Cada proyecto WordPress detectado (tiene `wp-config.php`) y con `acept_login=1` muestra:

- **Acceder** — auto-login al sitio WordPress sin pasar por `wp-login.php`
- **WP Admin** — acceso directo al panel `/wp-admin/`

El auto-login busca el primer administrador en la DB de WordPress y genera una cookie de sesión.

## Seguridad

- El `.htaccess` bloquea acceso directo a `.env` y `dashboard-logic/` (solo `wp-auto-login.php` pasa)
- Las contraseñas se almacenan con bcrypt en la tabla USERS
- La cookie `project_user` contiene base64(UUID) — se valida contra la DB en cada request
- Rate limiting en login (5 intentos, 15 min de bloqueo, por sesión PHP)
- Las redirecciones post-login validan que sean internas (empiecen con `/`)

## Licencia

Uso interno — entorno de desarrollo.
