<?php

declare(strict_types=1);

namespace Dashboard\Presentation;

use Dashboard\Infrastructure\Auth\AuthContext;
use Dashboard\Infrastructure\Persistence\LegacyReader;
use Dashboard\Presentation\Controller\AdminController;
use Dashboard\Presentation\Controller\AuthController;
use Dashboard\Presentation\Controller\DashboardController;
use Dashboard\Presentation\Controller\ProfileController;

/**
 * Router principal del dashboard.
 *
 * Responsabilidad única: interpretar la request HTTP y delegar
 * la renderización del body al controlador correspondiente.
 *
 * Este router se ejecuta DENTRO del shell HTML (después de <body>).
 * Las operaciones que requieren headers HTTP (login, logout, redirects)
 * se manejan en index.php ANTES del DOCTYPE.
 *
 * Mapeo de rutas (body content):
 *   No autenticado    → views/login.php
 *   Sin params        → DashboardController::index()
 *   ?users=1          → AdminController::handleUsers()
 *   ?users=1&tab=levels → AdminController::handleLevels()
 *   ?profile=1        → ProfileController::handleProfile()
 *   ?phpinfo=1        → phpinfo()
 */
final class Router
{
    /**
     * @var AuthContext Contexto de autenticación
     */
    private AuthContext $authContext;

    /**
     * @var string Nombre del script actual
     */
    private string $scriptName;

    /**
     * @var string Mensaje de error (desde rate limit o login fallido)
     */
    private string $error;

    /**
     * @var string Parámetro de redirect
     */
    private string $redirectParam;

    /**
     * @param AuthContext $authContext   Contexto de autenticación
     * @param string      $scriptName    $_SERVER['SCRIPT_NAME']
     * @param string      $error         Mensaje de error (rate limit o login)
     * @param string      $redirectParam Parámetro de redirect
     */
    public function __construct(
        AuthContext $authContext,
        string $scriptName,
        string $error = '',
        string $redirectParam = '',
    ) {
        $this->authContext   = $authContext;
        $this->scriptName    = $scriptName;
        $this->error         = $error;
        $this->redirectParam = $redirectParam;
    }

    /**
     * Renderiza el body content según la ruta detectada.
     *
     * Llama al controlador correspondiente que prepara los datos
     * y requiere la vista. Las vistas son partials que se renderizan
     * dentro del shell HTML de index.php.
     */
    public function render(): void
    {
        $authenticated = $this->authContext->isAuthenticated();

        // ─── No autenticado: login ─────────────────────────────
        if (!$authenticated) {
            $error          = $this->error;
            $script_name    = $this->scriptName;
            $redirect_param = $this->redirectParam;
            require __DIR__ . '/../views/login.php';
            return;
        }

        // ─── Autenticado: refrescar cookie ─────────────────────
        $this->authContext->refreshCookie();

        // ─── phpinfo (solo admin en modo desarrollo) ──────────────
        if (isset($_GET['phpinfo'])) {
            $devMode = ($_ENV['DEV_MODE'] ?? '1') === '1';
            $authUser = $this->authContext->currentUser();
            $isAdmin = $authUser && ($authUser['level_type'] ?? 1) === 0;
            if ($devMode && $this->authContext->isAuthenticated() && $isAdmin) {
                \phpinfo();
            } else {
                echo '<p style="color:#d29922;padding:2rem;font-family:monospace;">⚠️ phpinfo() deshabilitado. Requiere DEV_MODE=1 y autenticación de admin.</p>';
            }
            return;
        }

        // ─── Admin: usuarios y niveles ─────────────────────────
        if (isset($_GET['users'])) {
            $tab = $_GET['tab'] ?? 'usuarios';

            $controller = new AdminController(
                ServiceContainer::get(\Dashboard\Application\UseCase\Level\CreateLevelUseCase::class),
                ServiceContainer::get(\Dashboard\Application\UseCase\Level\UpdateLevelUseCase::class),
                ServiceContainer::get(\Dashboard\Application\UseCase\Level\DeleteLevelUseCase::class),
                ServiceContainer::get(\Dashboard\Application\UseCase\Permission\CheckPermissionUseCase::class),
                ServiceContainer::get(\Dashboard\Application\UseCase\User\CreateUserUseCase::class),
                ServiceContainer::get(\Dashboard\Application\UseCase\User\UpdateUserUseCase::class),
                ServiceContainer::get(\Dashboard\Application\UseCase\User\DeleteUserUseCase::class),
                ServiceContainer::get(\Dashboard\Application\UseCase\Project\SaveProjectUseCase::class),
                ServiceContainer::get(\Dashboard\Application\UseCase\Project\DeleteProjectUseCase::class),
                ServiceContainer::get(\Dashboard\Application\UseCase\Project\AssignProjectUseCase::class),
                $this->authContext,
                ServiceContainer::get(LegacyReader::class),
            );

            if ($tab === 'levels') {
                $controller->handleLevels();
            } else {
                $controller->handleUsers();
            }
            return;
        }

        // ─── Perfil ────────────────────────────────────────────
        if (isset($_GET['profile'])) {
            $controller = new ProfileController(
                ServiceContainer::get(\Dashboard\Application\UseCase\User\UpdateUserUseCase::class),
                $this->authContext,
            );
            $controller->handleProfile();
            return;
        }

        // ─── Default: Dashboard ────────────────────────────────
        $controller = new DashboardController(
            ServiceContainer::get(\Dashboard\Infrastructure\Filesystem\ProjectScanner::class),
            ServiceContainer::get(\Dashboard\Application\UseCase\Project\ListProjectsForUserUseCase::class),
            ServiceContainer::get(\Dashboard\Application\UseCase\Permission\CheckPermissionUseCase::class),
            $this->authContext,
        );
        $controller->index($this->scriptName);
    }
}
