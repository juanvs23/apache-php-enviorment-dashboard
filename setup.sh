#!/usr/bin/env bash
set -euo pipefail

# ═══════════════════════════════════════════════════════════════════════
# Dev Dashboard — Instalación completa (multi-distro)
# ═══════════════════════════════════════════════════════════════════════

ROOT="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$ROOT/.env"; EXAMPLE="$ROOT/.env.example"
HTACCESS="$ROOT/.htaccess"; SEED="$ROOT/seed.php"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()  { echo -e "  ${GREEN}[✓]${NC} $1"; }
warn()  { echo -e "  ${YELLOW}[!]${NC} $1"; }
err()   { echo -e "  ${RED}[✗]${NC} $1"; }
step()  { echo -e "\n${BLUE}─── $1 ───${NC}"; }

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║     Dev Dashboard — Instalación completa             ║"
echo "╚══════════════════════════════════════════════════════╝"
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

DISTRO=$(detect_distro)
PKG=$(detect_pkg_manager)
PHP_VER=$(detect_php_version)
APACHE_SVC=$(detect_apache_service)

echo "  Distro:       $DISTRO"
echo "  Package mgr:  $PKG"
echo "  PHP version:  $PHP_VER"
echo "  Apache:       $APACHE_SVC"

# ─── Verificar sudo ────────────────────────────────────────────────────
if ! sudo -n true 2>/dev/null; then
    err "Necesitás sudo. Ejecutá el script con permisos o configurá NOPASSWD."
    exit 1
fi

# ══════════════════════════════════════════════════════════════════════
# 1. Instalar paquetes del sistema
# ══════════════════════════════════════════════════════════════════════
step "1. Instalando Apache + PHP + MySQL"

# Extensiones necesarias para WordPress, Laravel y PHP puro
# ------------------------------------------------------------------
# Core:        pdo_mysql, openssl, mbstring, session, json (built-in 8.0+)
# WordPress:   gd, imagick, zip, intl, curl, xml
# Laravel:     zip, bcmath, tokenizer, fileinfo, curl, xml, mbstring
# Apache:      libapache2-mod-php / mod_php
# ------------------------------------------------------------------

PHP_SHORT="${PHP_VER/./}"

case "$PKG" in
    apt)
        # Ubuntu/Debian — detectar versión de PHP disponible si no está instalada
        if [[ "$PHP_VER" == "0.0" ]]; then
            PHP_VER=$(apt-cache search '^php[0-9]+\.[0-9]+$' 2>/dev/null | grep -oP 'php\K[0-9]+\.[0-9]+' | sort -V | tail -1)
            PHP_SHORT="${PHP_VER/./}"
            [[ -z "$PHP_VER" ]] && PHP_VER="8.3" && PHP_SHORT="83"
        fi

        info "Instalando PHP $PHP_VER + extensiones..."

        sudo apt-get update -qq

        # Paquete base
        PKGS="apache2 mysql-server mysql-client"

        # PHP core + módulo Apache
        PKGS="$PKGS php${PHP_VER} libapache2-mod-php${PHP_VER}"

        # Extensiones comunes (las que existen como paquete separado)
        for ext in mysql mbstring xml curl gd zip intl bcmath imagick; do
            apt-cache show "php${PHP_VER}-${ext}" &>/dev/null && PKGS="$PKGS php${PHP_VER}-${ext}"
        done

        sudo apt-get install -y -qq $PKGS
        info "Paquetes instalados"
        ;;

    dnf|yum)
        # RHEL/Fedora — requiere EPEL/Remi para PHP moderno
        warn "RHEL/Fedora detectado. Asegurate de tener habilitado Remi/EPEL."

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
        sudo pacman -S --noconfirm apache mysql php php-apache \
            php-gd php-intl php-curl php-sodium
        info "Paquetes instalados"
        ;;

    zypper)
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

case "$PKG" in
    apt)
        # Redetectar versión PHP por si se instaló recién
        PHP_VER=$(detect_php_version)
        PHP_SHORT="${PHP_VER/./}"

        for mod in rewrite "php${PHP_VER}"; do
            if apache2ctl -M 2>/dev/null | grep -qi "${mod}_module"; then
                info "mod_$mod ya activo"
            else
                warn "Activando mod_$mod..."
                sudo a2enmod -q "$mod" 2>/dev/null && info "mod_$mod activado" || warn "No se pudo activar mod_$mod"
            fi
        done
        ;;
    dnf|yum)
        info "En RHEL/Fedora los módulos se gestionan en /etc/httpd/conf.modules.d/"
        info "Verificá que mod_rewrite y mod_php estén activos."
        ;;
    pacman)
        info "En Arch, verificá /etc/httpd/conf/httpd.conf"
        ;;
    zypper)
        for mod in rewrite php8; do
            sudo a2enmod -q "$mod" 2>/dev/null && info "mod_$mod activado" || warn "mod_$mod no disponible"
        done
        ;;
esac

# ══════════════════════════════════════════════════════════════════════
# 3. Verificar extensiones PHP
# ══════════════════════════════════════════════════════════════════════
step "3. Verificando extensiones PHP"

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

if [ -f "$ENV_FILE" ]; then
    warn ".env ya existe — lo salteo"
else
    cp "$EXAMPLE" "$ENV_FILE"

    echo ""
    echo "  Credenciales de MySQL:"
    echo ""
    read -p "  DB_HOST [localhost]: " DB_HOST;    DB_HOST=${DB_HOST:-localhost}
    read -p "  DB_PORT [3306]: " DB_PORT;         DB_PORT=${DB_PORT:-3306}
    read -p "  DB_NAME [apache-dashboard]: " DBN; DB_NAME=${DB_NAME:-apache-dashboard}
    read -p "  DB_USER: " DB_USER
    read -sp "  DB_PASS: " DB_PASS;              echo ""

    sed -i "s|DB_HOST.*|DB_HOST   = '${DB_HOST}'|" "$ENV_FILE"
    sed -i "s|DB_PORT.*|DB_PORT   = '${DB_PORT}'|" "$ENV_FILE"
    sed -i "s|DB_NAME.*|DB_NAME   = '${DB_NAME}'|" "$ENV_FILE"
    sed -i "s|DB_USER.*|DB_USER   = '${DB_USER}'|" "$ENV_FILE"
    sed -i "s|DB_PASS.*|DB_PASS   = '${DB_PASS}'|" "$ENV_FILE"

    info ".env creado"
fi

# Leer valores
source <(grep -E '^DB_' "$ENV_FILE" | sed "s/ //g; s/'//g" || true)
DB_NAME=${DB_NAME:-apache-dashboard}; DB_USER=${DB_USER:-root}; DB_PASS=${DB_PASS:-}

# ══════════════════════════════════════════════════════════════════════
# 5. Crear base de datos MySQL
# ══════════════════════════════════════════════════════════════════════
step "5. Creando base de datos '$DB_NAME'"

if sudo mysql -u root -e "SELECT 1" &>/dev/null; then
    sudo mysql -u root -e \
        "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    info "Base de datos '$DB_NAME' creada"

    if [[ "$DB_USER" != "root" ]] && [[ -n "$DB_USER" ]]; then
        sudo mysql -u root -e "
            CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
            GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
            FLUSH PRIVILEGES;
        " 2>/dev/null && info "Usuario MySQL '$DB_USER' creado"
    fi
else
    warn "No se pudo conectar a MySQL como root. Creá la BD manualmente."
fi

# ══════════════════════════════════════════════════════════════════════
# 6. Actualizar .htaccess
# ══════════════════════════════════════════════════════════════════════
step "6. Configurando auto_prepend_file"

AUTH_CHECK="$ROOT/dashboard-logic/auth-check.php"
if [ -f "$HTACCESS" ]; then
    if grep -q "php_value auto_prepend_file" "$HTACCESS"; then
        sed -i "s|php_value auto_prepend_file .*|php_value auto_prepend_file ${AUTH_CHECK}|" "$HTACCESS"
    else
        echo "php_value auto_prepend_file ${AUTH_CHECK}" >> "$HTACCESS"
    fi
    info ".htaccess actualizado: $AUTH_CHECK"
else
    warn ".htaccess no encontrado"
fi

# ══════════════════════════════════════════════════════════════════════
# 7. Seed de datos iniciales
# ══════════════════════════════════════════════════════════════════════
step "7. Sembrando datos iniciales"

php "$SEED" 2>/dev/null && info "Seed ejecutado" || warn "Ejecutá manualmente: php $SEED"

# ══════════════════════════════════════════════════════════════════════
# 8. Configurar Apache VirtualHost
# ══════════════════════════════════════════════════════════════════════
step "8. Configurando Apache"

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
        info "DocumentRoot actualizado: $ROOT"
    fi

    if ! grep -q "AllowOverride All" "$VHOST_FILE"; then
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

# Reiniciar Apache (respeta el nombre del servicio)
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
echo "║   Contraseña:  Sinal14.                               ║"
echo "║                                                      ║"
echo "║   ⚠️  Cambiá la contraseña después del primer login   ║"
echo "║                                                      ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""
