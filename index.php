<?php
/**
 * Dev Dashboard — Orquestador principal.
 *
 * Responsabilidad ÚNICA: rutear la request al handler correcto
 * y renderizar el shell HTML. Cero lógica de negocio.
 */

require_once __DIR__ . '/dashboard-logic/bootstrap.php';
require_once __DIR__ . '/dashboard-logic/helpers.php';
require_once __DIR__ . '/dashboard-logic/rate-limiter.php';
require_once __DIR__ . '/dashboard-logic/auth.php';
require_once __DIR__ . '/dashboard-logic/projects.php';

// ─── Estado global ─────────────────────────────────────────────────────────
$script_name     = $_SERVER['SCRIPT_NAME'];
$redirect_param  = get_redirect_param();
$redirect_target = get_redirect_target($script_name);
$authenticated   = check_auth();
$rate_limited    = check_rate_limit();
$error           = '';

// Si está bloqueado por rate limit, mostrar mensaje de entrada
if ($rate_limited && !$authenticated) {
    $error = 'Demasiados intentos. Espere 15 minutos.';
}

// ─── Logout ───────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    do_logout();
    header('Location: ' . $script_name);
    exit;
}

// ─── Login ────────────────────────────────────────────────────────────────
if (!$authenticated && is_login_attempt()) {
    if ($rate_limited) {
        // Silencio — el error ya está seteado arriba
    } else {
        $result = attempt_login();
        if ($result['success']) {
            header('Location: ' . $redirect_target);
            exit;
        }
        $error = $result['error'];
    }
}

// Refrescar cookie de sesión si ya está autenticado
if ($authenticated) {
    refresh_auth_cookie();
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dev Dashboard — <?= gethostname() ?></title>
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
        .server-card { min-height: 100px; }
        .project-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,.15); transition: .2s; }
        .logout-link { position: absolute; top: 1rem; right: 1.5rem; z-index: 10; }
    </style>
</head>
<body>

<?php if ($authenticated): ?>

    <?php if (isset($_GET['phpinfo'])): ?>
        <?php phpinfo(); ?>
    <?php else: ?>
        <?php
        $projects     = list_projects();
        $has_projects = !empty($projects);
        require __DIR__ . '/dashboard-logic/views/dashboard.php';
        ?>
    <?php endif; ?>

<?php else: ?>

    <?php require __DIR__ . '/dashboard-logic/views/login.php'; ?>

<?php endif; ?>

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
