<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Project;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Application\UseCase\Project\DeleteProjectUseCase;
use Dashboard\Domain\Entity\Project;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeleteProjectUseCase::class)]
final class DeleteProjectUseCaseTest extends TestCase
{
    private ProjectRepositoryInterface $projectRepo;
    private DeleteProjectUseCase $useCase;

    protected function setUp(): void
    {
        $this->projectRepo = $this->createMock(ProjectRepositoryInterface::class);
        $this->useCase     = new DeleteProjectUseCase($this->projectRepo);
    }

    public function test_delete_existing_project(): void
    {
        $project = Project::create('uuid-1', 'Test Project');

        $this->projectRepo->method('findById')
            ->with('uuid-1')
            ->willReturn($project);

        $this->projectRepo->expects(self::once())
            ->method('delete')
            ->with('uuid-1');

        $this->useCase->execute('uuid-1');
        self::assertTrue(true);
    }

    public function test_delete_nonexistent_project_throws_exception(): void
    {
        $this->projectRepo->method('findById')
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Project not found');

        $this->useCase->execute('uuid-none');
    }
}
