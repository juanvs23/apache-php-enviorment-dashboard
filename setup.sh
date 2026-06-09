#!/usr/bin/env bash
set -euo pipefail

# ═══════════════════════════════════════════════════════════════════════
# Dev Dashboard — Script de instalación mínima
# ═══════════════════════════════════════════════════════════════════════

ROOT="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$ROOT/.env"
EXAMPLE="$ROOT/.env.example"
HTACCESS="$ROOT/.htaccess"
SEED="$ROOT/seed.php"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()  { echo -e "${GREEN}[✓]${NC} $1"; }
warn()  { echo -e "${YELLOW}[!]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║     Dev Dashboard — Instalación mínima               ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

# ─── 1. Verificar PHP ──────────────────────────────────────────────────
echo "1. Verificando PHP..."
if ! command -v php &>/dev/null; then
    error "PHP no está instalado. Instalá PHP 8.0+ con las extensiones:"
    error "  pdo_mysql, openssl, mbstring, session, json"
    exit 1
fi
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
if (( $(echo "$PHP_VERSION < 8.0" | bc -l) 2>/dev/null || [[ "$PHP_VERSION" < "8.0" ]] )); then
    error "PHP $PHP_VERSION detectado. Se requiere PHP 8.0+"
    exit 1
fi
for ext in pdo_mysql openssl mbstring session json; do
    php -m 2>/dev/null | grep -qi "$ext" || { error "Extensión PHP '$ext' no encontrada"; exit 1; }
done
info "PHP $PHP_VERSION con todas las extensiones"

# ─── 2. Verificar MySQL ────────────────────────────────────────────────
echo "2. Verificando MySQL..."
if ! command -v mysql &>/dev/null; then
    error "MySQL client no encontrado. Instalá MySQL 8.0+"
    exit 1
fi
info "MySQL client detectado"

# ─── 3. Configurar .env ────────────────────────────────────────────────
echo "3. Configurando .env..."
if [ -f "$ENV_FILE" ]; then
    warn ".env ya existe — lo salteo"
else
    cp "$EXAMPLE" "$ENV_FILE"
    echo ""
    echo "  Configurá las credenciales de MySQL:"
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

    info ".env creado con credenciales"
fi

# ─── 4. Crear base de datos ────────────────────────────────────────────
# Leer del .env real
source <(grep -E '^DB_' "$ENV_FILE" | sed "s/ //g" | sed "s/'//g")
DB_NAME=${DB_NAME:-apache-dashboard}

echo "4. Creando base de datos '$DB_NAME'..."
if mysql -u "$DB_USER" -p"$DB_PASS" -h "${DB_HOST:-localhost}" -P "${DB_PORT:-3306}" \
    -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
    info "Base de datos '$DB_NAME' lista"
else
    warn "No se pudo crear la BD automáticamente. Creala manualmente:"
    warn "  mysql -u root -p -e \"CREATE DATABASE IF NOT EXISTS \\\`$DB_NAME\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
fi

# ─── 5. Actualizar ruta en .htaccess ───────────────────────────────────
echo "5. Actualizando auto_prepend_file en .htaccess..."
AUTH_CHECK="$ROOT/dashboard-logic/auth-check.php"
if grep -q "php_value auto_prepend_file" "$HTACCESS" 2>/dev/null; then
    sed -i "s|php_value auto_prepend_file .*|php_value auto_prepend_file ${AUTH_CHECK}|" "$HTACCESS"
    info ".htaccess actualizado: $AUTH_CHECK"
else
    warn ".htaccess no contiene 'php_value auto_prepend_file' — agregalo manualmente"
    warn "  php_value auto_prepend_file ${AUTH_CHECK}"
fi

# ─── 6. Seed de datos iniciales ────────────────────────────────────────
echo "6. Sembrando datos iniciales..."
if php "$SEED" 2>/dev/null; then
    info "Seed ejecutado: admin@admin / Sinal14."
else
    # El seed puede fallar si las tablas ya existen (INSERT IGNORE lo maneja)
    # Probemos sin capturar stderr para ver el error real
    warn "Seed encontró un problema. Intentá manualmente: php $SEED"
fi

# ─── 7. Verificar Apache ───────────────────────────────────────────────
echo "7. Verificando Apache..."
if apache2ctl -M 2>/dev/null | grep -qi 'php_module'; then
    info "mod_php detectado"
elif apachectl -M 2>/dev/null | grep -qi 'php'; then
    info "Módulo PHP detectado"
else
    warn "No se detectó mod_php. Verificá que PHP esté como módulo de Apache."
    warn "  sudo apt install libapache2-mod-php"
    warn "  sudo a2enmod php8.3 && sudo systemctl restart apache2"
fi

# ─── Resumen ───────────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║  Instalación completada                              ║"
echo "╠══════════════════════════════════════════════════════╣"
echo "║                                                      ║"
echo "║  Dashboard:  http://localhost                        ║"
echo "║  Usuario:    admin@admin                             ║"
echo "║  Contraseña: Sinal14.                                ║"
echo "║                                                      ║"
echo "║  ⚠️  Cambiá la contraseña después del primer login   ║"
echo "║                                                      ║"
echo "║  Asegurate que Apache tenga AllowOverride All:       ║"
echo "║    <Directory $ROOT>                                 ║"
echo "║        AllowOverride All                             ║"
echo "║        Require all granted                           ║"
echo "║    </Directory>                                      ║"
echo "║                                                      ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""
