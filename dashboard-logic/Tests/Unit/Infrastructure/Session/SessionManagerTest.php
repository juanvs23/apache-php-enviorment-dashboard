<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Session;

use Dashboard\Infrastructure\Session\SessionManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests del SessionManager.
 *
 * Verifica rate limiting: contador de intentos, bloqueo,
 * reinicio automático por ventana de tiempo, y unlock.
 *
 * NOTA: Estos tests modifican $_SESSION directamente.
 * Se ejecutan en CLI donde no hay sesión activa previamente.
 */
#[CoversClass(SessionManager::class)]
final class SessionManagerTest extends TestCase
{
    private string $sessionKey = 'login_attempts';

    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function test_new_session_is_not_rate_limited(): void
    {
        $manager = new SessionManager(5, 900);

        self::assertFalse($manager->isRateLimited());
    }

    public function test_after_max_attempts_is_rate_limited(): void
    {
        $manager = new SessionManager(3, 900); // Max 3 intentos

        self::assertFalse($manager->isRateLimited());

        $manager->incrementAttempts();
        self::assertFalse($manager->isRateLimited());

        $manager->incrementAttempts();
        self::assertFalse($manager->isRateLimited());

        $manager->incrementAttempts(); // Llega a 3
        self::assertTrue($manager->isRateLimited());
    }

    public function test_resetAttempts_clears_rate_limit(): void
    {
        $manager = new SessionManager(2, 900);

        $manager->incrementAttempts();
        $manager->incrementAttempts();
        self::assertTrue($manager->isRateLimited());

        $manager->resetAttempts();
        self::assertFalse($manager->isRateLimited());
    }

    public function test_getCurrentAttempts_returns_count(): void
    {
        $manager = new SessionManager(5, 900);

        self::assertSame(0, $manager->getCurrentAttempts());

        $manager->incrementAttempts();
        self::assertSame(1, $manager->getCurrentAttempts());

        $manager->incrementAttempts();
        self::assertSame(2, $manager->getCurrentAttempts());
    }

    public function test_window_expiry_in_isRateLimited_auto_resets(): void
    {
        $manager = new SessionManager(2, -1);

        // Simular 2 intentos con ventana ya expirada
        $_SESSION[$this->sessionKey] = [
            'count'         => 2,
            'first_attempt' => time() - 100, // 100 segundos atrás, ventana es -1
        ];

        // isRateLimited debería detectar ventana expirada y reiniciar
        $result = $manager->isRateLimited();

        self::assertFalse($result);
        self::assertSame(0, $manager->getCurrentAttempts());
    }

    public function test_getSecondsUntilUnlock_returns_positive(): void
    {
        $manager = new SessionManager(3, 60);

        // Sin intentos
        self::assertSame(0, $manager->getSecondsUntilUnlock());

        // Con intentos pero sin bloqueo
        $manager->incrementAttempts();
        $seconds = $manager->getSecondsUntilUnlock();
        self::assertGreaterThan(0, $seconds);
        self::assertLessThanOrEqual(60, $seconds);
    }

    public function test_incrementAttempts_starts_at_one(): void
    {
        $manager = new SessionManager(5, 900);

        $manager->incrementAttempts();
        self::assertSame(1, $manager->getCurrentAttempts());
    }

    public function test_custom_max_attempts(): void
    {
        $manager = new SessionManager(1, 900); // Solo 1 intento

        self::assertFalse($manager->isRateLimited());
        $manager->incrementAttempts();
        self::assertTrue($manager->isRateLimited());
    }

    public function test_reset_after_rate_limit_allows_new_attempts(): void
    {
        $manager = new SessionManager(2, 900);

        $manager->incrementAttempts();
        $manager->incrementAttempts();
        self::assertTrue($manager->isRateLimited());

        $manager->resetAttempts();
        self::assertFalse($manager->isRateLimited());

        $manager->incrementAttempts();
        self::assertSame(1, $manager->getCurrentAttempts());
        self::assertFalse($manager->isRateLimited());
    }
}
