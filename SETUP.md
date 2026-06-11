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

### Componente opcional

| Componente | Versión mínima | Para qué |
|-----------|:---:|----------|
| Git | 2.30+ | Clonar repositorios desde GitHub al crear proyectos |
| Node.js | 22.x / 24.x LTS | Crear proyectos HTML con Vite.js (HMR, bundler moderno) |
| npm | 9.x+ | Gestor de paquetes de Node.js |

> **Nota:** Git solo es necesario si vas a usar **"Clonar desde GitHub"**. Node.js solo para **"Usar Vite.js"**. Para proyectos estáticos sin bundler ni clone, no hacen falta.

**Instalación de Git:**

```bash
# Ubuntu/Debian
sudo apt-get install -y git

# Fedora/RHEL
sudo dnf install -y git

# Arch Linux
sudo pacman -Sy git

# macOS (Homebrew) — ya viene instalado con Xcode CLI Tools
# Windows: https://git-scm.com/downloads
```

**Instalación de Node.js:**

```bash
# Ubuntu/Debian — via NodeSource (LTS)
curl -fsSL https://deb.nodesource.com/setup_24.x | sudo -E bash -
sudo apt-get install -y nodejs

# Fedora/RHEL
sudo dnf module install -y nodejs:24

# Arch Linux
sudo pacman -Sy nodejs npm

# macOS (Homebrew)
brew install node

# Windows
# Descargar de https://nodejs.org/ (versión LTS)
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

**Al iniciar, preguntá al usuario qué método prefiere:**

> "Para Windows tenés 4 opciones. ¿Cuál preferís?
> **A)** XAMPP (más fácil, 1 click)
> **B)** Laragon (moderno, portable, más rápido)
> **C)** WSL2 + Ubuntu (entorno Linux real dentro de Windows)
> **D)** Instalación nativa (Apache + PHP + MySQL por separado, control total)"

#### Opción A — XAMPP

1. Descargar XAMPP desde https://www.apachefriends.org/
2. Instalar con Apache, MySQL y PHP
3. DocumentRoot: `C:\xampp\htdocs\`
4. `mod_php` y `mod_rewrite` ya vienen activados

```powershell
# Clonar el proyecto
cd C:\xampp\htdocs
git clone https://github.com/juanvs23/apache-php-enviorment-dashboard.git dashboard
```

#### Opción B — Laragon

1. Descargar Laragon desde https://laragon.org/download/
2. Instalar (modo Full: Apache + MySQL + PHP)
3. DocumentRoot: `C:\laragon\www\`
4. Panel de control → Menú contextual → `PHP > Extensions` para activar extensiones

```powershell
cd C:\laragon\www
git clone https://github.com/juanvs23/apache-php-enviorment-dashboard.git dashboard
```

Ventajas sobre XAMPP: portable (USB), más rápido, terminal integrada (Cmder), SSL automático.

#### Opción C — WSL2 + Ubuntu

```powershell
# En PowerShell (Admin)
wsl --install -d Ubuntu
```

Después de reiniciar, abrir la terminal de Ubuntu y seguir las instrucciones de Linux (apt).

```bash
cd /mnt/c/Users/TuUsuario/www   # o donde prefieras
git clone https://github.com/juanvs23/apache-php-enviorment-dashboard.git dashboard
cd dashboard
./setup.sh
```

Ventajas: entorno idéntico a producción (Linux real).

#### Opción D — Instalación nativa (Apache + PHP + MySQL por separado)

**Apache (Apache Lounge):**

1. Descargar `httpd-2.4.x-win64-VS17.zip` desde https://www.apachelounge.com/download/
2. Extraer a `C:\Apache24`
3. Editar `C:\Apache24\conf\httpd.conf`:
   - Cambiar `ServerRoot` a `"C:/Apache24"`
   - Cambiar `DocumentRoot` a `"C:/www"`
   - Agregar al final:
     ```apache
     LoadModule php_module "C:/php/php8apache2_4.dll"
     AddHandler application/x-httpd-php .php
     PHPIniDir "C:/php"
     ```
   - Descomentar `LoadModule rewrite_module modules/mod_rewrite.so`
   - Cambiar `AllowOverride None` → `AllowOverride All` en `<Directory "C:/www">`
4. Instalar como servicio: `C:\Apache24\bin\httpd.exe -k install`

**PHP:**

1. Descargar `PHP 8.3.x (Thread Safe)` ZIP desde https://windows.php.net/download/
2. Extraer a `C:\php`
3. Copiar `php.ini-production` → `php.ini`
4. Descomentar extensiones: `extension=php_pdo_mysql.dll`, `extension=php_mbstring.dll`, 
   `extension=php_openssl.dll`, `extension=php_gd.dll`, `extension=php_curl.dll`,
   `extension=php_zip.dll`, `extension=php_intl.dll`, `extension=php_fileinfo.dll`
5. Agregar `C:\php` al PATH del sistema

**MySQL:**

1. Descargar MySQL Installer desde https://dev.mysql.com/downloads/installer/
2. Instalar "Server only"
3. Elegir puerto 3306, autenticación `mysql_native_password`, setear clave root

**Clonar y configurar:**

```powershell
mkdir C:\www
cd C:\www
git clone https://github.com/juanvs23/apache-php-enviorment-dashboard.git .

# Editar .htaccess — cambiar la ruta absoluta:
# php_value auto_prepend_file C:/www/dashboard-logic/auth-check.php
```

Crear directorios de proyecto como subcarpetas de `C:\www` (ej: `C:\www\twilight\`).

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
| Windows (Laragon) | `C:\laragon\bin\apache\httpd-*\conf\extra\httpd-vhosts.conf` |
| Windows (nativo) | `C:\Apache24\conf\httpd.conf` |

Después de editar, reiniciar Apache:

```bash
# Linux
sudo systemctl restart apache2   # o httpd

# macOS
brew services restart httpd

# Windows (XAMPP/Laragon)
# Usar el panel de control → Stop / Start Apache

# Windows (nativo)
# C:\Apache24\bin\httpd.exe -k restart
```

### 3.6 Activar mod_rewrite

```bash
# Ubuntu/Debian
sudo a2enmod rewrite

# RHEL/Fedora — ya viene activado
# macOS (Homebrew) — editar httpd.conf, descomentar:
#   LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so

# Windows
# XAMPP / Laragon: ya viene activado
# Nativo: editar C:\Apache24\conf\httpd.conf, descomentar:
#   LoadModule rewrite_module modules/mod_rewrite.so
```

### 3.7 Ejecutar el seed

```bash
php seed.php
```

Esto crea las tablas (auto-migrate) y el usuario por defecto:
- **Email:** admin@admin.com
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

### Windows

| Método | Problema | Solución |
|--------|----------|----------|
| XAMPP | No encuentra el proyecto | Mover a `C:\xampp\htdocs\` |
| XAMPP | Puerto 80 ocupado (Skype, IIS) | XAMPP Control Panel → Config → cambiar puerto |
| Laragon | Extensión no disponible | Click derecho en Laragon → PHP → Extensions |
| Laragon | MySQL no inicia | Laragon → Menú → MySQL → Change port |
| WSL2 | `setup.sh` no encuentra Apache | Ejecutar `sudo apt update && sudo apt install apache2` primero |
| WSL2 | Proyecto no accesible desde Windows | Usar `localhost`, WSL2 comparte la red |
| Nativo | `php8apache2_4.dll` no encontrado | Verificar que PHP sea Thread Safe y la ruta en httpd.conf use `/` no `\` |
| Nativo | Apache no inicia como servicio | Ejecutar como Admin: `C:\Apache24\bin\httpd.exe -k install` |
| Todos | `auto_prepend_file` con espacios en ruta | Usar forward slashes: `C:/xampp/htdocs/dashboard/...` |

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
