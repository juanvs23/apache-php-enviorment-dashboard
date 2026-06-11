<?php

declare(strict_types=1);

namespace Dashboard\Presentation;

use Dashboard\Application\Repository\LevelRepositoryInterface;
use Dashboard\Application\Repository\PermissionRepositoryInterface;
use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Application\UseCase\Auth\LoginUseCase;
use Dashboard\Application\UseCase\Auth\LogoutUseCase;
use Dashboard\Presentation\Controller\AuthController;
use Dashboard\Application\UseCase\Level\CreateLevelUseCase;
use Dashboard\Application\UseCase\Level\DeleteLevelUseCase;
use Dashboard\Application\UseCase\Level\UpdateLevelUseCase;
use Dashboard\Application\UseCase\Permission\CheckPermissionUseCase;
use Dashboard\Application\UseCase\Project\AssignProjectUseCase;
use Dashboard\Application\UseCase\Project\DeleteProjectUseCase;
use Dashboard\Application\UseCase\Project\ListProjectsForUserUseCase;
use Dashboard\Application\UseCase\Project\SaveProjectUseCase;
use Dashboard\Application\UseCase\User\CreateUserUseCase;
use Dashboard\Application\UseCase\User\DeleteUserUseCase;
use Dashboard\Application\UseCase\User\ListUsersUseCase;
use Dashboard\Application\UseCase\User\UpdateUserUseCase;
use Dashboard\Database\Connection;
use Dashboard\Infrastructure\Auth\AuthContext;
use Dashboard\Infrastructure\Filesystem\ProjectScanner;
use Dashboard\Infrastructure\Persistence\MySQLLevelRepository;
use Dashboard\Infrastructure\Persistence\MySQLPermissionRepository;
use Dashboard\Infrastructure\Persistence\MySQLProjectRepository;
use Dashboard\Infrastructure\Persistence\MySQLUserRepository;
use Dashboard\Infrastructure\Session\SessionManager;

/**
 * Contenedor de servicios simple (Service Container).
 *
 * Responsabilidad única: wirear las dependencias de la aplicación
 * y proveer instancias a los controladores.
 *
 * Implementa lazy initialization: los servicios se crean la primera
 * vez que se solicitan y se cachean para el resto del request.
 *
 * Uso:
 *   $loginUseCase = ServiceContainer::get(LoginUseCase::class);
 *   $scanner      = ServiceContainer::get(ProjectScanner::class);
 */
final class ServiceContainer
{
    /**
     * Cache de servicios instanciados.
     *
     * @var array<string, object>
     */
    private static array $services = [];

    /**
     * Mapa de fábricas: service-id → callable.
     *
     * @var array<string, callable>|null
     */
    private static ?array $factories = null;

    /**
     * Retorna una instancia del servicio solicitado.
     *
     * @template T of object
     * @param class-string<T> $id FQCN de la clase o interfaz
     * @return T Instancia del servicio
     */
    public static function get(string $id): object
    {
        if (isset(self::$services[$id])) {
            return self::$services[$id];
        }

        self::ensureFactories();

        if (!isset(self::$factories[$id])) {
            throw new \RuntimeException("Service not registered: {$id}");
        }

        $factory = self::$factories[$id];
        $instance = $factory();
        self::$services[$id] = $instance;

        return $instance;
    }

    /**
     * Reinicia el contenedor (útil en tests).
     */
    public static function reset(): void
    {
        self::$services = [];
        self::$factories = null;
    }

    /**
     * Inicializa el mapa de fábricas si no existe.
     */
    private static function ensureFactories(): void
    {
        if (self::$factories !== null) {
            return;
        }

        // La conexión PDO se comparte entre repositorios
        $pdo = \Dashboard\Database\Connection::get();

        self::$factories = [
            // ─── Infrastructure ────────────────────────────────────
            ProjectScanner::class          => fn() => new ProjectScanner(dirname(__DIR__, 2)),
            SessionManager::class          => fn() => new SessionManager(5, 900),
            AuthContext::class             => fn() => new AuthContext(),

            // ─── Repositories ──────────────────────────────────────
            UserRepositoryInterface::class => fn() => new MySQLUserRepository($pdo),
            LevelRepositoryInterface::class => fn() => new MySQLLevelRepository($pdo),
            ProjectRepositoryInterface::class => fn() => new MySQLProjectRepository($pdo),
            PermissionRepositoryInterface::class => fn() => new MySQLPermissionRepository($pdo),

            // ─── Use Cases: Auth ───────────────────────────────────
            LoginUseCase::class  => fn() => new LoginUseCase(
                self::get(UserRepositoryInterface::class),
            ),
            LogoutUseCase::class => fn() => new LogoutUseCase(),

            // ─── Use Cases: User ───────────────────────────────────
            CreateUserUseCase::class => fn() => new CreateUserUseCase(
                self::get(UserRepositoryInterface::class),
            ),
            ListUsersUseCase::class => fn() => new ListUsersUseCase(
                self::get(UserRepositoryInterface::class),
            ),
            UpdateUserUseCase::class => fn() => new UpdateUserUseCase(
                self::get(UserRepositoryInterface::class),
            ),
            DeleteUserUseCase::class => fn() => new DeleteUserUseCase(
                self::get(UserRepositoryInterface::class),
                self::get(ProjectRepositoryInterface::class),
            ),

            // ─── Use Cases: Project ────────────────────────────────
            ListProjectsForUserUseCase::class => fn() => new ListProjectsForUserUseCase(
                self::get(ProjectRepositoryInterface::class),
            ),
            SaveProjectUseCase::class => fn() => new SaveProjectUseCase(
                self::get(ProjectRepositoryInterface::class),
            ),
            DeleteProjectUseCase::class => fn() => new DeleteProjectUseCase(
                self::get(ProjectRepositoryInterface::class),
            ),
            AssignProjectUseCase::class => fn() => new AssignProjectUseCase(
                self::get(ProjectRepositoryInterface::class),
            ),

            // ─── Use Cases: Level ──────────────────────────────────
            CreateLevelUseCase::class => fn() => new CreateLevelUseCase(
                self::get(LevelRepositoryInterface::class),
                self::get(PermissionRepositoryInterface::class),
            ),
            UpdateLevelUseCase::class => fn() => new UpdateLevelUseCase(
                self::get(LevelRepositoryInterface::class),
                self::get(PermissionRepositoryInterface::class),
            ),
            DeleteLevelUseCase::class => fn() => new DeleteLevelUseCase(
                self::get(LevelRepositoryInterface::class),
                self::get(UserRepositoryInterface::class),
            ),

            // ─── Use Cases: Permission ─────────────────────────────
            CheckPermissionUseCase::class => fn() => new CheckPermissionUseCase(
                self::get(PermissionRepositoryInterface::class),
            ),

            // ─── Controllers ─────────────────────────────────────────
            AuthController::class => fn() => new AuthController(
                self::get(LoginUseCase::class),
                self::get(SessionManager::class),
            ),
        ];
    }
}
