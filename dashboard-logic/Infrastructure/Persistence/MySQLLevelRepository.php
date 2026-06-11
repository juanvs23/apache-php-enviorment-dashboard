<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\Persistence;

use Dashboard\Application\Repository\LevelRepositoryInterface;
use Dashboard\Domain\Entity\Level;
use Dashboard\Domain\ValueObject\LevelType;
use PDO;

/**
 * Repositorio de niveles implementado con MySQL (vía PDO).
 *
 * Implementa LevelRepositoryInterface usando la tabla `levels` del schema actual.
 * Mapea las filas de la base de datos a entidades Level del dominio.
 *
 * Dependencias:
 *   - PDO: conexión a MySQL obtenida via Dashboard\Database\Connection::get()
 *
 * @see LevelRepositoryInterface
 */
final class MySQLLevelRepository implements LevelRepositoryInterface
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
    public function findById(string $levelId): ?Level
    {
        $stmt = $this->pdo->prepare('
            SELECT levelsID, level_name, level_type
            FROM levels
            WHERE levelsID = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $levelId]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToLevel($row) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(): array
    {
        $rows = $this->pdo->query('
            SELECT levelsID, level_name, level_type
            FROM levels
            ORDER BY level_type, level_name
        ')->fetchAll();

        return array_map([$this, 'mapRowToLevel'], $rows);
    }

    /**
     * {@inheritDoc}
     *
     * Retorna los niveles sin permisos incluidos.
     * Los permisos se cargan por separado via PermissionRepositoryInterface.
     */
    public function findAllWithPermissions(): array
    {
        return $this->findAll();
    }

    /**
     * {@inheritDoc}
     */
    public function save(Level $level): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO levels (levelsID, level_name, level_type)
            VALUES (:id, :name, :type)
            ON DUPLICATE KEY UPDATE
                level_name = VALUES(level_name),
                level_type = VALUES(level_type)
        ');
        $stmt->execute([
            ':id'   => $level->levelId(),
            ':name' => $level->levelName(),
            ':type' => $level->type()->value(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $levelId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM levels WHERE levelsID = :id');
        $stmt->execute([':id' => $levelId]);
    }

    /**
     * {@inheritDoc}
     */
    public function nameExists(string $name): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM levels WHERE level_name = :name LIMIT 1');
        $stmt->execute([':name' => $name]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Mapea una fila de la base de datos a una entidad Level.
     *
     * @param array<string, mixed> $row Fila de la tabla levels
     * @return Level                     Entidad Level mapeada
     */
    private function mapRowToLevel(array $row): Level
    {
        return new Level(
            $row['levelsID'],
            $row['level_name'],
            new LevelType((int) $row['level_type']),
        );
    }
}
