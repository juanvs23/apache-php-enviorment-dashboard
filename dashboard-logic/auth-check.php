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

require_once __DIR__ . '/env-loader.php';

$__script = $_SERVER['SCRIPT_NAME'] ?? '';

// ─── Permitir el dashboard y sus assets ─────────────────────────────────
if (preg_match('#^/(index\.php|dashboard-logic/wp-auto-login\.php|assets/)#', $__script)) {
    return;
}

// ─── Auth por cookie + DB ───────────────────────────────────────────────
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
            $_ENV['DB_USER'] ?? 'juanvs23',
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
    header('Location: /index.php?redirect=' . urlencode($__script));
    exit;
}
