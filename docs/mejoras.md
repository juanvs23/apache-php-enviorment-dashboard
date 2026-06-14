# Posibles mejoras

## Dashboard

- [x] **Modo oscuro** — toggle claro/oscuro persistente en cookie o localStorage
- [x] **Gestión de usuarios** — CRUD de usuarios con niveles (admin/cliente) y asignación de proyectos por usuario
- [x] **Filtro de proyectos por usuario** — usuarios cliente solo ven proyectos asignados
- [x] **Control de acceso por proyecto** — flag `acept_login` habilita/deshabilita botones de acceso y WP Admin
- [x] **Rediseño del dashboard** — dark theme, cards en grid responsive, tabs en lugar de accordions
- [x] **Gestión de proyectos** — crear, editar y eliminar proyectos con asignación de usuarios. Auto-save a MySQL desde la UI.
- [x] **UX crear proyectos** — validación client-side onblur, auto-slug nombre→directorio, loading spinner, detección de DB existente (async)
- [x] **phpMyAdmin** — script `setup-phpmyadmin.sh` para instalar desde zip. Detección de instalaciones en subdirectorios.
- [❌] **Redis / Memcached** — No implementar.
- [x] **Logs en vivo** — tab 📜 Logs (solo server.view). Apache error log, últimas 100 líneas, polling 5s.
- [x] **Estadísticas de proyectos** — tab 📊 con cards por tipo, barras de progreso y gráfico stacked
- [x] **Búsqueda y filtros** — barra 🔍 en grilla de proyectos. Filtra por nombre o tipo client-side en tiempo real.
- [x] **Exportar credenciales** — botón 📋 Copiar credenciales en cada card. Copia URL + usuario + contraseña al portapapeles.
- [x] **Notificaciones de servicios** — polling cada 30s. Badges en Server Info se actualizan en vivo. Toast si un servicio cae o vuelve.
- [x] **Editor de .env** — `?edit_env=1` (solo admin). Editor monoespaciado con backup automático.
- [x] **Multi-usuario en proyectos** — JSON en `user_own` con `is_logeable` por usuario. Select2 con búsqueda AJAX. Toggle login.
- [⏳] **Multi-idioma** — Delegado.

## Infraestructura

- [🔗] **SSL/TLS** — documentado en README (Hardening para VPS). Let's Encrypt recomendado.
- [❌] **Dockerización** — NO realizar. El dashboard depende de mod_php + .htaccess + exec() a herramientas del host (git, composer, wp-cli, npm). Dockerizar requeriría bind-mounts y un contenedor de 2-3 GB sin ganancia real de portabilidad. `setup.sh` ya cubre 6 distros.
- [⚪] **Monitoreo** — fuera del scope del proyecto (responsabilidad del operador del VPS)
- [⚪] **Backup automático** — fuera del scope (infraestructura del servidor)
- [🔗] **Fail2ban** — documentado en README. Los intentos fallidos se registran en `auth_logs`.
- [⚪] **Health check endpoint** — fuera del scope (responsabilidad del operador)

## pgAdmin4

- [x] **Afinar WSGI** — `pgadmin4-optimize.conf`: 4 procesos × 15 threads.
- [x] **Proxy reverso** — incluido en `pgadmin4-optimize.conf`: ProxyPass a 127.0.0.1:5050.
- [❌] **Autologin** — NO realizar. Requiere modificar el core Python/Flask de pgAdmin4. Frágil (se rompe al actualizar pgAdmin).

## Código

- [x] **Tests iniciales** — estructura de tests con PHPUnit en `dashboard-logic/Tests/`
- [x] **Cobertura de tests** — 212 tests, 506 assertions. Domain, Application, Infrastructure, Presentation, Integration. ProjectCreator cubierto.
- [x] **Refactor server-info.php** — lógica de detección de servicios extraída a `ServiceDetector`. Compartido entre vista y endpoint `/service_status`.
- [x] **Linting** — `phpcs.xml` con PSR12 configurado. `vendor/bin/phpcs` para ejecutar.
- [x] **Docblocks** — PHPDoc presente en `Connection`, `Migration`, `Seed` y clases principales.
- [x] **Type hints** — todos los métodos en Domain, Application, Infrastructure tienen return types.

## Base de datos

- [x] **Migraciones versionadas** — tabla `migrations` con control de versión. `Migration::apply()` aplica secuencialmente las no ejecutadas.
- [x] **Índices** — composite index `idx_email_created` en `auth_logs`. Resto de tablas con índices adecuados.
- [x] **Seed data versionado** — `SEED_ENV` en .env: `dev` (default, contraseñas conocidas), `staging` (aleatorias fuertes), `prod` (solo admin).
- [❌] **Soft delete** — No implementar.
