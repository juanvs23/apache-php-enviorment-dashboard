<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Permission;

use Dashboard\Application\Repository\PermissionRepositoryInterface;
use Dashboard\Application\UseCase\Permission\CheckPermissionUseCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CheckPermissionUseCase::class)]
final class CheckPermissionUseCaseTest extends TestCase
{
    private PermissionRepositoryInterface $permRepo;
    private CheckPermissionUseCase $useCase;

    protected function setUp(): void
    {
        $this->permRepo = $this->createMock(PermissionRepositoryInterface::class);
        $this->useCase  = new CheckPermissionUseCase($this->permRepo);
    }

    public function test_user_has_permission_returns_true(): void
    {
        $this->permRepo->method('userHasPermission')
            ->with('user-1', 'users.manage')
            ->willReturn(true);

        $result = $this->useCase->execute('user-1', 'users.manage');

        self::assertTrue($result);
    }

    public function test_user_does_not_have_permission_returns_false(): void
    {
        $this->permRepo->method('userHasPermission')
            ->with('user-1', 'server.view')
            ->willReturn(false);

        $result = $this->useCase->execute('user-1', 'server.view');

        self::assertFalse($result);
    }

    public function test_delegates_to_repository(): void
    {
        $this->permRepo->expects(self::once())
            ->method('userHasPermission')
            ->with('user-42', 'badge.admin')
            ->willReturn(true);

        $this->useCase->execute('user-42', 'badge.admin');
    }
}
