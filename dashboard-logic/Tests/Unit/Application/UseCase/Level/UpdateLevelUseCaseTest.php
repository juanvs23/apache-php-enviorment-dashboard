<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Level;

use Dashboard\Application\Repository\LevelRepositoryInterface;
use Dashboard\Application\Repository\PermissionRepositoryInterface;
use Dashboard\Application\UseCase\Level\UpdateLevelUseCase;
use Dashboard\Domain\Entity\Level;
use Dashboard\Domain\ValueObject\LevelType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UpdateLevelUseCase::class)]
final class UpdateLevelUseCaseTest extends TestCase
{
    private LevelRepositoryInterface $levelRepo;
    private PermissionRepositoryInterface $permRepo;
    private UpdateLevelUseCase $useCase;

    protected function setUp(): void
    {
        $this->levelRepo = $this->createMock(LevelRepositoryInterface::class);
        $this->permRepo  = $this->createMock(PermissionRepositoryInterface::class);
        $this->useCase   = new UpdateLevelUseCase($this->levelRepo, $this->permRepo);
    }

    public function test_update_client_level_name_and_permissions(): void
    {
        $level = new Level('lvl-1', 'Old Name', new LevelType(LevelType::CLIENT));

        $this->levelRepo->method('findById')
            ->with('lvl-1')
            ->willReturn($level);

        $this->levelRepo->expects(self::once())
            ->method('save')
            ->with($level);

        $this->permRepo->expects(self::once())
            ->method('syncLevelPermissions')
            ->with('lvl-1', [2, 4]);

        $updated = $this->useCase->execute('lvl-1', 'New Name', [2, 4]);

        self::assertSame('New Name', $updated->levelName());
    }

    public function test_update_admin_level_throws_exception(): void
    {
        $level = new Level('lvl-admin', 'admin', new LevelType(LevelType::ADMIN));

        $this->levelRepo->method('findById')
            ->willReturn($level);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The admin level cannot be modified');

        $this->useCase->execute('lvl-admin', 'new-name', []);
    }

    public function test_update_nonexistent_level_throws_exception(): void
    {
        $this->levelRepo->method('findById')
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Level not found');

        $this->useCase->execute('lvl-none', 'Name', []);
    }

    public function test_update_with_empty_name_throws_exception(): void
    {
        $level = new Level('lvl-1', 'Existing', new LevelType(LevelType::CLIENT));

        $this->levelRepo->method('findById')
            ->willReturn($level);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Level name is required');

        $this->useCase->execute('lvl-1', '', []);
    }
}
