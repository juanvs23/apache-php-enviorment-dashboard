# Dev Dashboard

Panel de administración centralizado para entornos de desarrollo con múltiples proyectos PHP (WordPress, Laravel, etc.).

## Características

- **Login protegido** con rate limiting (5 intentos / 15 min)
- **Listado automático** de proyectos basado en archivos `user-data.txt`
- **Auto-login a WordPress** — un click sin pasar por `wp-login.php`
- **Protección de proyectos** — los subdirectorios requieren sesión del dashboard
- **Card Acciones** con estado de servicios (PostgreSQL, MySQL), acceso rápido (phpinfo, phpMyAdmin, pgAdmin4) y claves configurables desde `.env`
- Información del servidor (PHP, SO, disco, etc.)
- Badges por tipo de proyecto (WordPress, Laravel, Symfony, phpMyAdmin, static)
- Sesión persistente por cookie (7 días)

## Requisitos

| Requisito | Versión |
|-----------|---------|
| Apache | 2.4+ con mod_rewrite |
| PHP | 8.0+ |
| Extensión PHP | `openssl` |
| Bootstrap | 5.x (incluido en `assets/`) |

## Instalación

```bash
# 1. Clonar el repo
git clone <repo-url> /ruta/al/document-root

# 2. Configurar credenciales
cp .env.example .env
# Editar .env con tus credenciales

# 3. Verificar que Apache tenga AllowOverride All
# para el DocumentRoot (ya incluido en el repo)

# 4. ⚠️ Actualizar ruta absoluta en .htaccess
# Editar .htaccess y cambiar:
#   php_value auto_prepend_file /ruta/actual/dashboard-logic/auth-check.php
# por la ruta real del proyecto en tu servidor.

# 5. Agregar proyectos
# Cada subdirectorio con un archivo user-data.txt:
mkdir -p mi-proyecto
cat > mi-proyecto/user-data.txt <<EOF
user: admin
password: secreto
type: wordpress
EOF
```

## Configuración

### `.env`

| Variable | Descripción | Default |
|----------|-------------|---------|
| `DASHBOARD_KEY` | Clave de encriptación AES | `$z]7hB92d1pT` |
| `DASHBOARD_CLAVE` | Contraseña del dashboard | `Sinal14.` |
| `PMA_URL` | URL personalizada de phpMyAdmin | — |
| `PGA_EMAIL` | Email de pgAdmin4 | — |
| `PGA_PASS` | Contraseña de pgAdmin4 | — |
| `DB_USER` | Usuario de PostgreSQL | — |
| `DB_PASS` | Contraseña de PostgreSQL | — |
| `MYSQL_USER` | Usuario de MySQL | — |
| `MYSQL_PASS` | Contraseña de MySQL | — |
| `PMA_USER` | Usuario de phpMyAdmin | — |
| `PMA_PASS` | Contraseña de phpMyAdmin | — |

> **NUNCA** commitees el `.env` real. El repo incluye `.env.example` como template.

### `user-data.txt`

Por proyecto, opcional. Si existe, el proyecto aparece en el dashboard.

```
user: admin              ← se muestra en la tarjeta
password: pass123        ← se muestra en la tarjeta
type: wordpress          ← define el badge de color (wordpress, laravel, symfony, phpmyadmin, static)
```

Si el archivo está vacío o falta, el proyecto igual aparece (sin badge ni credenciales).

## Estructura del proyecto

```
/
├── index.php                        # Orquestador delgado
├── .env                             # Credenciales (NO trackeado)
├── .env.example                     # Template de credenciales
├── env-loader.php                   # Carga .env en $_ENV
├── auth-check.php                   # Prepend de auth para proyectos
├── .htaccess                        # Reglas Apache
├── assets/                          # Bootstrap 5
│   ├── css/
│   └── js/
├── dashboard-logic/
│   ├── bootstrap.php                # Session, constantes
│   ├── helpers.php                  # Crypto, get_os, type_badge
│   ├── rate-limiter.php             # Control de intentos
│   ├── auth.php                     # Login, logout, cookies
│   ├── projects.php                 # Listado de proyectos
│   ├── wp-auto-login.php            # Auto-login WordPress
│   └── views/
│       ├── dashboard.php            # Vista autenticada
│       ├── login.php                # Formulario de login
│       └── components/
│           ├── server-info.php      # Info del servidor + card Acciones
│           └── project-card.php     # Tarjeta de proyecto
├── README.md
├── CHANGELOG.md
└── .gitignore
```

## Flujo de autenticación

```
Request a /twilight/wp-admin/
  → .htaccess (AllowOverride)
  → auth-check.php (auto_prepend_file)
    ├─ ¿Es index.php, wp-auto-login o assets? → permite
    ├─ ¿Cookie project_user válida? → permite, carga WordPress
    └─ No → redirige a /index.php?redirect=...
```

El dashboard usa AES-256-CBC para encriptar la cookie de sesión. El rate limiter cuenta intentos fallidos en sesión PHP y bloquea por 15 minutos después de 5 intentos.

## Auto-login WordPress

Cada proyecto WordPress detectado (tiene `wp-config.php`) muestra un botón **"Acceder"** que:
1. Busca el primer usuario administrador en la base de datos
2. Genera cookie de sesión de WordPress (`wp_set_auth_cookie`)
3. Redirige al sitio sin pasar por `wp-login.php`

## Seguridad

- El `.htaccess` bloquea acceso directo a `.env` y `dashboard-logic/`
- Las credenciales viajan en cookie encriptada con AES-256-CBC
- Rate limiting en login (5 intentos, 15 min de bloqueo)
- Las redirecciones post-login validan que sean internas (empiecen con `/`)
- La función `desencriptar` usa `base64_decode` estricto para detectar cookies inválidas

## Licencia

Uso interno — entorno de desarrollo.
