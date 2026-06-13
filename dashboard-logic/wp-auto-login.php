<?php
/**
 * Auto-login en un proyecto WordPress vía el dashboard.
 * Busca el primer usuario administrador en la DB y genera sesión.
 * Uso: /wp-auto-login.php?project=twilight
 */

require_once __DIR__ . '/env-loader.php';

// ─── Auth por cookie + DB ────────────────────────────────────────────────
$__cookie  = $_COOKIE['project_user'] ?? '';
$__userID  = $__cookie !== '' ? base64_decode($__cookie, true) : false;
$__authed  = false;

if ($__userID !== false && $__userID !== '') {
    try {
        $__pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_PORT'] ?? '3306',
                $_ENV['DB_NAME'] ?? 'apache-dashboard'
            ),
            $_ENV['DB_USER'] ?? '',
            $_ENV['DB_PASS'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $__stmt = $__pdo->prepare('SELECT 1 FROM USERS WHERE userID = :userID LIMIT 1');
        $__stmt->execute([':userID' => $__userID]);
        $__authed = (bool) $__stmt->fetchColumn();
    } catch (Throwable $e) {
        $__authed = false;
    }
}

if (!$__authed) {
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
