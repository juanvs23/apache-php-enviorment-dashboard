<?php
/**
 * Auto-login en un proyecto WordPress vía el dashboard.
 * Busca el primer usuario administrador en la DB y genera sesión.
 * Uso: /wp-auto-login.php?project=twilight
 */

require_once __DIR__ . '/env-loader.php';

// ─── Auth check (misma lógica que index.php) ────────────────────────────────
$key   = $_ENV['DASHBOARD_KEY'] ?? '$z]7hB92d1pT';
$clave = $_ENV['DASHBOARD_CLAVE'] ?? 'Sinal14.';

function _desencriptar($clave, $texto_encriptado) {
    $texto_encriptado = base64_decode($texto_encriptado, true);
    if ($texto_encriptado === false) {
        return false;
    }
    $metodo    = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($metodo);
    $iv        = substr($texto_encriptado, 0, $iv_length);
    return openssl_decrypt(substr($texto_encriptado, $iv_length), $metodo, $clave, 0, $iv);
}

$get_cookie    = $_COOKIE['project_user'] ?? '';
$authenticated = $get_cookie !== '' && _desencriptar($clave, $get_cookie) === $key;

if (!$authenticated) {
    header('Location: /index.php');
    exit;
}

// ─── Validar proyecto ───────────────────────────────────────────────────────
$project = $_GET['project'] ?? '';
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $project)) {
    http_response_code(400);
    exit('Proyecto inválido');
}

$project_dir = __DIR__ . '/../' . $project;
$wp_load     = $project_dir . '/wp-load.php';

if (!file_exists($wp_load)) {
    http_response_code(404);
    exit('WordPress no encontrado en este proyecto');
}

// ─── Bootstrap WordPress (silencioso) ────────────────────────────────────────
define('WP_USE_THEMES', false);
ob_start();
require_once $wp_load;
ob_end_clean();

// ─── Buscar primer administrador ─────────────────────────────────────────────
$admins = get_users([
    'role'   => 'administrator',
    'number' => 1,
    'orderby' => 'ID',
    'order'   => 'ASC',
]);

if (empty($admins)) {
    exit('No se encontró un usuario administrador en este proyecto');
}

$admin = $admins[0];
wp_set_auth_cookie($admin->ID, true);

// ─── Redirigir al sitio ─────────────────────────────────────────────────────
$redirect = !empty($_GET['redirect']) && str_starts_with($_GET['redirect'], '/')
    ? $_GET['redirect']
    : '/' . $project;

wp_redirect($redirect);
exit;
