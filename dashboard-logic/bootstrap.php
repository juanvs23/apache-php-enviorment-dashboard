<?php
/**
 * Bootstrap del dashboard.
 * Inicializa sesión, carga variables de entorno, autoload PSR-4 y constantes.
 */
session_start();
require_once __DIR__ . '/env-loader.php';

// ─── Autoload PSR-4 ──────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $prefix = 'Dashboard\\';
    $baseDir = __DIR__ . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// ─── Rate limiting ────────────────────────────────────────────────────────
define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 900); // 15 minutos

// ─── Cookies ──────────────────────────────────────────────────────────────
define('COOKIE_EXPIRY', 86400 * 7); // 7 días
define('COOKIE_PATH', '/');
