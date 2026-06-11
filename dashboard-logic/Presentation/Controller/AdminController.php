<?php

declare(strict_types=1);

namespace Dashboard\Presentation\Controller;

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

/**
 * Controlador de administración.
 *
 * Maneja la gestión de usuarios, niveles (roles) y proyectos
 * desde la interfaz de administración del dashboard.
 *
 * Las operaciones de escritura (create/update/delete) se delegan
 * a Use Cases. Las lecturas para las vistas todavía usan las
 * funciones legacy (get_all_users, get_all_levels, etc.) para
 * mantener compatibilidad con el formato de arrays que esperan
 * los templates existentes.
 *
 * Rutas:
 *   GET  ?users=1              → handleUsers()   — gestión de usuarios y proyectos
 *   GET  ?users=1&tab=levels   → handleLevels()  — gestión de niveles y permisos
 */
final class AdminController
{
    /**
     * @param CreateLevelUseCase       $createLevel       Caso de uso para crear niveles
     * @param UpdateLevelUseCase       $updateLevel       Caso de uso para actualizar niveles
     * @param DeleteLevelUseCase       $deleteLevel       Caso de uso para eliminar niveles
     * @param CheckPermissionUseCase   $checkPermission   Caso de uso para verificar permisos
     * @param CreateUserUseCase        $createUser        Caso de uso para crear usuarios
     * @param UpdateUserUseCase        $updateUser        Caso de uso para actualizar usuarios
     * @param DeleteUserUseCase        $deleteUser        Caso de uso para eliminar usuarios
     * @param SaveProjectUseCase       $saveProject       Caso de uso para crear/actualizar proyectos
     * @param DeleteProjectUseCase     $deleteProject     Caso de uso para eliminar proyectos
     * @param AssignProjectUseCase     $assignProject     Caso de uso para asignar/desasignar proyectos
     */
    public function __construct(
        private readonly CreateLevelUseCase $createLevel,
        private readonly UpdateLevelUseCase $updateLevel,
        private readonly DeleteLevelUseCase $deleteLevel,
        private readonly CheckPermissionUseCase $checkPermission,
        private readonly CreateUserUseCase $createUser,
        private readonly UpdateUserUseCase $updateUser,
        private readonly DeleteUserUseCase $deleteUser,
        private readonly SaveProjectUseCase $saveProject,
        private readonly DeleteProjectUseCase $deleteProject,
        private readonly AssignProjectUseCase $assignProject,
    ) {}

    /**
     * Maneja la gestión de usuarios y proyectos.
     *
     * Procesa formularios POST usando Use Cases (create/update/delete user,
     * create/update/delete/assign project) y prepara los datos para la vista.
     *
     * Variables que prepara para `views/user-management.php`:
     *   - $msg, $msg_type → resultado de la operación POST
     *   - $tab            → pestaña activa ('usuarios' | 'proyectos')
     *   - $users          → lista de usuarios
     *   - $levels         → lista de niveles
     *   - $projects       → lista de proyectos
     *   - $client_users   → usuarios tipo client
     */
    public function handleUsers(): void
    {
        require_once __DIR__ . '/../../user-management.php';

        $msg      = '';
        $msg_type = 'success';
        $tab      = \trim($_GET['tab'] ?? 'usuarios');

        // ─── Procesar POST con Use Cases ──────────────────────────
        $action = $_POST['action'] ?? '';

        try {
            $result = match ($action) {
                'create_user'    => $this->processCreateUser(),
                'update_user'    => $this->processUpdateUser(),
                'delete_user'    => $this->processDeleteUser(),
                'assign_project' => $this->processAssignProject(),
                'create_project' => $this->processCreateProject(),
                'delete_project' => $this->processDeleteProject(),
                'update_project' => $this->processUpdateProject(),
                default          => null,
            };
        } catch (\DomainException $e) {
            $result = ['success' => false, 'error' => $e->getMessage()];
        }

        if ($result) {
            $msg      = $result['error'] ?? 'Operación exitosa';
            $msg_type = $result['success'] ? 'success' : 'danger';
        }

        $users        = \get_all_users();
        $levels       = \get_all_levels();
        $projects     = \get_all_projects();
        $client_users = \get_client_users();

        require __DIR__ . '/../../views/user-management.php';
    }

    /**
     * Maneja la gestión de niveles y permisos.
     *
     * Procesa formularios POST usando CreateLevelUseCase, UpdateLevelUseCase
     * y DeleteLevelUseCase (ya inyectados). Las lecturas para la vista
     * todavía usan funciones legacy para mantener compatibilidad de formato.
     *
     * Prepara las variables que necesita la vista `views/level-management.php`:
     *   - $msg, $msg_type → resultado de la operación POST
     *   - $tab            → pestaña activa (siempre 'levels')
     *   - $levels         → niveles con permisos (get_all_levels_with_perms)
     *   - $permissions    → catálogo de permisos (get_all_permissions)
     */
    public function handleLevels(): void
    {
        require_once __DIR__ . '/../../level-management.php';

        $msg      = '';
        $msg_type = 'success';
        $tab      = 'levels';

        // ─── Procesar POST con Use Cases ──────────────────────────
        $action = $_POST['action'] ?? '';

        try {
            $result = match ($action) {
                'create_level' => $this->processCreateLevel(),
                'update_level' => $this->processUpdateLevel(),
                'delete_level' => $this->processDeleteLevel(),
                default        => null,
            };
        } catch (\DomainException $e) {
            $result = ['success' => false, 'error' => $e->getMessage()];
        }

        if ($result) {
            $msg      = $result['error'] ?? 'Operación exitosa';
            $msg_type = $result['success'] ? 'success' : 'danger';
        }

        $levels      = \get_all_levels_with_perms();
        $permissions = \get_all_permissions();

        require __DIR__ . '/../../views/level-management.php';
    }

    // ─── Handlers internos ──────────────────────────────────────────

    /**
     * Procesa la creación de un usuario via CreateUserUseCase.
     *
     * @return array{success: bool, error?: string, userID?: string}
     */
    private function processCreateUser(): array
    {
        $email    = \trim($_POST['email'] ?? '');
        $name     = \trim($_POST['name'] ?? '');
        $pass     = $_POST['password'] ?? '';
        $level    = \trim($_POST['level'] ?? '');

        if ($level === '') {
            return ['success' => false, 'error' => 'Nivel requerido'];
        }

        $userId = \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex(\random_bytes(16)), 4));
        $user = $this->createUser->execute($userId, $email, $name !== '' ? $name : null, $pass, $level);

        return ['success' => true, 'userID' => $user->userId()];
    }

    /**
     * Procesa la actualización de un usuario via UpdateUserUseCase.
     *
     * @return array{success: bool, error?: string}
     */
    private function processUpdateUser(): array
    {
        $userId = \trim($_POST['userID'] ?? '');
        $email  = \trim($_POST['email'] ?? '');
        $name   = \trim($_POST['name'] ?? '');
        $pass   = $_POST['password'] ?? '';
        $level  = \trim($_POST['level'] ?? '');

        if ($userId === '' || $email === '' || $level === '') {
            return ['success' => false, 'error' => 'Faltan campos requeridos'];
        }

        $this->updateUser->execute($userId, $email, $name !== '' ? $name : null, $pass, $level);

        return ['success' => true];
    }

    /**
     * Procesa la eliminación de un usuario via DeleteUserUseCase.
     *
     * @return array{success: bool, error?: string}
     */
    private function processDeleteUser(): array
    {
        $userId = \trim($_POST['userID'] ?? '');
        if ($userId === '') {
            return ['success' => false, 'error' => 'ID de usuario requerido'];
        }

        // DeleteUserUseCase ahora desvincula proyectos automáticamente
        $this->deleteUser->execute($userId);
        return ['success' => true];
    }

    /**
     * Procesa la asignación de un proyecto a un usuario via AssignProjectUseCase.
     *
     * @return array{success: bool, error?: string}
     */
    private function processAssignProject(): array
    {
        $projectId = \trim($_POST['projectID'] ?? '');
        $userId    = \trim($_POST['userID'] ?? '');
        $acept     = (bool) ($_POST['acept_login'] ?? 0);

        if ($projectId === '') {
            return ['success' => false, 'error' => 'ID de proyecto requerido'];
        }

        if ($userId === '') {
            $this->assignProject->unassign($projectId);
        } else {
            $this->assignProject->assign($projectId, $userId, $acept);
        }

        return ['success' => true];
    }

    /**
     * Procesa la creación de un proyecto via SaveProjectUseCase.
     *
     * @return array{success: bool, error?: string}
     */
    private function processCreateProject(): array
    {
        $name   = \trim($_POST['project_name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'error' => 'Nombre del proyecto requerido'];
        }

        $projectId = \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex(\random_bytes(16)), 4));
        $this->saveProject->create($projectId, $name);

        return ['success' => true];
    }

    /**
     * Procesa la eliminación de un proyecto via DeleteProjectUseCase.
     *
     * @return array{success: bool, error?: string}
     */
    private function processDeleteProject(): array
    {
        $projectId = \trim($_POST['projectID'] ?? '');
        if ($projectId === '') {
            return ['success' => false, 'error' => 'ID de proyecto requerido'];
        }

        $this->deleteProject->execute($projectId);
        return ['success' => true];
    }

    /**
     * Procesa la actualización de un proyecto via SaveProjectUseCase.
     *
     * @return array{success: bool, error?: string}
     */
    private function processUpdateProject(): array
    {
        $projectId = \trim($_POST['projectID'] ?? '');
        $name      = \trim($_POST['project_name'] ?? '');
        $userId    = \trim($_POST['userID'] ?? '');
        $acept     = (bool) ($_POST['acept_login'] ?? 0);

        if ($projectId === '' || $name === '') {
            return ['success' => false, 'error' => 'ID y nombre del proyecto requeridos'];
        }

        $this->saveProject->update($projectId, $name, $userId !== '' ? $userId : null, $acept);
        return ['success' => true];
    }

    // ─── Level Handlers ──────────────────────────────────────────

    /**
     * Procesa la creación de un nivel via CreateLevelUseCase.
     *
     * @return array{success: bool, error?: string}
     */
    private function processCreateLevel(): array
    {
        $name  = \trim($_POST['level_name'] ?? '');
        $type  = (int) ($_POST['level_type'] ?? 1);
        $perms = \array_map('intval', $_POST['perms'] ?? []);

        $levelId = \vsprintf('%s%s-%s-%s-%s-%s%s%s', \str_split(\bin2hex(\random_bytes(16)), 4));
        $this->createLevel->execute($levelId, $name, $type, $perms);

        return ['success' => true];
    }

    /**
     * Procesa la actualización de un nivel via UpdateLevelUseCase.
     * Protege el nivel admin (no se puede modificar).
     *
     * @return array{success: bool, error?: string}
     */
    private function processUpdateLevel(): array
    {
        $levelId = \trim($_POST['levelID'] ?? '');
        $name    = \trim($_POST['level_name'] ?? '');
        $perms   = \array_map('intval', $_POST['perms'] ?? []);

        if ($levelId === '' || $name === '') {
            return ['success' => false, 'error' => 'ID y nombre requeridos'];
        }

        $this->updateLevel->execute($levelId, $name, $perms);
        return ['success' => true];
    }

    /**
     * Procesa la eliminación de un nivel via DeleteLevelUseCase.
     * Protege el nivel admin y niveles con usuarios asignados.
     *
     * @return array{success: bool, error?: string}
     */
    private function processDeleteLevel(): array
    {
        $levelId = \trim($_POST['levelID'] ?? '');
        if ($levelId === '') {
            return ['success' => false, 'error' => 'ID de nivel requerido'];
        }

        $this->deleteLevel->execute($levelId);
        return ['success' => true];
    }
}
