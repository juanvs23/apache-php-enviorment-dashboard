<?php
/**
 * Autenticación del dashboard.
 *
 * Responsabilidad única: verificar credenciales, manejar cookies de sesión,
 * login y logout.
 */

function get_auth_key(): string {
    return $_ENV['DASHBOARD_KEY'] ?? '$z]7hB92d1pT';
}

function get_auth_clave(): string {
    return $_ENV['DASHBOARD_CLAVE'] ?? 'Sinal14.';
}

function check_auth(): bool {
    $cookie = $_COOKIE['project_user'] ?? '';
    if ($cookie === '') {
        return false;
    }

    return desencriptar(get_auth_clave(), $cookie) === get_auth_key();
}

function is_login_attempt(): bool {
    return ($_POST['password'] ?? '') !== '';
}

function attempt_login(): array {
    $key             = get_auth_key();
    $clave           = get_auth_clave();
    $password        = $_POST['password'] ?? '';
    $password_encript = encriptar($clave, $key);

    if (desencriptar($password, $password_encript) === $key) {
        setcookie('project_user', $password_encript, time() + COOKIE_EXPIRY, COOKIE_PATH);
        reset_attempts();
        return ['success' => true];
    }

    increment_attempts();
    return ['success' => false, 'error' => 'La contraseña es incorrecta'];
}

function do_logout(): void {
    setcookie('project_user', '', time() - 3600, COOKIE_PATH);
}

function refresh_auth_cookie(): void {
    $key             = get_auth_key();
    $clave           = get_auth_clave();
    $password_encript = encriptar($clave, $key);
    setcookie('project_user', $password_encript, time() + COOKIE_EXPIRY, COOKIE_PATH);
}

function get_redirect_param(): string {
    return $_POST['redirect'] ?? $_GET['redirect'] ?? '';
}

function get_redirect_target(string $script_name): string {
    $param = get_redirect_param();
    return ($param !== '' && str_starts_with($param, '/')) ? $param : $script_name;
}
