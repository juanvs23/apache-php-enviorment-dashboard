<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Level;

use Dashboard\Application\Repository\LevelRepositoryInterface;
use Dashboard\Application\Repository\PermissionRepositoryInterface;
use Dashboard\Application\UseCase\Level\CreateLevelUseCase;
use Dashboard\Domain\Entity\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreateLevelUseCase::class)]
final class CreateLevelUseCaseTest extends TestCase
{
    private LevelRepositoryInterface $levelRepo;
    private PermissionRepositoryInterface $permRepo;
    private CreateLevelUseCase $useCase;

    protected function setUp(): void
    {
        $this->levelRepo = $this->createMock(LevelRepositoryInterface::class);
        $this->permRepo  = $this->createMock(PermissionRepositoryInterface::class);
        $this->useCase   = new CreateLevelUseCase($this->levelRepo, $this->permRepo);
    }

    public function test_create_client_level_without_permissions(): void
    {
        $this->levelRepo->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(Level::class));

        $level = $this->useCase->execute('lvl-new', 'Editor', 1);

        self::assertSame('lvl-new', $level->levelId());
        self::assertSame('Editor', $level->levelName());
        self::assertFalse($level->isAdmin());
    }

    public function test_create_admin_level(): void
    {
        $this->levelRepo->expects(self::once())
            ->method('save');

        $level = $this->useCase->execute('lvl-admin', 'admin', 0);

        self::assertTrue($level->isAdmin());
    }

    public function test_create_with_permissions_syncs_them(): void
    {
        $this->levelRepo->expects(self::once())
            ->method('save');

        $this->permRepo->expects(self::once())
            ->method('syncLevelPermissions')
            ->with('lvl-1', [1, 2, 3]);

        $this->useCase->execute('lvl-1', 'Moderator', 1, [1, 2, 3]);
    }

    public function test_create_without_permissions_does_not_sync(): void
    {
        $this->levelRepo->expects(self::once())
            ->method('save');

        $this->permRepo->expects(self::never())
            ->method('syncLevelPermissions');

        $this->useCase->execute('lvl-1', 'Viewer', 1);
    }

    public function test_create_with_empty_name_throws_exception(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Level name is required');

        $this->useCase->execute('lvl-1', '', 1);
    }

    public function test_create_with_invalid_type_throws_exception(): void
    {
        $this->expectException(\DomainException::class);

        $this->useCase->execute('lvl-1', 'Invalid', 99);
    }
}
