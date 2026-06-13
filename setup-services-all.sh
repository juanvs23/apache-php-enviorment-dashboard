#!/bin/bash
# ============================================================================
# setup-services-all.sh — Instalador maestro de servicios para Dev Dashboard
# ============================================================================
# Ejecuta setup-phpmyadmin.sh y setup-postgres.sh en secuencia.
# Idempotente: si un servicio ya está instalado, lo saltea.
#
# Uso:   sudo bash setup-services-all.sh
# ============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "============================================="
echo "  Dev Dashboard — Instalador de servicios"
echo "============================================="
echo ""

if [ "$(id -u)" -ne 0 ]; then
    echo "[✗] Este script necesita sudo."
    exit 1
fi

# ── phpMyAdmin ────────────────────────────────────────────────────────────────
if [ -f "$SCRIPT_DIR/setup-phpmyadmin.sh" ]; then
    echo "→ Ejecutando setup-phpmyadmin.sh..."
    bash "$SCRIPT_DIR/setup-phpmyadmin.sh"
    echo ""
else
    echo "[!] setup-phpmyadmin.sh no encontrado, salteando."
    echo ""
fi

# ── PostgreSQL + pgAdmin4 ─────────────────────────────────────────────────────
if [ -f "$SCRIPT_DIR/setup-postgres.sh" ]; then
    echo "→ Ejecutando setup-postgres.sh..."
    bash "$SCRIPT_DIR/setup-postgres.sh"
    echo ""
else
    echo "[!] setup-postgres.sh no encontrado, salteando."
    echo ""
fi

echo "============================================="
echo "  ✅ Instalación de servicios completa"
echo "============================================="
echo ""
echo "  phpMyAdmin:   http://localhost/phpmyadmin/"
echo "  pgAdmin4:     http://localhost/pgadmin4/"
echo ""
echo "  Guía completa: SETUP-SERVICES.md"
echo ""
