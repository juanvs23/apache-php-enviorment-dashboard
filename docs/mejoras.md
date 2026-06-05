# Posibles mejoras

## Dashboard

- [ ] **Modo oscuro** — toggle claro/oscuro persistente en cookie o localStorage
- [ ] **phpMyAdmin** — instalar y configurar desde el zip descargado, o agregar detección de instalaciones manuales en subdirectorios
- [ ] **Redis / Memcached** — detectar si están instalados y mostrar estado
- [ ] **Logs en vivo** — cola de logs de Apache accesible desde el dashboard (tail + WebSocket o polling)
- [ ] **Estadísticas de proyectos** — cantidad de proyectos por tipo, gráfico simple
- [ ] **Búsqueda y filtros** — filtrar proyectos por nombre, tipo, o estado
- [ ] **Exportar credenciales** — botón para copiar todas las claves de acceso de un proyecto
- [ ] **Notificaciones** — si un servicio está caído (PostgreSQL, MySQL, Apache), mostrar alerta visual
- [ ] **Editor de .env desde el dashboard** — con confirmación y backup automático
- [ ] **Multi-idioma** — soporte para español/inglés vía archivos de traducción

## Infraestructura

- [ ] **SSL/TLS** — generar certificado autofirmado o con Let's Encrypt para servir el dashboard por HTTPS
- [ ] **Dockerización** — docker-compose con Apache, PHP, PostgreSQL, MySQL y pgAdmin4
- [ ] **Monitoreo** — integración con Prometheus/node_exporter o dashboard de recursos en tiempo real
- [ ] **Backup automático** — script para dump de bases de datos (PostgreSQL + MySQL) con rotation
- [ ] **Fail2ban** — bloquear IPs después de N intentos fallidos de login en el dashboard
- [ ] **Health check endpoint** — `/health` que devuelva JSON con estado de todos los servicios

## pgAdmin4

- [ ] **Afinar WSGI** — aumentar procesos/threads en `WSGIDaemonProcess` para entornos con más carga
- [ ] **Autologin** — pasar token de sesión del dashboard a pgAdmin4 para evitar doble login
- [ ] **Proxy reverso** — servir pgAdmin4 bajo el mismo dominio que el dashboard

## Código

- [ ] **Tests** — agregar PHPUnit para la lógica del dashboard (auth, rate-limiter, helpers)
- [ ] **Type hints** — agregar tipos a todas las funciones de `helpers.php` y `dashboard-logic/`
- [ ] **Linting** — configurar PHP_CodeSniffer o Psalm
- [ ] **Refactor server-info.php** — extraer la lógica de detección de servicios a una clase separada en lugar de inline en la vista
