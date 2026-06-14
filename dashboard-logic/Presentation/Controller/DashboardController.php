<?php

declare(strict_types=1);

namespace Dashboard\Presentation\Controller;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Application\UseCase\Permission\CheckPermissionUseCase;
use Dashboard\Application\UseCase\Project\ListProjectsForUserUseCase;
use Dashboard\Domain\Entity\Project;
use Dashboard\Infrastructure\Auth\AuthContext;
use Dashboard\Infrastructure\Filesystem\ProjectScanner;

/**
 * Controlador del dashboard principal.
 *
 * Renderiza la grilla de proyectos con los botones de acceso
 * y la información del servidor (solo para usuarios con permisos).
 *
 * Flujo:
 *   1. Escanea el filesystem con ProjectScanner para detectar proyectos
 *   2. Enriquece cada proyecto con datos de la DB (acept_login)
 *   3. Filtra según permisos del usuario autenticado
 *
 * Ruta:
 *   GET / (sin params) → index()
 */
final class DashboardController
{
    /**
     * @param ProjectScanner                $scanner          Escáner de proyectos en filesystem
     * @param ListProjectsForUserUseCase    $listProjects     Caso de uso para listar proyectos desde DB
     * @param CheckPermissionUseCase        $checkPermission  Caso de uso para verificar permisos
     * @param AuthContext                   $authContext      Contexto de autenticación
     */
    public function __construct(
        private readonly ProjectScanner $scanner,
        private readonly ListProjectsForUserUseCase $listProjects,
        private readonly CheckPermissionUseCase $checkPermission,
        private readonly AuthContext $authContext,
    ) {}

    /**
     * Renderiza el dashboard principal.
     *
     * Prepara las variables que necesita la vista `views/dashboard.php`:
     *   - $projects       → array de proyectos enriquecidos
     *   - $has_projects   → bool si hay proyectos
     *   - $script_name    → string para formularios
     *   - $authUser       → usuario autenticado (array)
     *   - $isAdmin        → bool si es admin
     *   - $canManageUsers → bool permiso users.manage
     *   - $canViewServer  → bool permiso server.view
     *
     * @param string $scriptName $_SERVER['SCRIPT_NAME']
     */
    public function index(string $scriptName): void
    {
        $authUser   = $this->authContext->currentUser();
        $scriptName = $scriptName;

        // ─── Escanear filesystem con ProjectScanner ──────────────
        $projects = $this->scanner->scan();

        // Agregar campo badge (HTML) que espera la vista
        foreach ($projects as &$p) {
            $p['badge'] = $p['type'] !== '' ? self::typeBadge($p['type']) : '';
        }
        unset($p);

        // ─── Enriquecer con datos de la DB ───────────────────────
        $userId = $authUser ? ($authUser['userID'] ?? null) : null;
        $isAdminOrOp = $authUser && $this->checkPermission->execute($userId, 'projects.acept_login');

        try {
            $dbProjects = $this->listProjects->all();
            $loginMap = [];
            foreach ($dbProjects as $dbp) {
                $loginMap[\strtolower($dbp->projectName())] = $dbp->isLogeableForUser($userId ?? '');
            }

            foreach ($projects as &$p) {
                $p['acept_login'] = $isAdminOrOp
                    ? 1
                    : (int) ($loginMap[\strtolower($p['dir'])] ?? false);
            }
            unset($p);
        } catch (\Throwable) {
            // Sin DB, sin login
        }

        // ─── Filtrar para usuarios no-admin ──────────────────────
        if ($authUser && !$this->checkPermission->execute($authUser['userID'], 'projects.view_all')) {
            try {
                $userProjects = $this->listProjects->execute($authUser['userID']);
                $allowed = \array_map(
                    fn(Project $p) => \strtolower($p->projectName()),
                    $userProjects,
                );

                $projects = \array_values(\array_filter($projects, fn($p) =>
                    \in_array(\strtolower($p['dir']), $allowed)
                ));
            } catch (\Throwable) {
                $projects = [];
            }
        }

        $has_projects = !empty($projects);

        // ─── Auth data para la vista ────────────────────────────
        $isAdmin        = $authUser && $authUser['level_type'] === 0;
        $canManageUsers = $this->authContext->can('users.manage', $authUser);
        $canViewServer  = $this->authContext->can('server.view', $authUser);

        require __DIR__ . '/../../views/dashboard.php';
    }

    /**
     * Genera un badge HTML para el tipo de proyecto.
     *
     * @param string $type Tipo de proyecto (wordpress, laravel, etc.)
     * @return string HTML del badge Bootstrap
     */
    private static function typeBadge(string $type): string
    {
        $colors = [
            'wordpress'  => 'primary',
            'phpmyadmin' => 'warning',
            'laravel'    => 'danger',
            'symfony'    => 'info',
            'static'     => 'secondary',
        ];

        $color = $colors[\strtolower(\trim($type))] ?? 'secondary';

        return \sprintf(
            '<span class="badge bg-%s ms-2">%s</span>',
            $color,
            \htmlspecialchars(\trim($type)),
        );
    }
}
