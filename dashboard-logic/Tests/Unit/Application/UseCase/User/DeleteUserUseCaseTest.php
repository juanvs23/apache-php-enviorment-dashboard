<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\User;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Application\UseCase\User\DeleteUserUseCase;
use Dashboard\Domain\Entity\User;
use Dashboard\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeleteUserUseCase::class)]
final class DeleteUserUseCaseTest extends TestCase
{
    private UserRepositoryInterface $userRepo;
    private DeleteUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->useCase  = new DeleteUserUseCase($this->userRepo);
    }

    public function test_delete_existing_user(): void
    {
        $user = User::create('uuid-1', new Email('user@example.com'), 'User', 'pass', 'lvl-1');

        $this->userRepo->method('findById')
            ->with('uuid-1')
            ->willReturn($user);

        $this->userRepo->expects(self::once())
            ->method('delete')
            ->with('uuid-1');

        $this->useCase->execute('uuid-1');
        // No exception = success
        self::assertTrue(true);
    }

    public function test_delete_nonexistent_user_throws_exception(): void
    {
        $this->userRepo->method('findById')
            ->with('uuid-not-found')
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User not found');

        $this->useCase->execute('uuid-not-found');
    }

    public function test_delete_unlinks_projects_before_user_deletion(): void
    {
        $user = User::create('uuid-1', new Email('user@example.com'), 'User', 'pass', 'lvl-1');

        $this->userRepo->method('findById')
            ->with('uuid-1')
            ->willReturn($user);

        // Project repository debe recibir unassignProjectsByUserId ANTES del delete
        $projectRepo = $this->createMock(ProjectRepositoryInterface::class);
        $projectRepo->expects(self::once())
            ->method('unassignProjectsByUserId')
            ->with('uuid-1');

        $this->userRepo->expects(self::once())
            ->method('delete')
            ->with('uuid-1');

        $useCase = new DeleteUserUseCase($this->userRepo, $projectRepo);
        $useCase->execute('uuid-1');
    }

    public function test_delete_nonexistent_user_does_not_unlink_projects(): void
    {
        $this->userRepo->method('findById')
            ->with('uuid-not-found')
            ->willReturn(null);

        $projectRepo = $this->createMock(ProjectRepositoryInterface::class);
        $projectRepo->expects(self::never())
            ->method('unassignProjectsByUserId');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User not found');

        $useCase = new DeleteUserUseCase($this->userRepo, $projectRepo);
        $useCase->execute('uuid-not-found');
    }
}
