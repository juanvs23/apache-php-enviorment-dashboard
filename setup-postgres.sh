#!/bin/bash
# ============================================================================
# setup-postgres.sh — Instalador de PostgreSQL + pgAdmin4 para Dev Dashboard
# ============================================================================
# Instala PostgreSQL 16 y pgAdmin4 en modo servidor bajo Apache (mod_wsgi).
# Configura autenticación md5, crea usuario pgadmin superuser, y aplica
# optimizaciones WSGI + proxy reverso.
#
# Uso:   sudo bash setup-postgres.sh
# ============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PGADMIN_DIR="/opt/pgadmin4"
PGADMIN_USER="admin@localhost.com"
PGADMIN_PASS="admin"
PG_VERSION="16"

echo "============================================="
echo "  PostgreSQL + pgAdmin4 installer"
echo "============================================="
echo ""

if [ "$(id -u)" -ne 0 ]; then
    echo "[✗] Este script necesita sudo."
    exit 1
fi

# ── Detectar distro ─────────────────────────────────────────────────────────
if command -v apt &>/dev/null; then
    PKG="apt"
    INSTALL="apt install -y -qq"
    DISTRO="debian"
elif command -v dnf &>/dev/null; then
    PKG="dnf"
    INSTALL="dnf install -y -q"
    DISTRO="rhel"
else
    echo "[✗] Distro no soportada. Solo apt (Debian/Ubuntu) y dnf (RHEL/Fedora)."
    exit 1
fi

# ── PostgreSQL ───────────────────────────────────────────────────────────────
echo "→ Instalando PostgreSQL ${PG_VERSION}..."

if [ "$DISTRO" = "debian" ]; then
    # Agregar repo oficial de PostgreSQL
    if ! dpkg -l | grep -q postgresql-${PG_VERSION}; then
        if [ ! -f /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh ]; then
            apt update -qq
            apt install -y -qq curl ca-certificates
            curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | \
                gpg --dearmor -o /usr/share/keyrings/postgresql.gpg
            echo "deb [signed-by=/usr/share/keyrings/postgresql.gpg] http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" \
                > /etc/apt/sources.list.d/pgdg.list
            apt update -qq
        fi
    fi
    $INSTALL postgresql-${PG_VERSION} postgresql-client-${PG_VERSION}
else
    $INSTALL postgresql${PG_VERSION}-server postgresql${PG_VERSION}
    postgresql-${PG_VERSION}-setup initdb 2>/dev/null || true
fi

echo "[✓] PostgreSQL ${PG_VERSION} instalado"

# ── Configurar autenticación md5 ────────────────────────────────────────────
PG_HBA=$(find /etc/postgresql -name pg_hba.conf 2>/dev/null | head -1)
if [ -z "$PG_HBA" ]; then
    PG_HBA="/var/lib/pgsql/${PG_VERSION}/data/pg_hba.conf"
fi

if [ -f "$PG_HBA" ]; then
    # Asegurar md5 para conexiones locales
    if grep -q "^local\s\+all\s\+all\s\+peer" "$PG_HBA"; then
        sed -i 's/^local\s\+all\s\+all\s\+peer/local   all             all                                     md5/' "$PG_HBA"
        echo "[✓] pg_hba.conf: autenticación local cambiada a md5"
    fi
fi

# ── Iniciar PostgreSQL ──────────────────────────────────────────────────────
if command -v systemctl &>/dev/null; then
    systemctl enable postgresql 2>/dev/null || true
    systemctl start postgresql 2>/dev/null || true
elif command -v service &>/dev/null; then
    service postgresql start 2>/dev/null || true
fi

# Esperar a que PostgreSQL esté listo
for i in $(seq 1 10); do
    if pg_isready -q 2>/dev/null; then break; fi
    sleep 1
done
echo "[✓] PostgreSQL corriendo"

# ── Crear usuario pgadmin (superuser) ────────────────────────────────────────
if sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='pgadmin'" | grep -q 1; then
    echo "[✓] Usuario pgadmin ya existe"
else
    sudo -u postgres psql -c "CREATE ROLE pgadmin WITH LOGIN SUPERUSER PASSWORD '${PGADMIN_PASS}';"
    echo "[✓] Usuario pgadmin creado"
fi

# ── pgAdmin4 ─────────────────────────────────────────────────────────────────
echo "→ Instalando pgAdmin4..."

if [ -d "$PGADMIN_DIR" ]; then
    echo "[✓] pgAdmin4 ya está instalado en $PGADMIN_DIR"
else
    # Dependencias
    if [ "$DISTRO" = "debian" ]; then
        $INSTALL python3-venv python3-pip python3-dev \
            libgmp3-dev libpq-dev apache2-dev 2>/dev/null || true
    else
        $INSTALL python3-virtualenv python3-pip python3-devel \
            gmp-devel libpq-devel httpd-devel 2>/dev/null || true
    fi

    # Crear virtualenv
    python3 -m venv $PGADMIN_DIR/venv
    source $PGADMIN_DIR/venv/bin/activate

    # Instalar pgAdmin4 vía pip
    pip install --upgrade pip setuptools wheel 2>&1 | tail -1
    pip install pgadmin4 2>&1 | tail -1

    mkdir -p $PGADMIN_DIR/data/{sessions,storage}
    mkdir -p /var/log/pgadmin

    # Configuración
    cat > $PGADMIN_DIR/data/config_local.py <<PYCONF
import os
LOG_FILE = '/var/log/pgadmin/pgadmin4.log'
SQLITE_PATH = '$PGADMIN_DIR/data/pgadmin.db'
SESSION_DB_PATH = '$PGADMIN_DIR/data/sessions'
STORAGE_DIR = '$PGADMIN_DIR/data/storage'
SERVER_MODE = True
DEFAULT_SERVER = '0.0.0.0'
DEFAULT_SERVER_PORT = 5050
PYCONF

    # Configurar usuario admin de pgAdmin
    source $PGADMIN_DIR/venv/bin/activate
    python3 $PGADMIN_DIR/venv/lib/python*/site-packages/pgadmin4/setup.py <<SETUP
$PGADMIN_USER
$PGADMIN_PASS
$PGADMIN_PASS
SETUP

    # Permisos
    chown -R www-data:www-data $PGADMIN_DIR/data /var/log/pgadmin
    chmod -R 700 $PGADMIN_DIR/data

    echo "[✓] pgAdmin4 instalado en modo servidor"
fi

# ── Apache: mod_wsgi para pgAdmin4 ──────────────────────────────────────────
echo "→ Configurando Apache..."

if [ "$DISTRO" = "debian" ]; then
    $INSTALL libapache2-mod-wsgi-py3 2>/dev/null || true
    a2enmod wsgi 2>/dev/null || true
    CONF_DIR="/etc/apache2/conf-available"
    CONF_CMD="a2enconf pgadmin4"
else
    $INSTALL python3-mod_wsgi 2>/dev/null || true
    CONF_DIR="/etc/httpd/conf.d"
    CONF_CMD=""
fi

cat > "$CONF_DIR/pgadmin4.conf" <<APACHE
# pgAdmin4 — servido bajo Apache vía mod_wsgi
WSGIDaemonProcess pgadmin processes=4 threads=15 \
    python-home=$PGADMIN_DIR/venv \
    python-path=$PGADMIN_DIR/venv/lib/python*/site-packages
WSGIScriptAlias /pgadmin4 $PGADMIN_DIR/venv/lib/python*/site-packages/pgadmin4/pgAdmin4.wsgi

<Directory $PGADMIN_DIR/venv/lib/python*/site-packages/pgadmin4>
    WSGIProcessGroup pgadmin
    WSGIApplicationGroup %{GLOBAL}
    Require all granted
</Directory>
APACHE

if [ "$DISTRO" = "debian" ]; then
    $CONF_CMD 2>/dev/null || true
fi

# ── Incluir optimizaciones si existe el archivo ──────────────────────────────
if [ -f "$SCRIPT_DIR/pgadmin4-optimize.conf" ]; then
    cp "$SCRIPT_DIR/pgadmin4-optimize.conf" "$CONF_DIR/pgadmin4-optimize.conf"
    if [ "$DISTRO" = "debian" ]; then
        a2enconf pgadmin4-optimize 2>/dev/null || true
    fi
    echo "[✓] Optimizaciones WSGI + proxy aplicadas"
fi

# ── Recargar Apache ──────────────────────────────────────────────────────────
if command -v systemctl &>/dev/null; then
    if systemctl is-active apache2 &>/dev/null; then
        systemctl reload apache2
    elif systemctl is-active httpd &>/dev/null; then
        systemctl reload httpd
    fi
fi
echo "[✓] Apache recargado"

# ── Resumen ──────────────────────────────────────────────────────────────────
echo ""
echo "============================================="
echo "  ✅ PostgreSQL + pgAdmin4 instalado"
echo "============================================="
echo ""
echo "  PostgreSQL:"
echo "    Versión:  ${PG_VERSION}"
echo "    Usuario:  pgadmin (superuser)"
echo "    Password: ${PGADMIN_PASS}"
echo "    Puerto:   5432"
echo ""
echo "  pgAdmin4:"
echo "    URL:      http://localhost/pgadmin4/"
echo "    Email:    ${PGADMIN_USER}"
echo "    Password: ${PGADMIN_PASS}"
echo ""
echo "  ⚠️  Cambiá las contraseñas por defecto en producción."
echo ""
