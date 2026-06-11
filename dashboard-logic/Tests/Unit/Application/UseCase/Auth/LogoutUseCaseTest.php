<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Auth;

use Dashboard\Application\UseCase\Auth\LogoutUseCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogoutUseCase::class)]
final class LogoutUseCaseTest extends TestCase
{
    public function test_execute_returns_void(): void
    {
        $useCase = new LogoutUseCase();

        // Actualmente es un no-op, solo verifica que no lance excepción
        $useCase->execute();

        self::assertTrue(true);
    }
}
