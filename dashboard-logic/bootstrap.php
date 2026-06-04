<?php
/**
 * Bootstrap del dashboard.
 * Inicializa sesión, carga variables de entorno y configura constantes.
 */
session_start();
require_once __DIR__ . '/../env-loader.php';

// ─── Rate limiting ────────────────────────────────────────────────────────
define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 900); // 15 minutos

// ─── Cookies ──────────────────────────────────────────────────────────────
define('COOKIE_EXPIRY', 86400 * 7); // 7 días
define('COOKIE_PATH', '/');
