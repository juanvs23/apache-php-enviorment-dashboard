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

// Refrescar cookie si ya está autenticado
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

<?php if ($authenticated): ?>

    <?php if (isset($_GET['phpinfo'])): ?>
        <?php phpinfo(); ?>
    <?php elseif (isset($_GET['users'])): ?>
        <?php
        require_once __DIR__ . '/dashboard-logic/user-management.php';

        $tab = $_GET['tab'] ?? 'usuarios';

        if ($tab === 'levels') {
            require_once __DIR__ . '/dashboard-logic/level-management.php';
            $msg      = '';
            $msg_type = 'success';
            $result   = process_level_action();
            if ($result) {
                $msg      = $result['error'] ?? 'Operación exitosa';
                $msg_type = $result['success'] ? 'success' : 'danger';
            }
            $levels      = get_all_levels_with_perms();
            $permissions = get_all_permissions();
            require __DIR__ . '/dashboard-logic/views/level-management.php';
        } else {
            $msg      = '';
            $msg_type = 'success';
            $result   = process_user_action();
            if ($result) {
                $msg      = $result['error'] ?? 'Operación exitosa';
                $msg_type = $result['success'] ? 'success' : 'danger';
            }
            $users        = get_all_users();
            $levels       = get_all_levels();
            $projects     = get_all_projects();
            $client_users = get_client_users();
            require __DIR__ . '/dashboard-logic/views/user-management.php';
        }
        ?>
    <?php elseif (isset($_GET['profile'])): ?>
        <?php
        require_once __DIR__ . '/dashboard-logic/profile.php';
        $msg      = '';
        $msg_type = 'success';
        $result   = process_profile_action();
        if ($result) {
            $msg      = $result['error'] ?? 'Perfil actualizado';
            $msg_type = $result['success'] ? 'success' : 'danger';
        }
        $user = get_auth_user();
        require __DIR__ . '/dashboard-logic/views/profile.php';
        ?>
    <?php else: ?>
        <?php
        $projects     = list_projects();
        $auth_user    = get_auth_user();

        // Enriquecer proyectos con acept_login desde la DB
        try {
            $pdo = \Dashboard\Database\Connection::get();
            $dbProjects = $pdo->query('SELECT project_name, acept_login FROM Project')->fetchAll(\PDO::FETCH_ASSOC);
            $acceptMap = [];
            foreach ($dbProjects as $dbp) {
                $acceptMap[strtolower(trim($dbp['project_name']))] = (int) $dbp['acept_login'];
            }
            foreach ($projects as &$p) {
                $p['acept_login'] = $acceptMap[strtolower($p['dir'])] ?? 0;
            }
            unset($p);

            // Admin ve TODOS los botones siempre
            if ($auth_user && can('projects.acept_login', $auth_user)) {
                foreach ($projects as &$p) {
                    $p['acept_login'] = 1;
                }
                unset($p);
            }
        } catch (\Throwable $e) {
            // Si falla la DB, asumir 0
        }

        if ($auth_user && !can('projects.view_all', $auth_user)) {
            $allowed = [];
            try {
                $stmt = $pdo->prepare('SELECT project_name FROM Project WHERE user_own = :uid');
                $stmt->execute([':uid' => $auth_user['userID']]);
                $allowed = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            } catch (\Throwable $e) {
                $allowed = [];
            }
            $projects = array_values(array_filter($projects, fn($p) =>
                in_array(strtolower($p['dir']), array_map('strtolower', $allowed))
            ));
        }
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
