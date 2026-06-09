# Posibles mejoras

## Dashboard

- [x] **Modo oscuro** — toggle claro/oscuro persistente en cookie o localStorage
- [x] **Gestión de usuarios** — CRUD de usuarios con niveles (admin/cliente) y asignación de proyectos por usuario
- [x] **Filtro de proyectos por usuario** — usuarios cliente solo ven proyectos asignados
- [x] **Control de acceso por proyecto** — flag `acept_login` habilita/deshabilita botones de acceso y WP Admin
- [x] **Rediseño del dashboard** — dark theme, cards en grid responsive, tabs en lugar de accordions
- [x] **Gestión de proyectos** — crear, editar y eliminar proyectos con asignación de usuarios
- [ ] **phpMyAdmin** — instalar y configurar desde el zip descargado, o agregar detección de instalaciones manuales en subdirectorios
- [ ] **Redis / Memcached** — detectar si están instalados y mostrar estado
- [ ] **Logs en vivo** — cola de logs de Apache accesible desde el dashboard (tail + WebSocket o polling)
- [ ] **Estadísticas de proyectos** — cantidad de proyectos por tipo, gráfico simple
- [ ] **Búsqueda y filtros** — filtrar proyectos por nombre, tipo, o estado en el dashboard
- [ ] **Exportar credenciales** — botón para copiar todas las claves de acceso de un proyecto
- [ ] **Notificaciones** — si un servicio está caído (PostgreSQL, MySQL, Apache), mostrar alerta visual
- [ ] **Editor de .env desde el dashboard** — con confirmación y backup automático
- [ ] **Multi-idioma** — soporte para español/inglés vía archivos de traducción

## Infraestructura

- [ ] **SSL/TLS** — generar certificado autofirmado o con Let's Encrypt para servir el dashboard por HTTPS
- [ ] **Dockerización** — docker-compose con Apache, PHP, PostgreSQL y MySQL
- [ ] **Monitoreo** — integración con Prometheus/node_exporter o dashboard de recursos en tiempo real
- [ ] **Backup automático** — script para dump de bases de datos (PostgreSQL + MySQL) con rotation
- [ ] **Fail2ban** — bloquear IPs después de N intentos fallidos de login en el dashboard
- [ ] **Health check endpoint** — `/health` que devuelva JSON con estado de todos los servicios

## pgAdmin4

- [ ] **Afinar WSGI** — aumentar procesos/threads en `WSGIDaemonProcess` para entornos con más carga
- [ ] **Autologin** — pasar token de sesión del dashboard a pgAdmin4 para evitar doble login
- [ ] **Proxy reverso** — servir pgAdmin4 bajo el mismo dominio que el dashboard

## Código

- [x] **Tests iniciales** — estructura de tests con PHPUnit en `dashboard-logic/Tests/`
- [ ] **Cobertura de tests** — tests para auth, rate-limiter, helpers, user-management
- [ ] **Type hints** — agregar tipos a todas las funciones
- [ ] **Linting** — configurar PHP_CodeSniffer o Psalm
- [ ] **Refactor server-info.php** — extraer la lógica de detección de servicios a una clase separada en lugar de inline en la vista
- [ ] **Docblocks** — agregar PHPDoc a las clases de `Dashboard\Database\`

## Base de datos

- [ ] **Migraciones versionadas** — sistema de migraciones con control de versión (no solo auto-migrate)
- [ ] **Seed data versionado** — separar seeds por entorno (dev/staging/prod)
- [ ] **Índices** — revisar y agregar índices faltantes en tablas USERS y Project
- [ ] **Soft delete** — implementar borrado lógico en usuarios y proyectos
