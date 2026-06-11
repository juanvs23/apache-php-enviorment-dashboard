<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use Dashboard\Domain\Entity\Project;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios de la entidad Project.
 *
 * Verifica creación, asignación/desasignación de usuarios,
 * toggle de login, y renombre.
 */
#[CoversClass(Project::class)]
final class ProjectTest extends TestCase
{
    private const PROJECT_ID = 'proj-1';

    private Project $project;

    protected function setUp(): void
    {
        $this->project = new Project(
            self::PROJECT_ID,
            'Mi Proyecto',
            'user-123',
            true,
        );
    }

    public function test_constructor_sets_all_properties(): void
    {
        self::assertSame(self::PROJECT_ID, $this->project->projectId());
        self::assertSame('Mi Proyecto', $this->project->projectName());
        self::assertSame('user-123', $this->project->userOwnId());
        self::assertTrue($this->project->aceptLogin());
    }

    public function test_create_factory_creates_unassigned_with_login_disabled(): void
    {
        $project = Project::create(self::PROJECT_ID, 'Nuevo');

        self::assertSame(self::PROJECT_ID, $project->projectId());
        self::assertSame('Nuevo', $project->projectName());
        self::assertNull($project->userOwnId());
        self::assertFalse($project->aceptLogin());
    }

    public function test_assignToUser_sets_user(): void
    {
        $project = Project::create('p-1', 'Test');
        $project->assignToUser('user-456');

        self::assertSame('user-456', $project->userOwnId());
    }

    public function test_unassignUser_clears_user(): void
    {
        $this->project->unassignUser();
        self::assertNull($this->project->userOwnId());
    }

    public function test_enableLogin_sets_aceptLogin_true(): void
    {
        $project = new Project('p-1', 'Test', null, false);
        $project->enableLogin();

        self::assertTrue($project->aceptLogin());
    }

    public function test_disableLogin_sets_aceptLogin_false(): void
    {
        $this->project->disableLogin();
        self::assertFalse($this->project->aceptLogin());
    }

    public function test_rename_updates_projectName(): void
    {
        $this->project->rename('Nuevo Nombre');
        self::assertSame('Nuevo Nombre', $this->project->projectName());
    }

    public function test_assignToUser_overwrites_previous_assignment(): void
    {
        $this->project->assignToUser('user-999');
        self::assertSame('user-999', $this->project->userOwnId());
    }
}
