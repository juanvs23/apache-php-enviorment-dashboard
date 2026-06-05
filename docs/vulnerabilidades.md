# Vulnerabilidades conocidas

> ⚠️ Este dashboard está diseñado para **entornos de desarrollo local**, no para producción.
> Las siguientes vulnerabilidades son aceptadas por diseño, pero deben tenerse en cuenta.

## Críticas

### 1. Credenciales en texto plano en `.env`

El archivo `.env` contiene contraseñas en texto plano (dashboard, bases de datos, pgAdmin). Si alguien accede al servidor por cualquier medio, tiene todas las credenciales.

**Mitigación**: el `.gitignore` excluye `.env` del repositorio. El `.htaccess` bloquea el acceso HTTP directo. La seguridad recae en el acceso al sistema de archivos.

### 2. Auto-prepend para todos los subdirectorios

El `.htaccess` del proyecto aplica `php_value auto_prepend_file` a TODOS los subdirectorios del DocumentRoot. Si hay un proyecto que no debería tener esta protección (o que maneja su propio prepend), puede haber conflictos.

**Mitigación**: limitar el alcance del `auto_prepend_file` solo a proyectos conocidos, o usar `If` / `IfFile` en el `.htaccess`.

### 3. pgAdmin4 expuesto sin autenticación adicional

pgAdmin4 está servido en `/pgadmin4/` bajo el mismo host que el dashboard. Aunque pgAdmin4 tiene su propio login, comparte el mismo nivel de exposición.

**Mitigación**: agregar autenticación básica de Apache (`.htpasswd`) en el `Location /pgadmin4` o restringir por IP.

## Medias

### 4. Sin HTTPS

El dashboard y pgAdmin4 se sirven por HTTP. Las credenciales viajan en cookies sin cifrar en tránsito.

**Mitigación**: instalar Let's Encrypt o generar un certificado autofirmado y forzar HTTPS.

### 5. Sesión del dashboard en cookie sin Secure flag

La cookie de sesión del dashboard no tiene la flag `Secure` (porque no hay HTTPS). Tampoco tiene `SameSite`.

**Mitigación**: al implementar HTTPS, agregar `Secure` y `SameSite=Strict` a la cookie de sesión.

### 6. El rate limiter usa sesión PHP (por cliente)

El rate limiting cuenta intentos en `$_SESSION`, que se identifica por cookie de sesión PHP. Un atacante que resetee cookies puede reintentar ilimitadamente.

**Mitigación**: migrar a rate limiting por IP usando `ip2long` + archivo o tabla en memoria (APCu / Redis).

### 7. phpinfo() expuesto

El dashboard tiene un link a `?phpinfo=1` que muestra información completa del servidor (versiones, rutas, variables de entorno). Es información valiosa para un atacante.

**Mitigación**: deshabilitar el link en entornos que no sean estrictamente desarrollo local.

## Bajas

### 8. WordPress auto-login usa el primer admin

El auto-login de WordPress busca `ID = 1` o el primer administrador en la base de datos. Si el sitio tiene múltiples admins, siempre loguea al primero.

**Riesgo**: bajo — en desarrollo no suele ser un problema.

### 9. Bloqueo por `.htaccess` confía en `AllowOverride`

Si Apache no tiene `AllowOverride All` para el DocumentRoot, el `.htaccess` no se aplica y los archivos del dashboard quedan expuestos.

**Mitigación**: documentado en el README como requisito de instalación.

### 10. Sin logging de accesos al dashboard

No hay un registro de quién inició sesión, cuándo, o desde qué IP. En caso de incidente no hay auditoría.

**Mitigación**: agregar log a syslog o archivo en `dashboard-logic/auth.php` con timestamp, IP, y resultado del intento.
