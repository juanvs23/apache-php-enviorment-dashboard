<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Project;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Application\UseCase\Project\SaveProjectUseCase;
use Dashboard\Domain\Entity\Project;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SaveProjectUseCase::class)]
final class SaveProjectUseCaseTest extends TestCase
{
    private ProjectRepositoryInterface $projectRepo;
    private SaveProjectUseCase $useCase;

    protected function setUp(): void
    {
        $this->projectRepo = $this->createMock(ProjectRepositoryInterface::class);
        $this->useCase     = new SaveProjectUseCase($this->projectRepo);
    }

    public function test_create_project_successfully(): void
    {
        $this->projectRepo->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(Project::class));

        $project = $this->useCase->create('uuid-new', 'New Project');

        self::assertSame('uuid-new', $project->projectId());
        self::assertSame('New Project', $project->projectName());
        self::assertNull($project->userOwnId());
        self::assertFalse($project->aceptLogin());
    }

    public function test_create_with_empty_name_throws_exception(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Project name is required');

        $this->useCase->create('uuid-1', '');
    }

    public function test_update_project_successfully(): void
    {
        $project = Project::create('uuid-1', 'Old Name');

        $this->projectRepo->method('findById')
            ->with('uuid-1')
            ->willReturn($project);

        $this->projectRepo->expects(self::once())
            ->method('save')
            ->with($project);

        $updated = $this->useCase->update('uuid-1', 'New Name', 'user-42', true);

        self::assertSame('New Name', $updated->projectName());
        self::assertSame('user-42', $updated->userOwnId());
        self::assertFalse($updated->aceptLogin());
    }

    public function test_update_with_null_user_unassigns(): void
    {
        $project = new Project('uuid-1', 'Test', 'user-1');

        $this->projectRepo->method('findById')
            ->willReturn($project);

        $updated = $this->useCase->update('uuid-1', 'Test', null, false);

        self::assertNull($updated->userOwnId());
        self::assertFalse($updated->aceptLogin());
    }

    public function test_update_nonexistent_project_throws_exception(): void
    {
        $this->projectRepo->method('findById')
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Project not found');

        $this->useCase->update('uuid-none', 'Name', null, false);
    }
}
