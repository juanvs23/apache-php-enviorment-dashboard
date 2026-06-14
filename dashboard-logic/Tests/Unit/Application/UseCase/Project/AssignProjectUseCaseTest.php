<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Project;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Application\UseCase\Project\AssignProjectUseCase;
use Dashboard\Domain\Entity\Project;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssignProjectUseCase::class)]
final class AssignProjectUseCaseTest extends TestCase
{
    private ProjectRepositoryInterface $projectRepo;
    private AssignProjectUseCase $useCase;

    protected function setUp(): void
    {
        $this->projectRepo = $this->createMock(ProjectRepositoryInterface::class);
        $this->useCase     = new AssignProjectUseCase($this->projectRepo);
    }

    public function test_assign_project_to_user(): void
    {
        $project = Project::create('uuid-1', 'Test');

        $this->projectRepo->method('findById')
            ->with('uuid-1')
            ->willReturn($project);

        $this->projectRepo->expects(self::once())
            ->method('save')
            ->with($project);

        $result = $this->useCase->assign('uuid-1', 'user-42', true);

        self::assertSame('user-42', $result->userOwnId());
        self::assertTrue($result->aceptLogin());
    }

    public function test_assign_with_login_disabled(): void
    {
        $project = Project::create('uuid-1', 'Test');

        $this->projectRepo->method('findById')
            ->willReturn($project);

        $result = $this->useCase->assign('uuid-1', 'user-42', false);

        self::assertSame('user-42', $result->userOwnId());
        self::assertFalse($result->aceptLogin());
    }

    public function test_assign_nonexistent_project_throws_exception(): void
    {
        $this->projectRepo->method('findById')
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Project not found');

        $this->useCase->assign('uuid-none', 'user-1', true);
    }

    public function test_unassign_project(): void
    {
        $project = new Project('uuid-1', 'Test', 'user-42');

        $this->projectRepo->method('findById')
            ->with('uuid-1')
            ->willReturn($project);

        $this->projectRepo->expects(self::once())
            ->method('save')
            ->with($project);

        $result = $this->useCase->unassign('uuid-1');

        self::assertNull($result->userOwnId());
        self::assertFalse($result->aceptLogin());
    }

    public function test_unassign_nonexistent_project_throws_exception(): void
    {
        $this->projectRepo->method('findById')
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Project not found');

        $this->useCase->unassign('uuid-none');
    }
}
