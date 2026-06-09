<?php
/**
 * Rate limiter para login.
 *
 * Responsabilidad única: controlar que no se excedan los intentos
 * de login en una ventana de tiempo.
 */

function check_rate_limit(): bool {
    $key = 'login_attempts';

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $attempts = &$_SESSION[$key];

    if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
        if (time() - $attempts['first_attempt'] < RATE_LIMIT_WINDOW) {
            return true; // Bloqueado
        }
        // Ventana expirada — reiniciar
        $attempts = ['count' => 0, 'first_attempt' => time()];
    }

    return false;
}

function increment_attempts(): void {
    $_SESSION['login_attempts']['count']++;
}

function reset_attempts(): void {
    $_SESSION['login_attempts'] = [
        'count'         => 0,
        'first_attempt' => time(),
    ];
}
