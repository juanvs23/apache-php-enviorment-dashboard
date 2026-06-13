# Staging Checklist

Checklist de seguridad y configuración antes de subir el dashboard a un VPS.

## 🔴 Crítico — responsabilidad del desarrollador (ver README)

Estas medidas son externas al proyecto. El README tiene la guía completa en la sección "Hardening para VPS / staging".

- [🔗] **HTTPS** con Let's Encrypt → [README](README.md#hardening-para-vps--staging)
- [🔗] **Firewall** — solo puertos 22, 80, 443 → [README](README.md#hardening-para-vps--staging)
- [🔗] **Restringir pgAdmin4** → [README](README.md#hardening-para-vps--staging)
- [🔗] **Cambiar credenciales del `.env`** → [README](README.md#hardening-para-vps--staging)

## 🟡 Implementado en el proyecto

- [x] **phpinfo() protegido** — requiere `DEV_MODE=1` en `.env` + autenticación de admin
- [x] **Logging de accesos** — tabla `auth_logs`, visible en `?users=1&tab=logs`. Registra login exitoso, fallido y logout con IP y timestamp.

## 🟡 Recomendaciones (ver README)

- [🔗] **Rate limiting IP-based** (Fail2ban) → [README](README.md#hardening-para-vps--staging)
- [🔗] **Usuarios de BD dedicados** → [README](README.md#hardening-para-vps--staging)
- [🔗] **Cerrar puertos de BD** → [README](README.md#hardening-para-vps--staging)

## ⚪ Aceptado como tradeoff

- [~] **Permisos estrictos** — el proyecto usa `chmod 0777` en staging colaborativo. En producción aplicar `chown root:www-data` + `chmod 755`.

## ⚪ Fuera del scope del proyecto

- **Backup automático** — infraestructura del servidor, no del dashboard
- **Monitoreo / health check** — responsabilidad del operador del VPS
- **SSL en PostgreSQL** — no aplica (DB en localhost)
