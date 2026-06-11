<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\User;

use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Application\UseCase\User\CreateUserUseCase;
use Dashboard\Domain\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreateUserUseCase::class)]
final class CreateUserUseCaseTest extends TestCase
{
    private UserRepositoryInterface $userRepo;
    private CreateUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->useCase  = new CreateUserUseCase($this->userRepo);
    }

    public function test_create_user_successfully(): void
    {
        $this->userRepo->method('emailExists')
            ->with('new@example.com')
            ->willReturn(false);

        $this->userRepo->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(User::class));

        $user = $this->useCase->execute('uuid-new', 'new@example.com', 'New User', 'securePass', 'level-1');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('uuid-new', $user->userId());
        self::assertSame('new@example.com', $user->email()->value());
    }

    public function test_duplicate_email_throws_exception(): void
    {
        $this->userRepo->method('emailExists')
            ->with('existing@example.com')
            ->willReturn(true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email already exists');

        $this->useCase->execute('uuid-2', 'existing@example.com', 'User', 'pass', 'level-1');
    }

    public function test_empty_password_throws_exception(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Password is required');

        $this->useCase->execute('uuid-3', 'user@example.com', 'User', '', 'level-1');
    }

    public function test_invalid_email_throws_exception(): void
    {
        $this->expectException(\DomainException::class);

        $this->useCase->execute('uuid-4', 'not-an-email', 'User', 'pass', 'level-1');
    }
}
