<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\Session;

/**
 * Gestor de sesión PHP.
 *
 * Encapsula el acceso a $_SESSION para rate limiting y otros
 * datos de sesión del dashboard. Permite testear y desacoplar
 * la lógica de sesión de la infraestructura PHP global.
 *
 * Rate limiting:
 *   - maxAttempts: intentos máximos permitidos en la ventana de tiempo
 *   - windowSeconds: duración de la ventana en segundos
 *
 * Uso típico:
 *   $session = new SessionManager(5, 900);
 *   if ($session->isRateLimited()) { ... }
 *   $session->incrementAttempts();
 *   $session->resetAttempts();
 */
final class SessionManager
{
    /**
     * Clave en $_SESSION para los intentos de login.
     */
    private const ATTEMPT_KEY = 'login_attempts';

    /**
     * Número máximo de intentos antes de bloquear.
     *
     * @var int
     */
    private int $maxAttempts;

    /**
     * Ventana de tiempo en segundos para el rate limiting.
     *
     * @var int
     */
    private int $windowSeconds;

    /**
     * @param int $maxAttempts   Máximo de intentos permitidos (default: 5)
     * @param int $windowSeconds Ventana de tiempo en segundos (default: 900 = 15 min)
     */
    public function __construct(int $maxAttempts = 5, int $windowSeconds = 900)
    {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->ensureSession();
    }

    /**
     * Verifica si el rate limit está activo (bloqueado).
     *
     * @return bool True si se excedieron los intentos en la ventana actual
     */
    public function isRateLimited(): bool
    {
        if (!isset($_SESSION[self::ATTEMPT_KEY])) {
            return false;
        }

        $attempts = $_SESSION[self::ATTEMPT_KEY];

        if ($attempts['count'] >= $this->maxAttempts) {
            if (time() - $attempts['first_attempt'] < $this->windowSeconds) {
                return true;
            }
            // Ventana expirada — reiniciar automáticamente
            $this->resetAttempts();
        }

        return false;
    }

    /**
     * Incrementa el contador de intentos de login.
     */
    public function incrementAttempts(): void
    {
        $this->ensureAttemptsInitialized();
        $_SESSION[self::ATTEMPT_KEY]['count']++;
    }

    /**
     * Reinicia el contador de intentos (login exitoso).
     */
    public function resetAttempts(): void
    {
        $_SESSION[self::ATTEMPT_KEY] = [
            'count'         => 0,
            'first_attempt' => time(),
        ];
    }

    /**
     * Retorna el número actual de intentos realizados.
     *
     * @return int Intentos actuales
     */
    public function getCurrentAttempts(): int
    {
        $this->ensureAttemptsInitialized();
        return $_SESSION[self::ATTEMPT_KEY]['count'];
    }

    /**
     * Retorna los segundos restantes hasta que se desbloquee el rate limit.
     *
     * @return int Segundos restantes (0 si no está bloqueado)
     */
    public function getSecondsUntilUnlock(): int
    {
        if (!isset($_SESSION[self::ATTEMPT_KEY])) {
            return 0;
        }

        $attempts = $_SESSION[self::ATTEMPT_KEY];
        $elapsed = time() - $attempts['first_attempt'];

        return max(0, $this->windowSeconds - $elapsed);
    }

    /**
     * Asegura que la sesión PHP esté iniciada.
     */
    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Inicializa la estructura de intentos si no existe.
     */
    private function ensureAttemptsInitialized(): void
    {
        if (!isset($_SESSION[self::ATTEMPT_KEY])) {
            $_SESSION[self::ATTEMPT_KEY] = [
                'count'         => 0,
                'first_attempt' => time(),
            ];
        }
    }
}
