<?php
/**
 * Prepend de autenticación para proyectos internos.
 *
 * Se ejecuta antes de CADA request PHP en cualquier subdirectorio
 * vía php_value auto_prepend_file en el .htaccess raíz.
 *
 * URLs permitidas sin auth:
 *   - index.php, dashboard-logic/wp-auto-login.php, assets/
 */

$__script = $_SERVER['SCRIPT_NAME'] ?? '';

// ─── Permitir el dashboard y sus assets ─────────────────────────────────
if (preg_match('#^/(index\.php|dashboard-logic/wp-auto-login\.php|assets/)#', $__script)) {
    return;
}

// ─── Auth ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/env-loader.php';

$__key   = $_ENV['DASHBOARD_KEY'] ?? '$z]7hB92d1pT';
$__clave = $_ENV['DASHBOARD_CLAVE'] ?? 'Sinal14.';

function __auth_desencriptar($clave, $texto_encriptado) {
    $texto_encriptado = base64_decode($texto_encriptado, true);
    if ($texto_encriptado === false) {
        return false;
    }
    $metodo    = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($metodo);
    $iv        = substr($texto_encriptado, 0, $iv_length);
    return openssl_decrypt(substr($texto_encriptado, $iv_length), $metodo, $clave, 0, $iv);
}

$__cookie    = $_COOKIE['project_user'] ?? '';
$__authed    = $__cookie !== '' && __auth_desencriptar($__clave, $__cookie) === $__key;

if (!$__authed) {
    header('Location: /index.php?redirect=' . urlencode($__script));
    exit;
}
