# Vulnerabilidades conocidas

> ⚠️ Este dashboard está diseñado para **entornos de desarrollo y staging**, no para producción.
> Las siguientes vulnerabilidades son aceptadas por diseño o mitigadas según corresponda.

## ✅ Mitigadas en el proyecto

### 1. phpinfo() expuesto (v1.5.0)

El dashboard tenía un link a `?phpinfo=1` sin protección.

**Mitigación aplicada**: requiere `DEV_MODE=1` en `.env` + autenticación de admin. Sin ambas condiciones, muestra un mensaje de acceso denegado. En staging se recomienda `DEV_MODE=0`.

### 2. Sin logging de accesos (v1.5.0)

No había registro de quién inició sesión, cuándo, o desde qué IP.

**Mitigación aplicada**: tabla `auth_logs` con email, acción (`login_success`, `login_failed`, `logout`), IP, user-agent y timestamp. Vista en el dashboard: `?users=1&tab=logs`.

---

## 🔗 Mitigadas vía hardening del servidor

Estas vulnerabilidades se resuelven con medidas externas al proyecto. Ver [README](README.md#hardening-para-vps--staging).

| # | Vulnerabilidad | Solución (ver README) |
|---|---|---|
| 3 | `.env` en texto plano | `.htaccess` bloquea acceso HTTP. `.gitignore` excluye de git. |
| 4 | pgAdmin4 expuesto | Restringir por IP o `htpasswd` en el VirtualHost. |
| 5 | Sin HTTPS | Let's Encrypt (`certbot --apache`). |
| 6 | Cookie sin `Secure` / `SameSite` | Se corrige automáticamente al implementar HTTPS. |
| 7 | Rate limiter por sesión (bypasseable) | Complementar con Fail2ban a nivel servidor. |

---

## ⚪ Aceptadas para desarrollo/staging

| # | Vulnerabilidad | Justificación |
|---|---|---|
| 8 | `auto_prepend_file` en todos los subdirectorios | Es el diseño del dashboard para proteger proyectos. Si un proyecto requiere su propio prepend, se excluye en `.htaccess`. |
| 9 | WordPress auto-login usa el primer admin | En staging típicamente hay 1 admin. Riesgo bajo. |
| 10 | `.htaccess` confía en `AllowOverride All` | Es requisito de instalación documentado en README. Sin esto, el dashboard no funciona. |
