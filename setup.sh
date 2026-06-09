#!/usr/bin/env bash
set -euo pipefail

# ═══════════════════════════════════════════════════════════════════════
# Dev Dashboard — Instalación completa (Ubuntu/Debian)
# ═══════════════════════════════════════════════════════════════════════

ROOT="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$ROOT/.env"
EXAMPLE="$ROOT/.env.example"
HTACCESS="$ROOT/.htaccess"
SEED="$ROOT/seed.php"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()  { echo -e "  ${GREEN}[✓]${NC} $1"; }
warn()  { echo -e "  ${YELLOW}[!]${NC} $1"; }
error() { echo -e "  ${RED}[✗]${NC} $1"; }
step()  { echo -e "\n${BLUE}─── $1 ───${NC}"; }

# Verificar sudo
if ! sudo -n true 2>/dev/null; then
    echo ""
    echo "  Este script necesita sudo para instalar paquetes."
    echo ""
    exit 1
fi

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║     Dev Dashboard — Instalación completa             ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

# ══════════════════════════════════════════════════════════════════════
# 1. Instalar Apache + PHP + MySQL
# ══════════════════════════════════════════════════════════════════════
step "1. Instalando paquetes del sistema"

PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "0.0")
if (( $(echo "$PHP_VERSION < 8.0" | bc -l 2>/dev/null) )) || [[ "$PHP_VERSION" == "0.0" ]]; then
    warn "PHP $PHP_VERSION — instalando PHP 8.3..."
    sudo apt-get update -qq
    sudo apt-get install -y -qq \
        apache2 \
        php8.3 \
        php8.3-mysql \
        php8.3-mbstring \
        php8.3-xml \
        php8.3-curl \
        libapache2-mod-php8.3 \
        mysql-server \
        mysql-client
    info "Apache + PHP 8.3 + MySQL instalados"
else
    info "PHP $PHP_VERSION detectado"

    # Asegurar que paquetes necesarios estén instalados
    NEEDED=""
    dpkg -l apache2          &>/dev/null || NEEDED="$NEEDED apache2"
    dpkg -l mysql-server     &>/dev/null || NEEDED="$NEEDED mysql-server"
    dpkg -l "php${PHP_VERSION/./.}-mysql" &>/dev/null || NEEDED="$NEEDED php${PHP_VERSION/./.}-mysql"
    dpkg -l "php${PHP_VERSION/./.}-mbstring" &>/dev/null || NEEDED="$NEEDED php${PHP_VERSION/./.}-mbstring"
    dpkg -l "libapache2-mod-php${PHP_VERSION/./.}" &>/dev/null || NEEDED="$NEEDED libapache2-mod-php${PHP_VERSION/./.}"

    if [[ -n "$NEEDED" ]]; then
        warn "Faltan paquetes, instalando:$NEEDED"
        sudo apt-get update -qq
        sudo apt-get install -y -qq $NEEDED
        info "Paquetes instalados"
    else
        info "Todos los paquetes del sistema presentes"
    fi
fi

# ══════════════════════════════════════════════════════════════════════
# 2. Activar módulos de Apache
# ══════════════════════════════════════════════════════════════════════
step "2. Activando módulos de Apache"

MODULES="rewrite php8.3"
for mod in $MODULES; do
    if apache2ctl -M 2>/dev/null | grep -qi "${mod}_module"; then
        info "mod_$mod ya activo"
    else
        warn "Activando mod_$mod..."
        sudo a2enmod -q "$mod" 2>/dev/null && info "mod_$mod activado"
    fi
done

# ══════════════════════════════════════════════════════════════════════
# 3. Verificar extensiones PHP
# ══════════════════════════════════════════════════════════════════════
step "3. Verificando extensiones PHP"

for ext in pdo_mysql openssl mbstring session json; do
    php -m 2>/dev/null | grep -qi "$ext" && info "$ext" || {
        error "$ext no encontrada — instalá php8.3-$ext"
    }
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

    read -p "  DB_HOST [localhost]: " DB_HOST
    DB_HOST=${DB_HOST:-localhost}

    read -p "  DB_PORT [3306]: " DB_PORT
    DB_PORT=${DB_PORT:-3306}

    read -p "  DB_NAME [apache-dashboard]: " DB_NAME
    DB_NAME=${DB_NAME:-apache-dashboard}

    read -p "  DB_USER: " DB_USER

    read -sp "  DB_PASS: " DB_PASS
    echo ""

    sed -i "s|DB_HOST.*|DB_HOST   = '${DB_HOST}'|" "$ENV_FILE"
    sed -i "s|DB_PORT.*|DB_PORT   = '${DB_PORT}'|" "$ENV_FILE"
    sed -i "s|DB_NAME.*|DB_NAME   = '${DB_NAME}'|" "$ENV_FILE"
    sed -i "s|DB_USER.*|DB_USER   = '${DB_USER}'|" "$ENV_FILE"
    sed -i "s|DB_PASS.*|DB_PASS   = '${DB_PASS}'|" "$ENV_FILE"

    info ".env creado"
fi

# Leer valores para el resto del script
source <(grep -E '^DB_' "$ENV_FILE" | sed "s/ //g" | sed "s/'//g" || true)
DB_NAME=${DB_NAME:-apache-dashboard}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}

# ══════════════════════════════════════════════════════════════════════
# 5. Crear base de datos MySQL
# ══════════════════════════════════════════════════════════════════════
step "5. Creando base de datos '$DB_NAME'"

if sudo mysql -u root -e "SELECT 1" &>/dev/null; then
    sudo mysql -u root -e "
        CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    " 2>/dev/null && info "Base de datos '$DB_NAME' creada"

    # Crear usuario si se especificó algo distinto de root
    if [[ "$DB_USER" != "root" ]] && [[ -n "$DB_USER" ]]; then
        sudo mysql -u root -e "
            CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
            GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
            FLUSH PRIVILEGES;
        " 2>/dev/null && info "Usuario MySQL '$DB_USER' creado/actualizado"
    fi
else
    warn "No se pudo conectar a MySQL como root. Creá la BD manualmente:"
    warn "  sudo mysql -u root -e \"CREATE DATABASE IF NOT EXISTS \\\`$DB_NAME\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
fi

# ══════════════════════════════════════════════════════════════════════
# 6. Actualizar ruta en .htaccess
# ══════════════════════════════════════════════════════════════════════
step "6. Configurando auto_prepend_file"

AUTH_CHECK="$ROOT/dashboard-logic/auth-check.php"
if [ -f "$HTACCESS" ]; then
    if grep -q "php_value auto_prepend_file" "$HTACCESS"; then
        sed -i "s|php_value auto_prepend_file .*|php_value auto_prepend_file ${AUTH_CHECK}|" "$HTACCESS"
        info ".htaccess actualizado"
    else
        echo "php_value auto_prepend_file ${AUTH_CHECK}" >> "$HTACCESS"
        info ".htaccess actualizado (línea agregada)"
    fi
else
    warn ".htaccess no encontrado en $ROOT"
fi

# ══════════════════════════════════════════════════════════════════════
# 7. Seed de datos iniciales
# ══════════════════════════════════════════════════════════════════════
step "7. Sembrando datos iniciales"

if php "$SEED" 2>/dev/null; then
    info "Seed ejecutado correctamente"
else
    warn "El seed encontró un problema. Podés ejecutarlo manualmente:"
    warn "  php $SEED"
fi

# ══════════════════════════════════════════════════════════════════════
# 8. Configurar Apache VirtualHost
# ══════════════════════════════════════════════════════════════════════
step "8. Configurando Apache"

VHOST_FILE="/etc/apache2/sites-available/000-default.conf"
if [ -f "$VHOST_FILE" ]; then
    # Actualizar DocumentRoot
    if grep -q "DocumentRoot" "$VHOST_FILE"; then
        sudo sed -i "s|DocumentRoot .*|DocumentRoot $ROOT|" "$VHOST_FILE"
        info "DocumentRoot actualizado: $ROOT"
    fi

    # Asegurar AllowOverride All
    DIR_BLOCK="<Directory $ROOT>"
    if ! grep -q "AllowOverride All" "$VHOST_FILE"; then
        # Insertar después del cierre de </VirtualHost> o al final del Directory block
        sudo sed -i "/<\/VirtualHost>/i \\
    $DIR_BLOCK\\
        Options Indexes FollowSymLinks\\
        AllowOverride All\\
        Require all granted\\
    <\/Directory>" "$VHOST_FILE" 2>/dev/null || {
            warn "No se pudo editar el VirtualHost. Agregá manualmente:"
            warn "  $DIR_BLOCK"
            warn "      AllowOverride All"
            warn "      Require all granted"
            warn "  </Directory>"
        }
        info "VirtualHost actualizado"
    else
        info "AllowOverride All ya configurado"
    fi
else
    warn "No se encontró el VirtualHost en $VHOST_FILE"
    warn "Asegurate de configurar:"
    warn "  DocumentRoot $ROOT"
    warn "  <Directory $ROOT>"
    warn "      AllowOverride All"
    warn "      Require all granted"
    warn "  </Directory>"
fi

# Recargar Apache
sudo systemctl restart apache2 2>/dev/null && info "Apache reiniciado"

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
