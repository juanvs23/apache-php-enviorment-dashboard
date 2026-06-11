<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\User;

use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Application\UseCase\User\UpdateUserUseCase;
use Dashboard\Domain\Entity\User;
use Dashboard\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UpdateUserUseCase::class)]
final class UpdateUserUseCaseTest extends TestCase
{
    private UserRepositoryInterface $userRepo;
    private UpdateUserUseCase $useCase;
    private User $user;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->useCase  = new UpdateUserUseCase($this->userRepo);

        $this->user = new User(
            'uuid-exists',
            new Email('old@example.com'),
            'Old Name',
            password_hash('oldPass', PASSWORD_BCRYPT),
            'level-1',
        );
    }

    public function test_update_email_and_name(): void
    {
        $this->userRepo->method('findById')
            ->with('uuid-exists')
            ->willReturn($this->user);

        $this->userRepo->method('emailExists')
            ->with('new@example.com', 'uuid-exists')
            ->willReturn(false);

        $this->userRepo->expects(self::once())
            ->method('save')
            ->with($this->user);

        $updated = $this->useCase->execute('uuid-exists', 'new@example.com', 'New Name');

        self::assertSame('new@example.com', $updated->email()->value());
        self::assertSame('New Name', $updated->name());
        // Password se mantiene porque no se pasó
        self::assertTrue($updated->authenticate('oldPass'));
    }

    public function test_update_with_password_changes_it(): void
    {
        $this->userRepo->method('findById')
            ->willReturn($this->user);

        $this->userRepo->method('emailExists')
            ->willReturn(false);

        $updated = $this->useCase->execute('uuid-exists', 'old@example.com', null, 'newPass123');

        self::assertTrue($updated->authenticate('newPass123'));
        self::assertFalse($updated->authenticate('oldPass'));
    }

    public function test_update_nonexistent_user_throws_exception(): void
    {
        $this->userRepo->method('findById')
            ->with('uuid-not-found')
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User not found');

        $this->useCase->execute('uuid-not-found', 'any@example.com', 'Name');
    }

    public function test_update_with_duplicate_email_throws_exception(): void
    {
        $this->userRepo->method('findById')
            ->willReturn($this->user);

        $this->userRepo->method('emailExists')
            ->with('taken@example.com', 'uuid-exists')
            ->willReturn(true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email is already in use by another user');

        $this->useCase->execute('uuid-exists', 'taken@example.com', 'Name');
    }

    public function test_update_with_invalid_email_throws_exception(): void
    {
        $this->userRepo->method('findById')
            ->willReturn($this->user);

        $this->expectException(\DomainException::class);

        $this->useCase->execute('uuid-exists', 'not-valid', 'Name');
    }

    public function test_update_with_same_email_does_not_trigger_duplicate_check(): void
    {
        $this->userRepo->method('findById')
            ->willReturn($this->user);

        $this->userRepo->method('emailExists')
            ->with('old@example.com', 'uuid-exists')
            ->willReturn(false);

        $this->userRepo->expects(self::once())
            ->method('save');

        $updated = $this->useCase->execute('uuid-exists', 'old@example.com', 'New Name');

        self::assertSame('new name', strtolower($updated->name() ?? ''));
    }

    public function test_update_level_changes_level(): void
    {
        $this->userRepo->method('findById')
            ->willReturn($this->user);

        $this->userRepo->method('emailExists')
            ->willReturn(false);

        $this->userRepo->expects(self::once())
            ->method('save')
            ->with($this->user);

        $updated = $this->useCase->execute('uuid-exists', 'old@example.com', null, '', 'level-new');

        self::assertSame('level-new', $updated->levelId());
    }

    public function test_update_without_level_keeps_original_level(): void
    {
        $this->userRepo->method('findById')
            ->willReturn($this->user);

        $this->userRepo->method('emailExists')
            ->willReturn(false);

        $updated = $this->useCase->execute('uuid-exists', 'old@example.com', null);

        self::assertSame('level-1', $updated->levelId());
    }
}
