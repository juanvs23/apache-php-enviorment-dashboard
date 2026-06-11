<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Controller;

use Dashboard\Application\UseCase\Level\CreateLevelUseCase;
use Dashboard\Application\UseCase\Level\DeleteLevelUseCase;
use Dashboard\Application\UseCase\Level\UpdateLevelUseCase;
use Dashboard\Application\UseCase\Permission\CheckPermissionUseCase;
use Dashboard\Application\UseCase\Project\AssignProjectUseCase;
use Dashboard\Application\UseCase\Project\DeleteProjectUseCase;
use Dashboard\Application\UseCase\Project\SaveProjectUseCase;
use Dashboard\Application\UseCase\User\CreateUserUseCase;
use Dashboard\Application\UseCase\User\DeleteUserUseCase;
use Dashboard\Application\UseCase\User\UpdateUserUseCase;
use Dashboard\Domain\Entity\User;
use Dashboard\Domain\ValueObject\Email;
use Dashboard\Infrastructure\Auth\AuthContext;
use Dashboard\Infrastructure\Persistence\LegacyReader;
use Dashboard\Presentation\Controller\AdminController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdminController::class)]
final class AdminControllerTest extends TestCase
{
    private CreateLevelUseCase $createLevel;
    private UpdateLevelUseCase $updateLevel;
    private DeleteLevelUseCase $deleteLevel;
    private CheckPermissionUseCase $checkPermission;
    private CreateUserUseCase $createUser;
    private UpdateUserUseCase $updateUser;
    private DeleteUserUseCase $deleteUser;
    private SaveProjectUseCase $saveProject;
    private DeleteProjectUseCase $deleteProject;
    private AssignProjectUseCase $assignProject;
    private AuthContext $authContext;
    private LegacyReader $legacyReader;
    private AdminController $controller;

    protected function setUp(): void
    {
        $this->createLevel     = $this->createMock(CreateLevelUseCase::class);
        $this->updateLevel     = $this->createMock(UpdateLevelUseCase::class);
        $this->deleteLevel     = $this->createMock(DeleteLevelUseCase::class);
        $this->checkPermission = $this->createMock(CheckPermissionUseCase::class);
        $this->createUser      = $this->createMock(CreateUserUseCase::class);
        $this->updateUser      = $this->createMock(UpdateUserUseCase::class);
        $this->deleteUser      = $this->createMock(DeleteUserUseCase::class);
        $this->saveProject     = $this->createMock(SaveProjectUseCase::class);
        $this->deleteProject   = $this->createMock(DeleteProjectUseCase::class);
        $this->assignProject   = $this->createMock(AssignProjectUseCase::class);
        $this->authContext     = $this->createMock(AuthContext::class);
        $this->legacyReader    = $this->createMock(LegacyReader::class);

        $this->controller = new AdminController(
            $this->createLevel,
            $this->updateLevel,
            $this->deleteLevel,
            $this->checkPermission,
            $this->createUser,
            $this->updateUser,
            $this->deleteUser,
            $this->saveProject,
            $this->deleteProject,
            $this->assignProject,
            $this->authContext,
            $this->legacyReader,
        );

        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
    }

    /**
     * Invoca un método privado del controller via reflection.
     *
     * @return array{success: bool, error?: string, userID?: string}
     */
    private function invokePrivate(string $method): array
    {
        $ref = new \ReflectionMethod($this->controller, $method);
        /** @var array{success: bool, error?: string, userID?: string} */
        return $ref->invoke($this->controller);
    }

    // ══════════════════════════════════════════════════════════════
    // User handlers
    // ══════════════════════════════════════════════════════════════

    public function test_create_user_success(): void
    {
        $user = User::create('uuid-1', new Email('new@test.com'), 'New User', 'pass', 'lvl-2');

        $this->createUser->expects(self::once())
            ->method('execute')
            ->with(
                self::isType('string'),  // userId UUID
                'new@test.com',
                'New User',
                'pass123',
                'lvl-2',
            )
            ->willReturn($user);

        $_POST = [
            'email'    => 'new@test.com',
            'name'     => 'New User',
            'password' => 'pass123',
            'level'    => 'lvl-2',
        ];

        $result = $this->invokePrivate('processCreateUser');

        self::assertTrue($result['success']);
        self::assertArrayHasKey('userID', $result);
    }

    public function test_create_user_missing_level_returns_error(): void
    {
        $this->createUser->expects(self::never())
            ->method('execute');

        $_POST = ['email' => 'new@test.com', 'password' => 'pass', 'level' => ''];

        $result = $this->invokePrivate('processCreateUser');

        self::assertFalse($result['success']);
        self::assertStringContainsString('Nivel requerido', $result['error']);
    }

    public function test_update_user_with_level(): void
    {
        $this->updateUser->expects(self::once())
            ->method('execute')
            ->with('uuid-1', 'user@test.com', 'Updated', 'newpass', 'lvl-3');

        $_POST = [
            'userID'   => 'uuid-1',
            'email'    => 'user@test.com',
            'name'     => 'Updated',
            'password' => 'newpass',
            'level'    => 'lvl-3',
        ];

        $result = $this->invokePrivate('processUpdateUser');

        self::assertTrue($result['success']);
    }

    public function test_update_user_missing_fields_returns_error(): void
    {
        $this->updateUser->expects(self::never())
            ->method('execute');

        $_POST = ['userID' => '', 'email' => '', 'level' => ''];

        $result = $this->invokePrivate('processUpdateUser');

        self::assertFalse($result['success']);
        self::assertStringContainsString('Faltan campos', $result['error']);
    }

    public function test_delete_user_success(): void
    {
        $this->deleteUser->expects(self::once())
            ->method('execute')
            ->with('uuid-del');

        $_POST = ['userID' => 'uuid-del'];

        $result = $this->invokePrivate('processDeleteUser');

        self::assertTrue($result['success']);
    }

    public function test_delete_user_missing_id_returns_error(): void
    {
        $this->deleteUser->expects(self::never())
            ->method('execute');

        $_POST = ['userID' => ''];

        $result = $this->invokePrivate('processDeleteUser');

        self::assertFalse($result['success']);
        self::assertStringContainsString('ID de usuario requerido', $result['error']);
    }

    // ══════════════════════════════════════════════════════════════
    // Project handlers
    // ══════════════════════════════════════════════════════════════

    public function test_assign_project(): void
    {
        $this->assignProject->expects(self::once())
            ->method('assign')
            ->with('proj-1', 'user-2', true);

        $this->assignProject->expects(self::never())
            ->method('unassign');

        $_POST = ['projectID' => 'proj-1', 'userID' => 'user-2', 'acept_login' => '1'];

        $result = $this->invokePrivate('processAssignProject');

        self::assertTrue($result['success']);
    }

    public function test_unassign_project(): void
    {
        $this->assignProject->expects(self::once())
            ->method('unassign')
            ->with('proj-1');

        $this->assignProject->expects(self::never())
            ->method('assign');

        $_POST = ['projectID' => 'proj-1', 'userID' => ''];

        $result = $this->invokePrivate('processAssignProject');

        self::assertTrue($result['success']);
    }

    public function test_assign_project_missing_id_returns_error(): void
    {
        $this->assignProject->expects(self::never())
            ->method('assign');

        $this->assignProject->expects(self::never())
            ->method('unassign');

        $_POST = ['projectID' => ''];

        $result = $this->invokePrivate('processAssignProject');

        self::assertFalse($result['success']);
    }

    public function test_create_project_success(): void
    {
        $this->saveProject->expects(self::once())
            ->method('create')
            ->with(self::isType('string'), 'My Project');

        $_POST = ['project_name' => 'My Project'];

        $result = $this->invokePrivate('processCreateProject');

        self::assertTrue($result['success']);
    }

    public function test_create_project_empty_name_returns_error(): void
    {
        $this->saveProject->expects(self::never())
            ->method('create');

        $_POST = ['project_name' => ''];

        $result = $this->invokePrivate('processCreateProject');

        self::assertFalse($result['success']);
    }

    public function test_delete_project_success(): void
    {
        $this->deleteProject->expects(self::once())
            ->method('execute')
            ->with('proj-1');

        $_POST = ['projectID' => 'proj-1'];

        $result = $this->invokePrivate('processDeleteProject');

        self::assertTrue($result['success']);
    }

    public function test_update_project_success(): void
    {
        $this->saveProject->expects(self::once())
            ->method('update')
            ->with('proj-1', 'Renamed', 'user-1', true);

        $_POST = ['projectID' => 'proj-1', 'project_name' => 'Renamed', 'userID' => 'user-1', 'acept_login' => '1'];

        $result = $this->invokePrivate('processUpdateProject');

        self::assertTrue($result['success']);
    }

    public function test_update_project_missing_fields_returns_error(): void
    {
        $this->saveProject->expects(self::never())
            ->method('update');

        $_POST = ['projectID' => '', 'project_name' => ''];

        $result = $this->invokePrivate('processUpdateProject');

        self::assertFalse($result['success']);
    }

    // ══════════════════════════════════════════════════════════════
    // Level handlers
    // ══════════════════════════════════════════════════════════════

    public function test_create_level_success(): void
    {
        $this->createLevel->expects(self::once())
            ->method('execute')
            ->with(
                self::isType('string'),  // levelId UUID
                'Moderator',
                1,
                [3, 7],
            );

        $_POST = ['level_name' => 'Moderator', 'level_type' => '1', 'perms' => ['3', '7']];

        $result = $this->invokePrivate('processCreateLevel');

        self::assertTrue($result['success']);
    }

    public function test_update_level_success(): void
    {
        $this->updateLevel->expects(self::once())
            ->method('execute')
            ->with('lvl-5', 'Updated Level', [1, 2]);

        $_POST = ['levelID' => 'lvl-5', 'level_name' => 'Updated Level', 'perms' => ['1', '2']];

        $result = $this->invokePrivate('processUpdateLevel');

        self::assertTrue($result['success']);
    }

    public function test_update_level_missing_fields_returns_error(): void
    {
        $this->updateLevel->expects(self::never())
            ->method('execute');

        $_POST = ['levelID' => '', 'level_name' => ''];

        $result = $this->invokePrivate('processUpdateLevel');

        self::assertFalse($result['success']);
    }

    public function test_delete_level_success(): void
    {
        $this->deleteLevel->expects(self::once())
            ->method('execute')
            ->with('lvl-9');

        $_POST = ['levelID' => 'lvl-9'];

        $result = $this->invokePrivate('processDeleteLevel');

        self::assertTrue($result['success']);
    }

    public function test_delete_level_missing_id_returns_error(): void
    {
        $this->deleteLevel->expects(self::never())
            ->method('execute');

        $_POST = ['levelID' => ''];

        $result = $this->invokePrivate('processDeleteLevel');

        self::assertFalse($result['success']);
    }

    // ══════════════════════════════════════════════════════════════
    // DomainException handling (via handleUsers/Levels public API)
    // ══════════════════════════════════════════════════════════════

    public function test_user_handler_wraps_domain_exception(): void
    {
        $this->createUser->method('execute')
            ->willThrowException(new \DomainException('Email is already in use'));

        // Configurar mocks para que la vista no falle
        $this->legacyReader->method('getAllUsers')->willReturn([]);
        $this->legacyReader->method('getAllLevels')->willReturn([]);
        $this->legacyReader->method('getAllProjects')->willReturn([]);
        $this->legacyReader->method('getClientUsers')->willReturn([]);
        $this->authContext->method('currentUser')->willReturn([
            'userID' => 'admin-1', 'email' => 'admin@admin.com', 'name' => 'Admin',
            'level' => 'lvl-1', 'level_name' => 'admin', 'level_type' => 0,
        ]);
        $this->authContext->method('can')->willReturn(true);

        $_GET['tab'] = 'usuarios';
        $_POST = [
            'action' => 'create_user',
            'email'  => 'dupe@test.com',
            'password' => 'pass',
            'level'  => 'lvl-1',
        ];

        // handleUsers() atrapa la DomainException, setea $msg y $msg_type
        // La vista renderiza normalmente en este contexto (no hay HTML shell)
        $this->controller->handleUsers();
        // Si llegamos acá sin excepción, el DomainException fue atrapado correctamente
        self::assertTrue(true);
    }
}
