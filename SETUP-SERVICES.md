# SETUP-SERVICES — Guía de instalación para IA

Guía paso a paso para que una IA (o un desarrollador) pueda instalar phpMyAdmin y PostgreSQL+pgAdmin4 en el Dev Dashboard.

## Requisitos previos

El dashboard base ya instalado (`setup.sh` ejecutado). Apache, PHP y MySQL funcionando.

## Opción A — Scripts individuales

### phpMyAdmin

```bash
sudo bash setup-phpmyadmin.sh
```

- Descarga phpMyAdmin 5.2.3 y lo instala en `phpmyadmin/`
- Configura `config.inc.php` con blowfish secret aleatorio
- Instala `unzip` y `curl` si faltan
- Idempotente: si ya existe, no reinstala

### PostgreSQL + pgAdmin4

```bash
sudo bash setup-postgres.sh
```

- Instala PostgreSQL 16 desde repo oficial
- Crea usuario `pgadmin` (superuser, pass: `admin`)
- Configura autenticación md5 para conexiones locales
- Instala pgAdmin4 en `/opt/pgadmin4/` con virtualenv
- Configura Apache con mod_wsgi + optimizaciones WSGI
- Acceso: `http://localhost/pgadmin4/` (admin@localhost.com / admin)

## Opción B — Script maestro

```bash
sudo bash setup-services-all.sh
```

Ejecuta ambos scripts en secuencia. Si alguno ya está instalado, lo saltea.

## Variables de entorno necesarias (`.env`)

Después de instalar, asegurate que `.env` tenga estas variables para que el dashboard muestre el estado correctamente:

```env
# PostgreSQL — credenciales que se muestran en el dashboard
DB_USER   = 'pgadmin'
DB_PASS   = 'admin'

# pgAdmin4 — credenciales que se muestran en el dashboard
PGA_EMAIL = 'admin@localhost.com'
PGA_PASS  = 'admin'

# phpMyAdmin — si está instalado manualmente en otra URL
# PMA_URL = 'http://localhost/phpmyadmin/'
```

## Verificación

Después de instalar, el dashboard debe mostrar:

| Sección | Indicador |
|---|---|
| **Estado de servicios** | PostgreSQL ✅, MySQL ✅, pgAdmin4 ✅, phpMyAdmin ✅ |
| **Acceso rápido** | Botones phpMyAdmin, pgAdmin4 activos |
| **Claves de acceso** | Credenciales de PostgreSQL y pgAdmin4 visibles |

Si algún servicio aparece en gris, revisá los logs:

```bash
# PostgreSQL
sudo systemctl status postgresql
pg_isready

# pgAdmin4
sudo systemctl status apache2
tail -f /var/log/pgadmin/pgadmin4.log

# phpMyAdmin
ls -la phpmyadmin/config.inc.php
```

## Seguridad

En staging/producción, cambiá las contraseñas por defecto:

```bash
# PostgreSQL
sudo -u postgres psql -c "ALTER ROLE pgadmin WITH PASSWORD 'nueva-contraseña-fuerte';"

# pgAdmin4 — desde la interfaz web en /pgadmin4/
# phpMyAdmin — las credenciales son las de MySQL, cambiar en .env
```

Y actualizá `.env` con los nuevos valores.
