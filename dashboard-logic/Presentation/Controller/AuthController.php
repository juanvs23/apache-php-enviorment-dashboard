<?php

declare(strict_types=1);

namespace Dashboard\Presentation\Controller;

use Dashboard\Application\UseCase\Auth\LoginUseCase;
use Dashboard\Infrastructure\Session\SessionManager;

/**
 * Controlador de autenticación.
 *
 * Maneja el login (POST con email+password) y logout del dashboard.
 * La autenticación se mantiene via cookie con UUID, no sesión PHP.
 *
 * Rutas:
 *   POST (email + password) → login()
 *   GET ?logout=1           → logout()
 */
final class AuthController
{
    /**
     * @param LoginUseCase   $loginUseCase   Caso de uso de login
     * @param SessionManager $sessionManager Gestor de sesión para rate limiting
     */
    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly SessionManager $sessionManager,
    ) {}

    /**
     * Procesa el formulario de login.
     *
     * Valida credenciales contra la base de datos via LoginUseCase.
     * Si son correctas, setea la cookie `project_user` con el UUID
     * del usuario en base64 y reinicia el rate limiter.
     *
     * NOTA: el rate limiting se verifica ANTES de llamar a este método.
     *
     * @return array{success: bool, error?: string} Resultado del login
     */
    public function login(): array
    {
        $email    = \trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->sessionManager->incrementAttempts();
            return ['success' => false, 'error' => 'Email y contraseña requeridos'];
        }

        try {
            $user = $this->loginUseCase->execute($email, $password);
        } catch (\DomainException $e) {
            $this->sessionManager->incrementAttempts();
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            $this->sessionManager->incrementAttempts();
            return ['success' => false, 'error' => 'Error de conexión a la base de datos'];
        }

        // Login exitoso — guardar UUID en cookie (base64)
        \setcookie('project_user', \base64_encode($user->userId()), \time() + \COOKIE_EXPIRY, \COOKIE_PATH);
        $this->sessionManager->resetAttempts();

        return ['success' => true];
    }

    /**
     * Cierra la sesión del usuario.
     *
     * Invalida la cookie `project_user` seteándola con fecha expirada.
     *
     * @param string $scriptName Nombre del script para redirect
     */
    public function logout(string $scriptName): void
    {
        \setcookie('project_user', '', \time() - 3600, \COOKIE_PATH);
        \header('Location: ' . $scriptName);
    }
}
