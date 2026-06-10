<?php
/**
 * Autenticación del dashboard con MySQL.
 *
 * Responsabilidad única: verificar credenciales contra la tabla USERS,
 * mantener cookie con UUID, lookup en cada request.
 */

use Dashboard\Database\Connection;

function get_auth_user(): ?array {
    $cookie = $_COOKIE['project_user'] ?? '';
    if ($cookie === '') {
        return null;
    }

    $userID = base64_decode($cookie, true);
    if ($userID === false || $userID === '') {
        return null;
    }

    try {
        $pdo  = Connection::get();
        $stmt = $pdo->prepare('
            SELECT u.userID, u.email, u.name, u.level, l.level_name, l.level_type
            FROM USERS u
            JOIN levels l ON l.levelsID = u.level
            WHERE u.userID = :userID
            LIMIT 1
        ');
        $stmt->execute([':userID' => $userID]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        return null;
    }

    if (!$user) {
        return null;
    }

    return [
        'userID'     => $user['userID'],
        'email'      => $user['email'],
        'name'       => $user['name'],
        'level'      => $user['level'],
        'level_name' => $user['level_name'],
        'level_type' => (int) $user['level_type'],
    ];
}

function check_auth(): bool {
    return get_auth_user() !== null;
}

function is_login_attempt(): bool {
    return ($_POST['email'] ?? '') !== '' && ($_POST['password'] ?? '') !== '';
}

function attempt_login(): array {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        increment_attempts();
        return ['success' => false, 'error' => 'Email y contraseña requeridos'];
    }

    try {
        $pdo  = Connection::get();
        $stmt = $pdo->prepare('
            SELECT u.userID, u.email, u.name, u.pass, u.level, l.level_name, l.level_type
            FROM USERS u
            JOIN levels l ON l.levelsID = u.level
            WHERE u.email = :email
            LIMIT 1
        ');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        increment_attempts();
        return ['success' => false, 'error' => 'Error de conexión a la base de datos'];
    }

    if (!$user || !password_verify($password, $user['pass'])) {
        increment_attempts();
        return ['success' => false, 'error' => 'Email o contraseña incorrectos'];
    }

    // Login exitoso — guardar UUID en cookie (base64)
    setcookie('project_user', base64_encode($user['userID']), time() + COOKIE_EXPIRY, COOKIE_PATH);

    reset_attempts();
    return ['success' => true];
}

function do_logout(): void {
    setcookie('project_user', '', time() - 3600, COOKIE_PATH);
}

function refresh_auth_cookie(): void {
    $user = get_auth_user();
    if ($user) {
        setcookie('project_user', base64_encode($user['userID']), time() + COOKIE_EXPIRY, COOKIE_PATH);
    }
}

/**
 * Verifica si el usuario tiene un permiso específico.
 * Admin (level_type=0) siempre tiene todos los permisos.
 * Los permisos se cachean en una variable estática para no consultar la DB en cada llamada.
 */
function can(string $perm_key, ?array $user = null): bool
{
    static $cache = [];

    $user ??= get_auth_user();
    if (!$user) return false;

    // Admin tiene todos los permisos
    if ($user['level_type'] === 0) return true;

    $uid = $user['userID'];
    if (!isset($cache[$uid])) {
        try {
            $pdo = Connection::get();
            $stmt = $pdo->prepare('
                SELECT p.perm_key
                FROM permissions p
                JOIN level_permissions lp ON lp.perm_id = p.id
                JOIN levels l ON l.levelsID = lp.levelID
                WHERE l.levelsID = (SELECT level FROM USERS WHERE userID = :uid)
            ');
            $stmt->execute([':uid' => $uid]);
            $cache[$uid] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable) {
            $cache[$uid] = [];
        }
    }

    return in_array($perm_key, $cache[$uid] ?? []);
}

function get_redirect_param(): string {
    return $_POST['redirect'] ?? $_GET['redirect'] ?? '';
}

function get_redirect_target(string $script_name): string {
    $param = get_redirect_param();
    return ($param !== '' && str_starts_with($param, '/')) ? $param : $script_name;
}
