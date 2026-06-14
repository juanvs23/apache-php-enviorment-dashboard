<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\Persistence;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Domain\Entity\Project;
use PDO;

/**
 * Repositorio de proyectos implementado con MySQL (vía PDO).
 *
 * Implementa ProjectRepositoryInterface usando la tabla `Project` del schema actual.
 * Mapea las filas de la base de datos a entidades Project del dominio.
 *
 * Dependencias:
 *   - PDO: conexión a MySQL obtenida via Dashboard\Database\Connection::get()
 *
 * @see ProjectRepositoryInterface
 */
final class MySQLProjectRepository implements ProjectRepositoryInterface
{
    /**
     * Conexión PDO compartida.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * @param PDO $pdo Conexión PDO a MySQL
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $projectId): ?Project
    {
        $stmt = $this->pdo->prepare('
                SELECT id, project_name, user_own
                FROM Project
            WHERE id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $projectId]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToProject($row) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(): array
    {
        $rows = $this->pdo->query('
                SELECT id, project_name, user_own
                FROM Project
            ORDER BY project_name
        ')->fetchAll();

        return array_map([$this, 'mapRowToProject'], $rows);
    }

    /**
     * {@inheritDoc}
     */
    public function findByUser(string $userId): array
    {
        $stmt = $this->pdo->prepare("
                SELECT id, project_name, user_own
                FROM Project
            WHERE user_own IS NOT NULL
              AND JSON_SEARCH(user_own, 'one', :userId, NULL, '\$[*].userID') IS NOT NULL
            ORDER BY project_name
        ");
        $stmt->execute([':userId' => $userId]);

        return array_map([$this, 'mapRowToProject'], $stmt->fetchAll());
    }

    /**
     * {@inheritDoc}
     */
    public function save(Project $project): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO Project (id, project_name, user_own)
            VALUES (:id, :name, :own)
            ON DUPLICATE KEY UPDATE
                project_name = VALUES(project_name),
                user_own     = VALUES(user_own)
        ');
        $stmt->execute([
            ':id'    => $project->projectId(),
            ':name'  => $project->projectName(),
            ':own'   => $project->userOwnJson(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $projectId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM Project WHERE id = :id');
        $stmt->execute([':id' => $projectId]);
    }

    /**
     * {@inheritDoc}
     */
    public function nameExists(string $projectName): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM Project WHERE project_name = :name LIMIT 1');
        $stmt->execute([':name' => $projectName]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * {@inheritDoc}
     */
    public function unassignProjectsByUserId(string $userId): void
    {
        // Eliminar usuario del JSON user_own donde aparezca
        $stmt = $this->pdo->prepare("
            UPDATE Project
            SET user_own = JSON_REMOVE(user_own, 
                JSON_UNQUOTE(JSON_SEARCH(user_own, 'one', :userId, NULL, '\$[*].userID'))
            )
            WHERE JSON_CONTAINS(user_own, JSON_QUOTE(:userId2), '\$[*].userID')
        ");
        $stmt->execute([':userId' => $userId, ':userId2' => $userId]);
    }

    /**
     * Mapea una fila de la base de datos a una entidad Project.
     *
     * @param array<string, mixed> $row Fila de la tabla Project
     * @return Project                    Entidad Project mapeada
     */
    private function mapRowToProject(array $row): Project
    {
        return new Project(
            $row['id'],
            $row['project_name'],
            $row['user_own'] ?: null,
        );
    }
}
