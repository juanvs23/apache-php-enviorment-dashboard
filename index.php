<?php
/**
 * Dev Dashboard — Orquestador principal.
 *
 * Responsabilidad ÚNICA: rutear la request al handler correcto
 * y renderizar el shell HTML. La lógica de negocio se delega a
 * los controladores vía el Router.
 *
 * Las operaciones que requieren headers HTTP (login, logout, redirects)
 * se ejecutan ANTES del DOCTYPE. El Router maneja solo la renderización
 * del body content DENTRO del shell HTML.
 */

require_once __DIR__ . '/dashboard-logic/bootstrap.php';
require_once __DIR__ . '/dashboard-logic/helpers.php';

use Dashboard\Infrastructure\Auth\AuthContext;
use Dashboard\Infrastructure\Session\SessionManager;
use Dashboard\Presentation\Controller\AuthController;
use Dashboard\Presentation\ServiceContainer;

// ─── Estado global ─────────────────────────────────────────────────────────
$script_name     = $_SERVER['SCRIPT_NAME'];
$authContext     = ServiceContainer::get(AuthContext::class);
$redirect_target = $authContext->redirectTarget($script_name);
$authenticated   = $authContext->isAuthenticated();
$error           = '';

// ─── Rate limiting via SessionManager ──────────────────────────────────────
$sessionManager = ServiceContainer::get(SessionManager::class);
$rate_limited   = $sessionManager->isRateLimited();

if ($rate_limited && !$authenticated) {
    $error = 'Demasiados intentos. Espere 15 minutos.';
}

// ─── Logout via AuthController ─────────────────────────────────────────────
if (isset($_GET['logout'])) {
    $authController = ServiceContainer::get(AuthController::class);
    $authController->logout($script_name); // Hace redirect internamente
    exit;
}

// ─── Login via AuthController ──────────────────────────────────────────────
$isLoginAttempt = ($_POST['email'] ?? '') !== '' && ($_POST['password'] ?? '') !== '';

if (!$authenticated && $isLoginAttempt) {
    if (!$rate_limited) {
        $authController = ServiceContainer::get(AuthController::class);
        $result = $authController->login();
        if ($result['success']) {
            header('Location: ' . $redirect_target);
            exit;
        }
        $error = $result['error'];
    }
}

// Refrescar cookie si ya está autenticado
if ($authenticated) {
    $authContext->refreshCookie();
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dev Dashboard — <?= gethostname() ?></title>
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #1a1d23;
            color: #e4e6eb;
        }
        .card {
            transition: box-shadow .25s ease;
        }
        .card:hover {
            box-shadow: 1px 3px 6px rgba(0,0,0,.5) !important;
        }
        .nav-tabs .nav-link {
            color: #8b949e;
            border: none;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
        }
        .nav-tabs .nav-link:hover {
            color: #e4e6eb;
            border: none;
            background: rgba(255,255,255,.05);
        }
        .nav-tabs .nav-link.active {
            color: #fff;
            background: transparent;
            border-bottom: 2px solid #58a6ff;
        }
        .nav-tabs {
            border-bottom: 1px solid #30363d;
        }
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #1a1d23;
        }
        ::-webkit-scrollbar-thumb {
            background: #30363d;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #484f58;
        }
    </style>
</head>
<body>

<?php
$router = new \Dashboard\Presentation\Router($authContext, $script_name, $error, $authContext->redirectParam());
$router->render();
?>

<script src="./assets/js/bootstrap.min.js"></script>
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.textContent = isPassword ? 'Ocultar' : 'Mostrar';
}
</script>
</body>
</html>
