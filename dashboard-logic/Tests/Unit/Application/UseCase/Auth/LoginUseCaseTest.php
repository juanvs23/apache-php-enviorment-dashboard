<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Auth;

use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Application\UseCase\Auth\LoginUseCase;
use Dashboard\Domain\Entity\User;
use Dashboard\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoginUseCase::class)]
final class LoginUseCaseTest extends TestCase
{
    private UserRepositoryInterface $userRepo;
    private LoginUseCase $useCase;
    private User $user;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->useCase  = new LoginUseCase($this->userRepo);

        $this->user = User::create(
            'uuid-1',
            new Email('user@example.com'),
            'Test',
            'correctPassword',
            'level-1',
        );
    }

    public function test_execute_with_valid_credentials_returns_user(): void
    {
        $this->userRepo->method('findByEmail')
            ->with('user@example.com')
            ->willReturn($this->user);

        $result = $this->useCase->execute('user@example.com', 'correctPassword');

        self::assertSame($this->user, $result);
    }

    public function test_execute_with_wrong_password_throws_exception(): void
    {
        $this->userRepo->method('findByEmail')
            ->willReturn($this->user);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid email or password');

        $this->useCase->execute('user@example.com', 'wrongPassword');
    }

    public function test_execute_with_non_existent_email_throws_exception(): void
    {
        $this->userRepo->method('findByEmail')
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid email or password');

        $this->useCase->execute('nonexistent@example.com', 'anyPassword');
    }

    public function test_execute_with_empty_email_throws_exception(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email and password are required');

        $this->useCase->execute('', 'password');
    }

    public function test_execute_with_empty_password_throws_exception(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email and password are required');

        $this->useCase->execute('user@example.com', '');
    }

    public function test_execute_same_error_message_for_non_existent_and_wrong_password(): void
    {
        // Verificación de seguridad: mismo mensaje para email no existente y contraseña incorrecta
        $this->userRepo->method('findByEmail')
            ->willReturn(null);

        try {
            $this->useCase->execute('a@b.com', 'x');
        } catch (\DomainException $e) {
            $nonExistentMsg = $e->getMessage();
        }

        $existingUser = User::create('uuid-2', new Email('exists@example.com'), null, 'realPwd', 'lvl-1');
        $this->userRepo->method('findByEmail')
            ->with('exists@example.com')
            ->willReturn($existingUser);

        try {
            $this->useCase->execute('exists@example.com', 'wrongPwd');
        } catch (\DomainException $e) {
            $wrongPwdMsg = $e->getMessage();
        }

        self::assertSame($nonExistentMsg, $wrongPwdMsg);
    }
}
