# Staging Checklist

Checklist de seguridad y configuración antes de subir el dashboard a un VPS.

## 🔴 Crítico — hacer antes de subir

- [ ] **HTTPS** con Let's Encrypt
  ```bash
  apt install certbot python3-certbot-apache
  certbot --apache -d tudominio.com
  ```
- [ ] **Firewall** — solo puertos 22, 80, 443
  ```bash
  ufw default deny incoming
  ufw allow ssh
  ufw allow https
  ufw allow http
  ufw enable
  ```
- [ ] **Restringir pgAdmin4** — por IP, VPN o autenticación básica de Apache
- [ ] **Cambiar credenciales del `.env`** — nada de valores dummy

## 🟡 Muy recomendado

- [ ] **Deshabilitar phpinfo()** — condicionar a `DEV_MODE` o borrar el link
- [ ] **Rate limiting IP-based** — Fail2ban contra `/index.php` o migrar a APCu
- [ ] **Usuarios de base de datos dedicados** — no usar superuser para todo
- [ ] **Permisos estrictos** — `chown root:www-data`, `chmod 755` en archivos estáticos
- [ ] **Logging de accesos** — agregar log en `auth.php` con IP y timestamp

## 🟢 Buenas prácticas

- [ ] **Backup automático diario** — `pg_dumpall` + `mysqldump` comprimidos
- [ ] **Monitoreo mínimo** — health check vía cron que alerte si un servicio cae
- [ ] **SSL en PostgreSQL** — si la DB está en un host separado
- [ ] **Cerrar puertos de base de datos** — 5432, 3306 solo desde localhost
