#!/usr/bin/env bash
set -euo pipefail

# ═══════════════════════════════════════════════════════════════════════
# Dev Dashboard — Instalación completa (multi-distro)
# ═══════════════════════════════════════════════════════════════════════

ROOT="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$ROOT/.env"; EXAMPLE="$ROOT/.env.example"
HTACCESS="$ROOT/.htaccess"; SEED="$ROOT/seed.php"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; CYAN='\033[0;36m'; NC='\033[0m'
info()   { echo -e "  ${GREEN}[✓]${NC} $1"; }
warn()   { echo -e "  ${YELLOW}[!]${NC} $1"; }
err()    { echo -e "  ${RED}[✗]${NC} $1"; }
step()   { echo -e "\n${BLUE}─── $1 ───${NC}"; }
explain(){ echo -e "  ${CYAN}→${NC} $1"; }

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║     Dev Dashboard — Instalación completa             ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""
explain "Voy a detectar tu sistema, instalar lo necesario y dejar todo listo."
echo ""

# ─── Detectar distro y package manager ─────────────────────────────────
detect_distro() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        echo "$ID"
    elif [ -f /etc/debian_version ]; then echo "debian"
    elif [ -f /etc/redhat-release ]; then echo "rhel"
    elif [ -f /etc/arch-release ]; then echo "arch"
    else echo "unknown"; fi
}

detect_pkg_manager() {
    if   command -v apt-get &>/dev/null; then echo "apt"
    elif command -v dnf     &>/dev/null; then echo "dnf"
    elif command -v yum     &>/dev/null; then echo "yum"
    elif command -v pacman  &>/dev/null; then echo "pacman"
    elif command -v zypper  &>/dev/null; then echo "zypper"
    else echo "unknown"; fi
}

detect_php_version() {
    php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "0.0"
}

detect_apache_service() {
    if systemctl list-units --type=service 2>/dev/null | grep -q 'apache2.service'; then echo "apache2"
    elif systemctl list-units --type=service 2>/dev/null | grep -q 'httpd.service'; then echo "httpd"
    else echo "apache2"; fi
}

explain "Detectando tu sistema operativo y qué programas tenés instalados..."
DISTRO=$(detect_distro)
PKG=$(detect_pkg_manager)
PHP_VER=$(detect_php_version)
APACHE_SVC=$(detect_apache_service)

echo "  Sistema:      $DISTRO"
echo "  Instalador:   $PKG"
echo "  PHP actual:   $PHP_VER"
[[ "$PHP_VER" != "0.0" ]] && explain "Ya tenés PHP $PHP_VER — solo voy a instalar lo que falte." \
                         || explain "No tenés PHP instalado — voy a instalar la última versión disponible."

# ─── Verificar sudo ────────────────────────────────────────────────────
if ! sudo -n true 2>/dev/null; then
    err "Necesitás sudo para instalar paquetes del sistema."
    err "Ejecutá el script con un usuario con permisos sudo."
    exit 1
fi

# ══════════════════════════════════════════════════════════════════════
# 1. Instalar paquetes del sistema
# ══════════════════════════════════════════════════════════════════════
step "1. Instalando Apache + PHP + MySQL"
explain "Estos tres programas son la base del dashboard:"
explain "  • Apache = servidor web (sirve las páginas)"
explain "  • PHP    = lenguaje que ejecuta el dashboard"
explain "  • MySQL  = base de datos (usuarios, proyectos)"

# Extensiones necesarias para WordPress, Laravel y PHP puro
# Core:        pdo_mysql, openssl, mbstring, session, json (built-in 8.0+)
# WordPress:   gd, imagick, zip, intl, curl, xml
# Laravel:     zip, bcmath, tokenizer, fileinfo, curl, xml, mbstring
# Apache:      libapache2-mod-php / mod_php

PHP_SHORT="${PHP_VER/./}"

case "$PKG" in
    apt)
        if [[ "$PHP_VER" == "0.0" ]]; then
            explain "Buscando la última versión de PHP en los repositorios..."
            PHP_VER=$(apt-cache search '^php[0-9]+\.[0-9]+$' 2>/dev/null | grep -oP 'php\K[0-9]+\.[0-9]+' | sort -V | tail -1)
            PHP_SHORT="${PHP_VER/./}"
            [[ -z "$PHP_VER" ]] && PHP_VER="8.3" && PHP_SHORT="83"
            explain "Versión encontrada: PHP $PHP_VER"
        fi

        sudo apt-get update -qq 2>/dev/null
        explain "Repositorios actualizados."

        # Detectar MariaDB antes de instalar MySQL
        MARIADB_SKIP=false
        if dpkg -l mariadb-server 2>/dev/null | grep -q '^ii'; then
            if ss -tlnp 2>/dev/null | grep -q ':3306'; then
                warn "MariaDB detectado en puerto 3306 — salteando instalación de MySQL"
                explain "Tu MariaDB es 100% compatible con el dashboard."
                MARIADB_SKIP=true
            fi
        elif dpkg -l mysql-server 2>/dev/null | grep -q '^ii'; then
            info "MySQL ya instalado"
            MARIADB_SKIP=true
        fi

        # Instalar Apache
        explain "Instalando Apache..."
        sudo apt-get install -y -qq apache2 2>/dev/null
        info "Apache instalado"

        # Instalar MySQL solo si no hay MariaDB
        if [[ "$MARIADB_SKIP" == "false" ]]; then
            explain "Instalando MySQL..."
            sudo apt-get install -y -qq mysql-server mysql-client 2>/dev/null
            info "MySQL instalado"
        fi

        explain "Instalando PHP $PHP_VER y su módulo para Apache..."
        PHP_CORE="php${PHP_VER} libapache2-mod-php${PHP_VER}"
        sudo apt-get install -y -qq $PHP_CORE 2>/dev/null
        info "PHP $PHP_VER + mod_php instalados"

        # Extensiones
        EXTS="mysql mbstring xml curl gd zip intl bcmath imagick"
        EXT_DESC=(
            "pdo_mysql: conexión a MySQL"
            "mbstring: texto con acentos y emojis"
            "xml: procesar XML (WordPress, Laravel)"
            "curl: llamadas HTTP (APIs, updates)"
            "gd: redimensionar imágenes (WordPress)"
            "zip: comprimir/descomprimir (plugins, Composer)"
            "intl: fechas y monedas en múltiples idiomas"
            "bcmath: cálculos precisos (Laravel)"
            "imagick: imágenes avanzadas (WordPress)"
        )
        explain "Instalando extensiones de PHP..."
        for ext in $EXTS; do
            if apt-cache show "php${PHP_VER}-${ext}" &>/dev/null; then
                sudo apt-get install -y -qq "php${PHP_VER}-${ext}" 2>/dev/null
                info "php${PHP_VER}-${ext}"
            else
                warn "php${PHP_VER}-${ext} no disponible como paquete"
            fi
        done
        info "Extensiones instaladas"
        ;;

    dnf|yum)
        warn "RHEL/Fedora detectado. Asegurate de tener habilitado Remi/EPEL."
        explain "Instalando Apache (httpd), MySQL y PHP con extensiones..."
        if [[ "$PKG" == "dnf" ]]; then
            sudo dnf install -y httpd mysql-server php php-mysqlnd php-gd \
                php-imagick php-zip php-intl php-bcmath php-mbstring \
                php-xml php-curl php-cli php-pdo php-openssl
        else
            sudo yum install -y httpd mysql-server php php-mysqlnd php-gd \
                php-imagick php-zip php-intl php-bcmath php-mbstring \
                php-xml php-curl php-cli php-pdo php-openssl
        fi
        info "Paquetes instalados"
        ;;

    pacman)
        explain "Instalando Apache, MySQL y PHP (Arch-style)..."
        sudo pacman -S --noconfirm apache mysql php php-apache \
            php-gd php-intl php-curl php-sodium
        info "Paquetes instalados"
        ;;

    zypper)
        explain "Instalando Apache, MySQL y PHP (openSUSE-style)..."
        sudo zypper install -y apache2 mysql-server php8 php8-mysql \
            php8-gd php8-zip php8-intl php8-bcmath php8-mbstring \
            php8-xml php8-curl php8-fileinfo apache2-mod_php8
        info "Paquetes instalados"
        ;;

    *)
        err "Package manager '$PKG' no soportado automáticamente."
        err "Instalá manualmente Apache + PHP 8.0+ + MySQL + extensiones:"
        err "  pdo_mysql, gd, imagick, zip, intl, bcmath, mbstring, xml, curl, openssl"
        ;;
esac

# ══════════════════════════════════════════════════════════════════════
# 2. Activar módulos de Apache
# ══════════════════════════════════════════════════════════════════════
step "2. Activando módulos de Apache"
explain "Apache carga funcionalidades por módulos. Necesitamos dos:"
explain "  • mod_rewrite = URLs limpias (ej: /twilight/hola → index.php)"
explain "  • mod_php     = ejecutar código PHP dentro de Apache"

# Verificar que Apache esté instalado
if ! command -v apache2ctl &>/dev/null && ! command -v apachectl &>/dev/null && ! command -v httpd &>/dev/null; then
    err "Apache no está instalado. El paso 1 debería haberlo instalado."
    err "Instalalo manualmente y volvé a correr el script."
    exit 1
fi

case "$PKG" in
    apt)
        PHP_VER=$(detect_php_version)
        PHP_SHORT="${PHP_VER/./}"

        for mod in rewrite "php${PHP_VER}"; do
            if apache2ctl -M 2>/dev/null | grep -qi "${mod}_module"; then
                info "mod_$mod ya activo"
            else
                warn "mod_$mod no activo — activando..."
                sudo a2enmod -q "$mod" 2>/dev/null && info "mod_$mod activado" \
                    || warn "No se pudo activar mod_$mod — hacelo manual: sudo a2enmod $mod"
            fi
        done
        ;;
    dnf|yum)
        explain "En RHEL/Fedora los módulos se activan en /etc/httpd/conf.modules.d/"
        explain "Verificá que mod_rewrite y mod_php estén descomentados."
        ;;
    pacman)
        explain "En Arch, verificá /etc/httpd/conf/httpd.conf:"
        explain "  LoadModule rewrite_module modules/mod_rewrite.so"
        explain "  LoadModule php_module modules/libphp.so"
        ;;
    zypper)
        for mod in rewrite php8; do
            sudo a2enmod -q "$mod" 2>/dev/null && info "mod_$mod activado" \
                || warn "mod_$mod no disponible"
        done
        ;;
esac

# ══════════════════════════════════════════════════════════════════════
# 3. Verificar extensiones PHP
# ══════════════════════════════════════════════════════════════════════
step "3. Verificando extensiones PHP"
explain "Reviso una por una las extensiones que el dashboard necesita."
explain "Si alguna falta, instalala con: sudo apt install phpX.X-nombre"

REQUIRED_EXTS="pdo_mysql openssl mbstring json gd zip intl curl xml bcmath fileinfo tokenizer"

for ext in $REQUIRED_EXTS; do
    if php -m 2>/dev/null | grep -qi "$ext"; then
        info "$ext"
    else
        warn "$ext NO detectada"
    fi
done

# ══════════════════════════════════════════════════════════════════════
# 4. Configurar .env
# ══════════════════════════════════════════════════════════════════════
step "4. Configurando .env"
explain "El archivo .env guarda las credenciales de MySQL de forma segura."
explain "Voy a copiar .env.example → .env y pedirte los datos."

if [ -f "$ENV_FILE" ]; then
    warn ".env ya existe — lo salteo (tus credenciales están a salvo)"
else
    echo ""
    echo "  ┌─────────────────────────────────────────────┐"
    echo "  │  Configurá la conexión a tu base de datos:  │"
    echo "  └─────────────────────────────────────────────┘"
    echo ""
    read -p "  Host MySQL [localhost]: " DB_HOST;    DB_HOST=${DB_HOST:-localhost}
    read -p "  Puerto MySQL [3306]: " DB_PORT;        DB_PORT=${DB_PORT:-3306}
    read -p "  Nombre de la BD [apache-dashboard]: " DB_NAME_IN
    DB_NAME=${DB_NAME_IN:-apache-dashboard}
    read -p "  Usuario MySQL: " DB_USER
    read -sp "  Contraseña MySQL: " DB_PASS;        echo ""

    explain "Escribiendo .env con tus datos..."
    cat > "$ENV_FILE" << ENVEOF
# ─── Dev Dashboard ──────────────────────────────────────────────────────────
# Generado automáticamente por setup.sh

# ─── Base de datos MySQL (obligatorio) ───────────────────────────────────────
DB_DRIVER = 'mysql'
DB_HOST   = '${DB_HOST}'
DB_PORT   = '${DB_PORT}'
DB_NAME   = '${DB_NAME}'
DB_USER   = '${DB_USER}'
DB_PASS   = '${DB_PASS}'

# ─── phpMyAdmin (opcional) ───────────────────────────────────────────────────
# PMA_URL = 'http://localhost/phpmyadmin/'

# ─── Claves de acceso (informativas, se muestran en el dashboard) ────────────
# PGA_EMAIL  = 'admin@localhost.com'
# PGA_PASS   = 'admin'
# MYSQL_USER = 'root'
# MYSQL_PASS = ''
# PMA_USER   = 'root'
# PMA_PASS   = ''
ENVEOF

    info ".env creado correctamente"
fi

# Leer valores del .env para los siguientes pasos
# cut -d"'" -f2 extrae el valor entre comillas simples. No falla con
# /, |, $, ;, &, espacios ni la mayoria de caracteres especiales.
# NOTA: si tu contraseña contiene ' (comilla simple), guardala sin ' o
# editá el .env manualmente después.
DB_NAME=$(grep '^DB_NAME' "$ENV_FILE" | head -1 | cut -d"'" -f2)
DB_USER=$(grep '^DB_USER' "$ENV_FILE" | head -1 | cut -d"'" -f2)
DB_PASS=$(grep '^DB_PASS' "$ENV_FILE" | head -1 | cut -d"'" -f2)
DB_NAME=${DB_NAME:-apache-dashboard}; DB_USER=${DB_USER:-root}; DB_PASS=${DB_PASS:-}

# ══════════════════════════════════════════════════════════════════════
# 5. Crear base de datos MySQL
# ══════════════════════════════════════════════════════════════════════
step "5. Creando base de datos '$DB_NAME'"
explain "El dashboard guarda usuarios, proyectos y configuraciones en MySQL."
explain "Voy a crear la base de datos '$DB_NAME' si no existe todavía."

if sudo mysql -u root -e "SELECT 1" &>/dev/null; then
    sudo mysql -u root -e \
        "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    info "Base de datos '$DB_NAME' creada"

    if [[ "$DB_USER" != "root" ]] && [[ -n "$DB_USER" ]]; then
        explain "Creando usuario '$DB_USER' con permisos sobre '$DB_NAME'..."
        sudo mysql -u root -e "
            CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
            GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
            FLUSH PRIVILEGES;
        " 2>/dev/null && info "Usuario '$DB_USER' configurado"
    fi
else
    warn "No se pudo conectar a MySQL como root."
    warn "Creá la BD manualmente y ejecutá seed.php después."
fi

# ══════════════════════════════════════════════════════════════════════
# 6. Actualizar .htaccess
# ══════════════════════════════════════════════════════════════════════
step "6. Configurando auto_prepend_file"
explain "El dashboard protege los subdirectorios (WordPress, etc.) ejecutando"
explain "un archivo de autenticación ANTES de cada página PHP. Esto se llama"
explain "'auto_prepend_file' y se configura en .htaccess con una ruta absoluta."

AUTH_CHECK="$ROOT/dashboard-logic/auth-check.php"
if [ -f "$HTACCESS" ]; then
    if grep -q "php_value auto_prepend_file" "$HTACCESS"; then
        sed -i "s|php_value auto_prepend_file .*|php_value auto_prepend_file ${AUTH_CHECK}|" "$HTACCESS"
    else
        echo "php_value auto_prepend_file ${AUTH_CHECK}" >> "$HTACCESS"
    fi
    info ".htaccess → $AUTH_CHECK"
else
    warn ".htaccess no encontrado en $ROOT"
fi

# ══════════════════════════════════════════════════════════════════════
# 7. Seed de datos iniciales
# ══════════════════════════════════════════════════════════════════════
step "7. Sembrando datos iniciales"
explain "El seed crea el usuario administrador por defecto y las tablas"
explain "si no existen todavía (no borra nada si ya están creadas)."

php "$SEED" 2>/dev/null && info "Seed ejecutado correctamente" \
    || warn "Ejecutá manualmente: php $SEED"

# ══════════════════════════════════════════════════════════════════════
# 8. Configurar Apache VirtualHost
# ══════════════════════════════════════════════════════════════════════
step "8. Configurando Apache"
explain "Apache necesita saber dónde está el proyecto (DocumentRoot) y que"
explain "puede usar archivos .htaccess (AllowOverride All)."

VHOST_FILE=""
case "$PKG" in
    apt)     VHOST_FILE="/etc/apache2/sites-available/000-default.conf" ;;
    dnf|yum) VHOST_FILE="/etc/httpd/conf.d/000-default.conf" ;;
    pacman)  VHOST_FILE="/etc/httpd/conf/httpd.conf" ;;
    zypper)  VHOST_FILE="/etc/apache2/vhosts.d/000-default.conf" ;;
esac

ENABLE_SITE=""
case "$PKG" in
    apt|zypper) ENABLE_SITE="a2ensite" ;;
esac

if [[ -n "$VHOST_FILE" ]] && [[ -f "$VHOST_FILE" ]]; then
    if grep -q "DocumentRoot" "$VHOST_FILE"; then
        sudo sed -i "s|DocumentRoot .*|DocumentRoot $ROOT|" "$VHOST_FILE"
        info "DocumentRoot → $ROOT"
    fi

    if ! grep -q "AllowOverride All" "$VHOST_FILE"; then
        explain "Agregando AllowOverride All (necesario para .htaccess)..."
        sudo sed -i "/<\/VirtualHost>/i \\
    <Directory $ROOT>\\
        Options Indexes FollowSymLinks\\
        AllowOverride All\\
        Require all granted\\
    <\/Directory>" "$VHOST_FILE" 2>/dev/null
        info "AllowOverride All agregado"
    else
        info "AllowOverride All ya configurado"
    fi

    [[ -n "$ENABLE_SITE" ]] && sudo $ENABLE_SITE 000-default 2>/dev/null
fi

# Reiniciar Apache
explain "Reiniciando Apache para aplicar los cambios..."
if systemctl list-units --type=service 2>/dev/null | grep -q "${APACHE_SVC}.service"; then
    sudo systemctl restart "$APACHE_SVC" 2>/dev/null && info "$APACHE_SVC reiniciado"
elif command -v apache2ctl &>/dev/null; then
    sudo apache2ctl restart 2>/dev/null && info "Apache reiniciado"
elif command -v apachectl &>/dev/null; then
    sudo apachectl restart 2>/dev/null && info "Apache reiniciado"
else
    warn "Reiniciá Apache manualmente"
fi

# ══════════════════════════════════════════════════════════════════════
# Resumen
# ══════════════════════════════════════════════════════════════════════
echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║                                                      ║"
echo "║   ✅  Dashboard instalado                             ║"
echo "║                                                      ║"
echo "║   URL:         http://localhost                       ║"
echo "║   Usuario:     admin@admin                            ║"
echo "║   Contraseña:  Admin123                               ║"
echo "║                                                      ║"
echo "║   ⚠️  Cambiá la contraseña después del primer login   ║"
echo "║                                                      ║"
echo "║   ¿Problemas? Revisá el README.md                     ║"
echo "║                                                      ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""
