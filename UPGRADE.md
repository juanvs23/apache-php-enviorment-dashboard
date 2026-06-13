# Guía de actualización — v1.1.0 → v1.5.0

Actualización del Dev Dashboard desde la versión 1.1.0 a la actual (1.5.0).

## 1. Backup

```bash
cd /mnt/vol/projects/apache

# Backup de la base de datos
mysqldump -u root -p apache-dashboard > backup-$(date +%Y%m%d-%H%M%S).sql

# Backup del .env
cp .env .env.backup-$(date +%Y%m%d-%H%M%S)

# Backup de todo el proyecto por si toca rollback
tar czf ../dashboard-backup.tar.gz .
```

## 2. Actualizar código

```bash
cd /mnt/vol/projects/apache
git pull origin dev
```

## 3. Actualizar base de datos

La v1.5.0 agrega las tablas `permissions`, `level_permissions` y `auth_logs`. Si venís de v1.1.0, también necesitás la tabla `migrations`.

```bash
# Aplicar migración 002 (auth_logs)
mysql -u root -p apache-dashboard < migrations/002-auth-logs.sql

# O dejar que el auto-migrate lo haga al recargar el dashboard
```

Al recargar el dashboard por primera vez, `Connection::get()` ejecuta `Migration::apply()` que crea las tablas faltantes automáticamente.

## 4. Actualizar `.env`

Agregá estas variables al `.env`:

```env
# Modo desarrollo
DEV_MODE = '1'

# Entorno de seed (dev | staging | prod)
# SEED_ENV = 'dev'

# Logs en vivo (opcional — el dashboard la detecta automáticamente)
# APACHE_ERROR_LOG = '/var/log/apache2/error.log'
```

## 5. Archivos eliminados

Estos archivos ya no existen. Si tenés modificaciones locales en ellos, migralas antes de hacer pull:

```
dashboard-logic/auth.php
dashboard-logic/user-management.php
dashboard-logic/level-management.php
dashboard-logic/rate-limiter.php
dashboard-logic/projects.php
dashboard-logic/profile.php
dashboard-logic/helpers.php
```

## 6. Reseed (opcional)

Si querés aprovechar los nuevos niveles y permisos RBAC:

```bash
php -r "require 'dashboard-logic/bootstrap.php'; \Dashboard\Database\Seed::run(\Dashboard\Database\Connection::get());"
```

Esto agrega los niveles `operator` y `revisor`, los 8 permisos, y los usuarios de prueba. Si el admin ya existe, no lo sobreescribe.

## 7. Verificar

1. Recargá `http://localhost/`
2. Verificá que puedas hacer login con tus credenciales
3. Andá a **Usuarios → Niveles y Permisos** — deberías ver 4 niveles con checkboxes de permisos
4. Andá a **Usuarios → Registro de accesos** — deberías ver la tabla de logs (vacía al principio)
5. Si ves `📊 Estadísticas` y `📜 Logs` en las pestañas del dashboard, la actualización fue exitosa

## Rollback

Si algo falla:

```bash
cd /mnt/vol/projects/apache
mysql -u root -p apache-dashboard < backup-YYYYMMDD.sql
cp .env.backup-YYYYMMDD .env
git checkout v1.1.0  # o el tag que corresponda
```
