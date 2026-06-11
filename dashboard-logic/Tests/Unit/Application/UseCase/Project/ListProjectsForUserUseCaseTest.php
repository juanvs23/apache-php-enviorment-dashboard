<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\Project;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Application\UseCase\Project\ListProjectsForUserUseCase;
use Dashboard\Domain\Entity\Project;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ListProjectsForUserUseCase::class)]
final class ListProjectsForUserUseCaseTest extends TestCase
{
    private ProjectRepositoryInterface $projectRepo;
    private ListProjectsForUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->projectRepo = $this->createMock(ProjectRepositoryInterface::class);
        $this->useCase     = new ListProjectsForUserUseCase($this->projectRepo);
    }

    public function test_execute_returns_projects_for_user(): void
    {
        $projects = [
            Project::create('p1', 'Project A'),
            Project::create('p2', 'Project B'),
        ];

        $this->projectRepo->method('findByUser')
            ->with('user-123')
            ->willReturn($projects);

        $result = $this->useCase->execute('user-123');

        self::assertCount(2, $result);
        self::assertSame('Project A', $result[0]->projectName());
    }

    public function test_all_returns_all_projects(): void
    {
        $projects = [
            Project::create('p1', 'Alpha'),
            Project::create('p2', 'Beta'),
            Project::create('p3', 'Gamma'),
        ];

        $this->projectRepo->method('findAll')
            ->willReturn($projects);

        $result = $this->useCase->all();

        self::assertCount(3, $result);
    }

    public function test_execute_returns_empty_array_when_no_projects(): void
    {
        $this->projectRepo->method('findByUser')
            ->willReturn([]);

        $result = $this->useCase->execute('user-none');

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function test_all_returns_empty_array_when_no_projects(): void
    {
        $this->projectRepo->method('findAll')
            ->willReturn([]);

        $result = $this->useCase->all();

        self::assertEmpty($result);
    }
}
