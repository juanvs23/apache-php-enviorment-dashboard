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

use Dashboard\Infrastructure\Auth\AuthContext;
use Dashboard\Infrastructure\Session\SessionManager;
use Dashboard\Presentation\Controller\AuthController;
use Dashboard\Presentation\ServiceContainer;

// ─── Estado global ─────────────────────────────────────────────────────────
$script_name     = $_SERVER['SCRIPT_NAME'];
$authContext     = ServiceContainer::get(AuthContext::class);
$redirect_target = $authContext->redirectTarget($script_name);
$authenticated   = $authContext->isAuthenticated();
$isAdmin         = false;
if ($authenticated) {
    $authUser = $authContext->currentUser();
    $isAdmin  = $authUser && ($authUser['level_type'] ?? 1) === 0;
}
$error           = '';

// ─── Rate limiting via SessionManager ──────────────────────────────────────
$sessionManager = ServiceContainer::get(SessionManager::class);
$rate_limited   = $sessionManager->isRateLimited();

if ($rate_limited && !$authenticated) {
    $error = 'Demasiados intentos. Espere 15 minutos.';
}

// ─── Logout via AuthController ─────────────────────────────────────────────
if (isset($_GET['logout'])) {
    // Log the logout before redirect
    if ($authenticated && $authUser) {
        $logger = ServiceContainer::get(\Dashboard\Infrastructure\Auth\AuthLogger::class);
        $logger->log($authUser['email'] ?? 'unknown', 'logout');
    }
    $authController = ServiceContainer::get(AuthController::class);
    $authController->logout($script_name); // Hace redirect internamente
    exit;
}

// ─── npm install (solo admin autenticado) ───────────────────────────────────
if (isset($_GET['npm_action']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $npmDir = $_GET['dir'] ?? '';
    if ($authenticated && $isAdmin && $npmDir !== '') {
        $projectDir = realpath(__DIR__ . '/' . $npmDir);
        $valid = $projectDir && is_dir($projectDir) && file_exists($projectDir . '/package.json')
                 && str_starts_with($projectDir, realpath(__DIR__) ?: '');
        if ($valid) {
            $owner = posix_getpwuid(fileowner($projectDir))['name'] ?? 'www-data';
            $sudo  = ($owner !== 'www-data') ? "sudo -u {$owner}" : '';

            // Buscar npm: wrapper del proyecto → system paths → fnm
            $npm = '';
            $wrapper = __DIR__ . '/dashboard-logic/dashboard-npm';
            if (file_exists($wrapper)) { $npm = $wrapper; }
            if ($npm === '') {
                foreach (['/usr/bin/npm', '/usr/local/bin/npm'] as $c) {
                    if (file_exists($c)) { $npm = $c; break; }
                }
            }
            // Si npm es via fnm, necesitamos node en el PATH también
            $fnmEnv = '';
            if ($npm && str_contains($npm, 'fnm')) {
                $fnmDir = dirname(dirname(dirname(dirname(dirname($npm)))));
                $fnmBin = dirname(dirname(dirname($npm))) . '/bin';
                $fnmEnv = "FNM_DIR={$fnmDir} PATH={$fnmBin}:\$PATH";
            }
            if ($npm === '') {
                header('Location: /?flash=danger&msg=' . urlencode('npm no encontrado'));
                exit;
            }

            $cd  = 'cd ' . escapeshellarg($projectDir) . ' && ';
            $act = $_GET['npm_action'];
            $exitCode = 0; $output = [];

            switch ($act) {
                case 'install':
                    exec($cd . "npm_config_cache=/tmp/npm-cache {$sudo} {$npm} install 2>&1", $output, $exitCode);
                    $t = $exitCode === 0 ? 'success' : 'danger';
                    $m = $exitCode === 0 ? '📦 Dependencias instaladas' : 'Error al instalar dependencias';
                    break;

                case 'start':
                    $pidFile = $projectDir . '/.pid';
                    // Limpiar TODOS los servidores Vite previos (evita acumulación)
                    exec('pkill -f "node.*vite" 2>/dev/null; pkill -f "npm run dev" 2>/dev/null');
                    sleep(1);
                    @unlink($pidFile);
                    exec($cd . "nohup {$sudo} {$npm} run dev > /dev/null 2>&1 & echo \$!", $output, $exitCode);
                    if ($exitCode === 0 && !empty($output[0])) {
                        file_put_contents($pidFile, trim($output[0]));
                        $t = 'success'; $m = '🚀 Dev server iniciado en el proyecto';
                    } else {
                        $t = 'danger'; $m = 'Error al iniciar: ' . implode(' | ', $output);
                    }
                    break;

                case 'stop':
                    $pidFile = $projectDir . '/.pid';
                    if (file_exists($pidFile)) {
                        $pid = (int) file_get_contents($pidFile);
                        if ($pid > 0) { exec("kill {$pid} 2>&1", $output, $exitCode); }
                        @unlink($pidFile);
                        $t = 'success'; $m = '⏹ Servidor detenido';
                    } else {
                        $t = 'warning'; $m = 'No hay servidor corriendo';
                    }
                    break;

                default:
                    $t = 'danger'; $m = 'Acción no válida';
            }
            header("Location: /?flash={$t}&msg=" . urlencode($m));
            exit;
        } else {
            $msg = urlencode('Proyecto no válido o sin package.json');
            header("Location: /?flash=danger&msg={$msg}");
            exit;
        }
    }
    header('Location: ' . $redirect_target);
    exit;
}

// ─── Crear proyecto (solo admin autenticado) ───────────────────────────────
if (isset($_GET['create_project']) && in_array($_GET['create_project'], ['html', 'laravel', 'wordpress']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($authenticated && $isAdmin) {
        $name = trim($_POST['project_name'] ?? '');
        $dir  = trim($_POST['directory'] ?? '');
        $repo = trim($_POST['repo_url'] ?? '');
        $branch = trim($_POST['branch'] ?? '') ?: null;
        $useVite = ($_POST['use_vite'] ?? '') === '1';

        if ($name === '' || $dir === '') {
            $msg = urlencode('Nombre y directorio son requeridos.');
            header("Location: /?flash=danger&msg={$msg}");
            exit;
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $dir)) {
            $msg = urlencode('El directorio solo puede contener letras, números, guiones y guiones bajos.');
            header("Location: /?flash=danger&msg={$msg}");
            exit;
        }
        if (str_contains($dir, '..') || str_starts_with($dir, '/')) {
            $msg = urlencode('Directorio no válido.');
            header("Location: /?flash=danger&msg={$msg}");
            exit;
        }

        try {
            $creator = ServiceContainer::get(\Dashboard\Infrastructure\Filesystem\ProjectCreator::class);
            $saveProject = ServiceContainer::get(\Dashboard\Application\UseCase\Project\SaveProjectUseCase::class);

            // ── Crear en filesystem ──────────────────────────────────
            if ($repo !== '') {
                $creator->createFromGithub($name, $dir, $repo, $branch);
                $successMsg = "Proyecto clonado: {$name}";
            } elseif ($_GET['create_project'] === 'html') {
                $creator->createHtml($name, $dir, $useVite);
                $viteMsg = $useVite ? ' con Vite.js' : '';
                $successMsg = "Proyecto creado: {$name}{$viteMsg}";
            } elseif ($_GET['create_project'] === 'laravel') {
                $dbName = trim($_POST['db_name'] ?? '');
                if ($dbName === '') {
                    header("Location: /?flash=danger&msg=" . urlencode('El nombre de la base de datos es requerido'));
                    exit;
                }
                if (!preg_match('/^[a-zA-Z_]\w*$/', $dbName)) {
                    header("Location: /?flash=danger&msg=" . urlencode('Nombre de base de datos no válido. Solo letras, números y guiones bajos. No puede empezar con número.'));
                    exit;
                }
                if ($creator->databaseExists($dbName)) {
                    header("Location: /?flash=danger&msg=" . urlencode("La base de datos '{$dbName}' ya existe. Elegí otro nombre."));
                    exit;
                }
                $creator->createLaravel($name, $dir, $dbName);
                $successMsg = "Proyecto Laravel creado: {$name}";
            } elseif ($_GET['create_project'] === 'wordpress') {
                $dbName    = trim($_POST['db_name'] ?? '');
                $title     = trim($_POST['site_title'] ?? '');
                $wpEmail   = trim($_POST['admin_email'] ?? '');
                $wpPass    = $_POST['admin_password'] ?? '';
                if ($dbName === '' || $title === '' || $wpEmail === '' || $wpPass === '') {
                    header("Location: /?flash=danger&msg=" . urlencode('Todos los campos son requeridos'));
                    exit;
                }
                if (!preg_match('/^[a-zA-Z_]\w*$/', $dbName)) {
                    header("Location: /?flash=danger&msg=" . urlencode('Nombre de base de datos no válido. Solo letras, números y guiones bajos. No puede empezar con número.'));
                    exit;
                }
                if ($creator->databaseExists($dbName)) {
                    header("Location: /?flash=danger&msg=" . urlencode("La base de datos '{$dbName}' ya existe. Elegí otro nombre."));
                    exit;
                }
                $creator->createWordpress($name, $dir, $dbName, $title, $wpEmail, $wpPass);
                $successMsg = "Proyecto WordPress creado: {$name}";
            }

            // ── Registrar en la base de datos del dashboard ──────────
            $projectId = \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex(\random_bytes(16)), 4));
            $saveProject->create($projectId, $dir);

            header("Location: /?flash=success&msg=" . urlencode($successMsg));
        } catch (\RuntimeException $e) {
            header("Location: /?flash=danger&msg=" . urlencode($e->getMessage()));
        }
        exit;
    }
}
// ─── Eliminar proyecto (solo admin) ────────────────────────────────────────
if (isset($_GET['delete_project']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $delDir = $_GET['delete_project'] ?? '';
    if ($authenticated && $isAdmin && $delDir !== '') {
        $projectDir = realpath(__DIR__ . '/' . $delDir);
        $valid = $projectDir && is_dir($projectDir)
                 && str_starts_with($projectDir, realpath(__DIR__) ?: '')
                 && $delDir !== 'dashboard-logic' && $delDir !== 'assets';
        if ($valid) {
            // Detener Vite si está corriendo
            $pidFile = $projectDir . '/.pid';
            if (file_exists($pidFile)) {
                $pid = (int) file_get_contents($pidFile);
                if ($pid > 0) { exec("kill {$pid} 2>/dev/null"); }
            }
            exec(sprintf('rm -rf %s 2>&1', escapeshellarg($projectDir)), $out, $code);
            $t = $code === 0 ? 'success' : 'danger';
            $m = $code === 0 ? '🗑 Proyecto eliminado' : 'Error: ' . implode(' | ', $out);
        } else {
            $t = 'danger'; $m = 'No se puede eliminar este directorio';
        }
    } else {
        $t = 'danger'; $m = 'No autorizado';
    }
    header("Location: /?flash={$t}&msg=" . urlencode($m));
    exit;
}
// ─── AJAX: estado de servicios (solo server.view) ───────────────────────────
if (isset($_GET['service_status'])) {
    header('Content-Type: application/json');
    if (!$authenticated || !$authContext->can('server.view', $authUser)) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $detector = ServiceContainer::get(\Dashboard\Infrastructure\System\ServiceDetector::class);
    echo json_encode($detector->all());
    exit;
}
// ─── AJAX: Apache error log tail (solo admin) ──────────────────────────────
if (isset($_GET['tail_log'])) {
    header('Content-Type: application/json');
    if (!$authenticated || !$isAdmin) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // 1. Ruta personalizada desde .env (máxima prioridad)
    $envLog = $_ENV['APACHE_ERROR_LOG'] ?? '';
    if ($envLog !== '' && file_exists($envLog) && is_readable($envLog)) {
        $logFile = $envLog;
    }

    // 2. Auto-detección si no hay ruta personalizada
    if (!$logFile) {
        $candidates = [
            '/var/log/apache2/error.log',     // Debian/Ubuntu
            '/var/log/httpd/error_log',       // RHEL/Fedora/CentOS
            '/var/log/apache2/error_log',     // Alternativo
            '/var/log/httpd/error.log',       // Alternativo
        ];

        // Detectar HTTPD_ROOT desde apache2ctl/httpd -V
        $httpdBin = null;
        foreach (['apache2ctl', 'httpd'] as $bin) {
            $path = trim(shell_exec("command -v $bin 2>/dev/null") ?? '');
            if ($path) { $httpdBin = $bin; break; }
        }
        if ($httpdBin) {
            $version = shell_exec("$httpdBin -V 2>/dev/null") ?? '';
            if (preg_match('/HTTPD_ROOT="(.+?)"/', $version, $m)) {
                $candidates[] = $m[1] . '/logs/error_log';
                $candidates[] = $m[1] . '/logs/error.log';
            }
            if (preg_match('/DEFAULT_ERRORLOG="(.+?)"/', $version, $m)) {
                $candidates[] = $m[1];
            }
        }

        // Parsear configuración de Apache
        $confCandidates = [
            '/etc/apache2/apache2.conf',
            '/etc/httpd/conf/httpd.conf',
            '/etc/apache2/httpd.conf',
        ];
        foreach ($confCandidates as $conf) {
            if (file_exists($conf) && is_readable($conf)) {
                $content = file_get_contents($conf);
                if (preg_match('/^\s*ErrorLog\s+(.+)$/m', $content, $m)) {
                    $candidates[] = trim($m[1]);
                }
            }
        }

        // Escanear VirtualHosts
        $vhostDirs = ['/etc/apache2/sites-enabled', '/etc/httpd/conf.d'];
        foreach ($vhostDirs as $dir) {
            if (!is_dir($dir) || !is_readable($dir)) continue;
            foreach (glob("$dir/*.conf") as $vhost) {
                if (!is_readable($vhost)) continue;
                $content = file_get_contents($vhost);
                if (preg_match('/^\s*ErrorLog\s+(.+)$/m', $content, $m)) {
                    $candidates[] = trim($m[1]);
                }
            }
        }

        foreach ($candidates as $c) {
            if (file_exists($c) && is_readable($c)) {
                $logFile = $c;
                break;
            }
        }
    }

    if (!$logFile) {
        $tried = $envLog ?: implode(', ', array_slice($candidates ?? [], 0, 4));
        echo json_encode([
            'lines' => [
                '⚠️ No se encontró un archivo de log de Apache legible.',
                'Ruta buscada: ' . $tried,
                '',
                'Soluciones:',
                '  1. Definí APACHE_ERROR_LOG en .env con la ruta correcta',
                '  2. O asegurate de que www-data pueda leer el archivo:',
                '     sudo usermod -aG adm www-data',
                '     sudo systemctl restart apache2',
            ],
        ]);
        exit;
    }

    $lines = 100;
    $content = [];
    $cmd = sprintf('tail -n %d %s 2>&1', $lines, escapeshellarg($logFile));
    exec($cmd, $content);
    $content = array_map(function ($l) {
        return mb_convert_encoding($l, 'UTF-8', 'UTF-8');
    }, $content);

    echo json_encode(['lines' => $content, 'file' => $logFile]);
    exit;
}
// ─── Editor de .env (solo admin autenticado) ─────────────────────────────────
if (isset($_GET['edit_env'])) {
    if (!$authenticated || !$isAdmin) {
        header('Location: /');
        exit;
    }
    $envPath = __DIR__ . '/.env';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $content = $_POST['env_content'] ?? '';
        // Backup antes de sobrescribir
        $backup = $envPath . '.bak.' . date('Ymd-His');
        if (file_exists($envPath)) {
            copy($envPath, $backup);
        }
        file_put_contents($envPath, $content);
        $msg = urlencode('✅ .env guardado. Backup: ' . basename($backup));
        header("Location: /?edit_env=1&flash=success&msg={$msg}");
        exit;
    }

    $envContent = file_exists($envPath) ? file_get_contents($envPath) : '# .env no encontrado';
    require __DIR__ . '/dashboard-logic/views/env-editor.php';
    exit;
}
// ─── AJAX: check if database exists (solo admin autenticado) ────────────────
if (isset($_GET['check_db']) && $_GET['check_db'] !== '') {
    header('Content-Type: application/json');
    if (!$authenticated || !$isAdmin) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $dbName = trim($_GET['check_db']);
    if (!preg_match('/^[a-zA-Z_]\w*$/', $dbName)) {
        echo json_encode(['exists' => false, 'error' => 'Invalid name format']);
        exit;
    }
    $creator = ServiceContainer::get(\Dashboard\Infrastructure\Filesystem\ProjectCreator::class);
    echo json_encode(['exists' => $creator->databaseExists($dbName)]);
    exit;
}
// ─── Login via AuthController ──────────────────────────────────────────────
$isLoginAttempt = ($_POST['email'] ?? '') !== '' && ($_POST['password'] ?? '') !== '';

if (!$authenticated && $isLoginAttempt) {
    $loginEmail = trim($_POST['email'] ?? '');
    if (!$rate_limited) {
        $authController = ServiceContainer::get(AuthController::class);
        $result = $authController->login();
        $logger = ServiceContainer::get(\Dashboard\Infrastructure\Auth\AuthLogger::class);
        if ($result['success']) {
            $logger->log($loginEmail, 'login_success');
            header('Location: ' . $redirect_target);
            exit;
        }
        $logger->log($loginEmail, 'login_failed');
        $error = $result['error'];
    } else {
        // Rate limited — still log the attempt
        $logger = ServiceContainer::get(\Dashboard\Infrastructure\Auth\AuthLogger::class);
        $logger->log($loginEmail, 'login_failed');
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
        .new-project-card {
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
        }
        .new-project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,.4) !important;
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
<script src="./assets/js/create-project-ux.js"></script>
</body>
</html>
