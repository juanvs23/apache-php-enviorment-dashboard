<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use Dashboard\Application\UseCase\Auth\LoginUseCase;
use Dashboard\Domain\Entity\User;
use Dashboard\Domain\ValueObject\Email;
use Dashboard\Infrastructure\Session\SessionManager;
use Dashboard\Presentation\Controller\AuthController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthController::class)]
final class AuthControllerTest extends TestCase
{
    private LoginUseCase $loginUseCase;
    private SessionManager $sessionManager;
    private AuthController $controller;

    protected function setUp(): void
    {
        $this->loginUseCase   = $this->createMock(LoginUseCase::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->controller     = new AuthController($this->loginUseCase, $this->sessionManager);

        // Constants normally defined by bootstrap.php
        if (!defined('COOKIE_EXPIRY')) {
            define('COOKIE_EXPIRY', 86400 * 7);
        }
        if (!defined('COOKIE_PATH')) {
            define('COOKIE_PATH', '/');
        }

        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
    }

    // ──────────────────────────────────────────────────────────────
    // Login — valid credentials
    // ──────────────────────────────────────────────────────────────

    public function test_login_with_valid_credentials_resets_attempts(): void
    {
        $user = User::create('uuid-ok', new Email('admin@admin.com'), 'Admin', 'pass', 'lvl-1');

        $this->loginUseCase->method('execute')
            ->willReturn($user);

        $this->sessionManager->expects(self::once())
            ->method('resetAttempts');

        $this->sessionManager->expects(self::never())
            ->method('incrementAttempts');

        $_POST = ['email' => 'admin@admin.com', 'password' => 'Admin123'];

        $result = $this->controller->login();

        self::assertTrue($result['success']);
        self::assertArrayNotHasKey('error', $result);
    }

    public function test_login_sets_authentication_cookie(): void
    {
        if (!function_exists('xdebug_get_headers')) {
            self::markTestSkipped('xdebug required for header assertions');
        }

        $user = User::create('uuid-cookie', new Email('admin@admin.com'), 'Admin', 'pass', 'lvl-1');

        $this->loginUseCase->method('execute')
            ->willReturn($user);

        $_POST = ['email' => 'admin@admin.com', 'password' => 'Admin123'];

        $this->controller->login();

        $headers = xdebug_get_headers();
        $cookieHeader = current(array_filter($headers, fn(string $h) => str_starts_with($h, 'Set-Cookie: project_user=')));

        self::assertNotFalse($cookieHeader, 'project_user cookie must be set');
        self::assertStringContainsString('project_user=', $cookieHeader);
    }

    // ──────────────────────────────────────────────────────────────
    // Login — empty fields
    // ──────────────────────────────────────────────────────────────

    public function test_login_with_empty_email_increments_attempts(): void
    {
        $this->sessionManager->expects(self::once())
            ->method('incrementAttempts');

        $this->sessionManager->expects(self::never())
            ->method('resetAttempts');

        $this->loginUseCase->expects(self::never())
            ->method('execute');

        $_POST = ['email' => '', 'password' => 'Admin123'];

        $result = $this->controller->login();

        self::assertFalse($result['success']);
        self::assertNotEmpty($result['error']);
    }

    public function test_login_with_empty_password_increments_attempts(): void
    {
        $this->sessionManager->expects(self::once())
            ->method('incrementAttempts');

        $this->loginUseCase->expects(self::never())
            ->method('execute');

        $_POST = ['email' => 'admin@admin.com', 'password' => ''];

        $result = $this->controller->login();

        self::assertFalse($result['success']);
    }

    // ──────────────────────────────────────────────────────────────
    // Login — invalid credentials
    // ──────────────────────────────────────────────────────────────

    public function test_login_with_invalid_credentials_increments_attempts(): void
    {
        $this->loginUseCase->method('execute')
            ->willThrowException(new \DomainException('Credenciales inválidas'));

        $this->sessionManager->expects(self::once())
            ->method('incrementAttempts');

        $this->sessionManager->expects(self::never())
            ->method('resetAttempts');

        $_POST = ['email' => 'wrong@test.com', 'password' => 'wrong'];

        $result = $this->controller->login();

        self::assertFalse($result['success']);
        self::assertStringContainsString('Credenciales', $result['error']);
    }

    public function test_login_with_db_failure_increments_attempts(): void
    {
        $this->loginUseCase->method('execute')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $this->sessionManager->expects(self::once())
            ->method('incrementAttempts');

        $_POST = ['email' => 'admin@admin.com', 'password' => 'Admin123'];

        $result = $this->controller->login();

        self::assertFalse($result['success']);
        self::assertStringContainsString('Error de conexión', $result['error']);
    }

    // ──────────────────────────────────────────────────────────────
    // Logout
    // ──────────────────────────────────────────────────────────────

    public function test_logout_expires_cookie_and_redirects(): void
    {
        if (!function_exists('xdebug_get_headers')) {
            self::markTestSkipped('xdebug required for header assertions');
        }

        try {
            $this->controller->logout('/index.php');
        } catch (\Throwable) {
            // header() may trigger "headers already sent" — that's expected
        }

        $headers = xdebug_get_headers();
        // Find ALL Set-Cookie: project_user= headers (xdebug accumulates across tests)
        $cookies = array_values(array_filter(
            $headers,
            fn(string $h) => str_starts_with($h, 'Set-Cookie: project_user='),
        ));
        $allCookieHeaders = implode(' ', $cookies);

        self::assertStringContainsString('deleted', $allCookieHeaders, 'Logout cookie must set "deleted" value');
    }
}
