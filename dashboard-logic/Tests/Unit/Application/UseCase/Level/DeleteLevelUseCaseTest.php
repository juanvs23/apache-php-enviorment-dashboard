<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Level;

use Dashboard\Application\Repository\LevelRepositoryInterface;
use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Application\UseCase\Level\DeleteLevelUseCase;
use Dashboard\Domain\Entity\Level;
use Dashboard\Domain\ValueObject\LevelType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeleteLevelUseCase::class)]
final class DeleteLevelUseCaseTest extends TestCase
{
    private LevelRepositoryInterface $levelRepo;
    private UserRepositoryInterface $userRepo;
    private DeleteLevelUseCase $useCase;

    protected function setUp(): void
    {
        $this->levelRepo = $this->createMock(LevelRepositoryInterface::class);
        $this->userRepo  = $this->createMock(UserRepositoryInterface::class);
        $this->useCase   = new DeleteLevelUseCase($this->levelRepo, $this->userRepo);
    }

    public function test_delete_client_level_without_users(): void
    {
        $level = new Level('lvl-1', 'Editor', new LevelType(LevelType::CLIENT));

        $this->levelRepo->method('findById')
            ->with('lvl-1')
            ->willReturn($level);

        $this->userRepo->method('findByLevel')
            ->with('lvl-1')
            ->willReturn([]);

        $this->levelRepo->expects(self::once())
            ->method('delete')
            ->with('lvl-1');

        $this->useCase->execute('lvl-1');
        self::assertTrue(true);
    }

    public function test_delete_admin_level_throws_exception(): void
    {
        $level = new Level('lvl-admin', 'admin', new LevelType(LevelType::ADMIN));

        $this->levelRepo->method('findById')
            ->willReturn($level);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The admin level cannot be deleted');

        $this->useCase->execute('lvl-admin');
    }

    public function test_delete_level_with_users_assigned_throws_exception(): void
    {
        $level = new Level('lvl-1', 'Editor', new LevelType(LevelType::CLIENT));

        $this->levelRepo->method('findById')
            ->willReturn($level);

        $this->userRepo->method('findByLevel')
            ->with('lvl-1')
            ->willReturn(['user-1', 'user-2']); // Simula usuarios asignados

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot delete level: 2 user(s) are assigned to it');

        $this->useCase->execute('lvl-1');
    }

    public function test_delete_nonexistent_level_throws_exception(): void
    {
        $this->levelRepo->method('findById')
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Level not found');

        $this->useCase->execute('lvl-none');
    }

    public function test_delete_verifies_users_before_deleting(): void
    {
        $level = new Level('lvl-1', 'Editor', new LevelType(LevelType::CLIENT));

        $this->levelRepo->method('findById')
            ->willReturn($level);

        $this->userRepo->method('findByLevel')
            ->willReturn([]);

        $this->levelRepo->expects(self::once())
            ->method('delete');

        $this->useCase->execute('lvl-1');
    }
}
