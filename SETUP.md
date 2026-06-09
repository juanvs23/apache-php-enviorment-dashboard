# Guía de instalación manual para IA

Este documento describe paso a paso cómo instalar el Dev Dashboard en sistemas
donde `setup.sh` no funciona: **macOS, Windows, o distribuciones Linux de nicho**.

Una IA leyendo este archivo debe ser capaz de guiar a cualquier usuario.

---

## 1. Componentes necesarios

| Componente | Versión mínima | Para qué |
|-----------|:---:|----------|
| Apache HTTP Server | 2.4+ | Servidor web (requiere **mod_php**, no PHP-FPM) |
| PHP | 8.0+ | Backend del dashboard |
| MySQL / MariaDB | 8.0+ / 10.5+ | Base de datos de usuarios y proyectos |
| Extensiones PHP | — | Ver tabla abajo |

**Extensiones PHP requeridas:**

```
pdo_mysql    → Conexión a MySQL
openssl      → random_bytes() para UUIDs
mbstring     → Manejo de strings multibyte
session      → Rate limiter
json         → Codificación de respuestas
gd           → WordPress (imágenes)
zip          → WordPress/Laravel (plugins, composer)
intl         → Internacionalización
curl         → WordPress/Laravel
xml          → WordPress/Laravel
bcmath       → Laravel
fileinfo     → Laravel (uploads)
tokenizer    → Laravel
```

---

## 2. Instalación por sistema operativo

### macOS (Homebrew)

```bash
# Instalar Apache, PHP y MySQL
brew install httpd php mysql

# Iniciar servicios
brew services start httpd
brew services start php
brew services start mysql

# DocumentRoot por defecto en Homebrew:
# Intel: /usr/local/var/www
# Apple Silicon: /opt/homebrew/var/www
```

### Windows

**Opción A — XAMPP (recomendado para desarrollo local):**

1. Descargar XAMPP desde https://www.apachefriends.org/
2. Instalar con Apache, MySQL y PHP
3. El DocumentRoot será `C:\xampp\htdocs\`
4. `mod_php` ya viene activado

**Opción B — WSL2 con Ubuntu:**

1. Activar WSL2 y instalar Ubuntu desde la Microsoft Store
2. Seguir las instrucciones de Ubuntu más abajo

### Linux de nicho (Void, Gentoo, Slackware, NixOS, etc.)

Instalar manualmente:
- Apache con mod_php
- MySQL o MariaDB
- PHP 8.0+ con TODAS las extensiones de la tabla de arriba

Los nombres de paquete varían por distro. Ejemplos:

```
Void:     xbps-install apache php php-mysql mysql
Gentoo:   emerge apache php mysql
NixOS:    agregar services.httpd, php, mysql a configuration.nix
```

---

## 3. Configuración manual (aplica a todos los SO)

### 3.1 Clonar el proyecto en el DocumentRoot

```bash
# Averiguar el DocumentRoot de Apache
grep -r "DocumentRoot" /etc/apache2/ /etc/httpd/ /usr/local/etc/httpd/ 2>/dev/null

# Clonar el proyecto
cd /ruta/al/document-root
git clone https://github.com/juanvs23/apache-php-enviorment-dashboard.git .
```

### 3.2 Crear base de datos MySQL

```sql
CREATE DATABASE IF NOT EXISTS `apache-dashboard`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### 3.3 Configurar `.env`

```bash
cp .env.example .env
```

Editar `.env` con las credenciales reales de MySQL:

```
DB_DRIVER = 'mysql'
DB_HOST   = 'localhost'
DB_PORT   = '3306'
DB_NAME   = 'apache-dashboard'
DB_USER   = 'tu-usuario'
DB_PASS   = 'tu-contraseña'
```

### 3.4 Actualizar la ruta en `.htaccess`

El archivo `.htaccess` contiene una línea con una ruta absoluta que debe apuntar
al archivo `auth-check.php` dentro del proyecto.

**Averiguar la ruta absoluta del proyecto:**

```bash
pwd   # Mostrar la ruta actual
```

**Editar `.htaccess`** y cambiar:

```apache
php_value auto_prepend_file /ruta/absoluta/del/proyecto/dashboard-logic/auth-check.php
```

> ⚠️ Si estás en **Windows con XAMPP**, la ruta es tipo `C:/xampp/htdocs/dashboard-logic/auth-check.php`.
> En **macOS con Homebrew**, típicamente `/usr/local/var/www/dashboard-logic/auth-check.php`.

### 3.5 Configurar el VirtualHost de Apache

Agregar al archivo de configuración de Apache:

```apache
<VirtualHost *:80>
    DocumentRoot /ruta/absoluta/del/proyecto

    <Directory /ruta/absoluta/del/proyecto>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Ubicación del archivo según SO:**

| SO | Archivo |
|----|---------|
| Ubuntu/Debian | `/etc/apache2/sites-available/000-default.conf` |
| RHEL/Fedora | `/etc/httpd/conf.d/000-default.conf` |
| macOS (Homebrew) | `/usr/local/etc/httpd/httpd.conf` |
| Windows (XAMPP) | `C:\xampp\apache\conf\extra\httpd-vhosts.conf` |

Después de editar, reiniciar Apache:

```bash
# Linux
sudo systemctl restart apache2   # o httpd

# macOS
brew services restart httpd

# Windows (XAMPP)
# Usar el panel de control de XAMPP → Stop / Start Apache
```

### 3.6 Activar mod_rewrite

```bash
# Ubuntu/Debian
sudo a2enmod rewrite

# RHEL/Fedora — ya viene activado
# macOS (Homebrew) — editar httpd.conf, descomentar:
#   LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so

# Windows (XAMPP) — editar C:\xampp\apache\conf\httpd.conf, descomentar:
#   LoadModule rewrite_module modules/mod_rewrite.so
```

### 3.7 Ejecutar el seed

```bash
php seed.php
```

Esto crea las tablas (auto-migrate) y el usuario por defecto:
- **Email:** admin@admin
- **Contraseña:** Admin123

> ⚠️ **Cambiá esta contraseña después del primer login.**

### 3.8 Acceder al dashboard

```
http://localhost
```

---

## 4. Troubleshooting por SO

### macOS

| Problema | Solución |
|----------|----------|
| Apache no carga `.htaccess` | Editar `/usr/local/etc/httpd/httpd.conf` → `AllowOverride All` en el `<Directory>` del DocumentRoot |
| `php_value` no funciona | Asegurate de usar **mod_php** (`brew services start php`), no PHP-FPM |
| Puerto 80 requiere root | Ejecutar `sudo brew services start httpd` o usar puerto 8080 |

### Windows (XAMPP)

| Problema | Solución |
|----------|----------|
| XAMPP no encuentra el proyecto | Mover el proyecto a `C:\xampp\htdocs\` |
| `auto_prepend_file` con espacios en ruta | Usar forward slashes: `C:/xampp/htdocs/mi-proyecto/...` |
| Puerto 80 ocupado (Skype, IIS) | Cambiar puerto de Apache en XAMPP Control Panel → Config |

### Linux de nicho

| Problema | Solución |
|----------|----------|
| Paquete PHP no incluye mod_php | Buscar `libapache2-mod-php`, `php-apache`, o `apache2-mod_php` según distro |
| PHP 8.0 no disponible | Compilar desde source (último recurso) |
| Extensiones con nombres distintos | `php-pdo_mysql` vs `php-mysqlnd` vs `php-mysql` — varía por distro |

---

## 5. Verificación final

Para confirmar que todo está bien, verificá:

```bash
# 1. PHP funciona con Apache
curl -s http://localhost/index.php | head -5

# 2. MySQL conecta
php -r "require 'dashboard-logic/bootstrap.php'; use Dashboard\Database\Connection; Connection::get(); echo 'OK';"

# 3. Las tablas se crearon
mysql -u root -e "SHOW TABLES FROM \`apache-dashboard\`;"

# 4. El seed funciona
php seed.php
```

Si los 4 comandos devuelven resultados sin errores, el dashboard está listo.
