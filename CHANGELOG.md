# Changelog

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
