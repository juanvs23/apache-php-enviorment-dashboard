<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\Persistence;

use Dashboard\Application\Repository\PermissionRepositoryInterface;
use Dashboard\Domain\Entity\Permission;
use PDO;

/**
 * Repositorio de permisos implementado con MySQL (vía PDO).
 *
 * Implementa PermissionRepositoryInterface usando las tablas `permissions`
 * y `level_permissions` del schema actual.
 *
 * Gestiona:
 *   - Catálogo de permisos (tabla permissions)
 *   - Asignación de permisos a niveles (tabla level_permissions)
 *   - Verificación de permisos por usuario (cadena: usuario → nivel → level_permissions)
 *
 * Dependencias:
 *   - PDO: conexión a MySQL obtenida via Dashboard\Database\Connection::get()
 *
 * @see PermissionRepositoryInterface
 */
final class MySQLPermissionRepository implements PermissionRepositoryInterface
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
    public function findAll(): array
    {
        $rows = $this->pdo->query('
            SELECT id, perm_key, perm_label
            FROM permissions
            ORDER BY id
        ')->fetchAll();

        return array_map([$this, 'mapRowToPermission'], $rows);
    }

    /**
     * {@inheritDoc}
     */
    public function findKeysByLevel(string $levelId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT p.perm_key
            FROM permissions p
            JOIN level_permissions lp ON lp.perm_id = p.id
            WHERE lp.levelID = :levelId
            ORDER BY p.perm_key
        ');
        $stmt->execute([':levelId' => $levelId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * {@inheritDoc}
     */
    public function findByLevel(string $levelId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT p.id, p.perm_key, p.perm_label
            FROM permissions p
            JOIN level_permissions lp ON lp.perm_id = p.id
            WHERE lp.levelID = :levelId
            ORDER BY p.id
        ');
        $stmt->execute([':levelId' => $levelId]);

        return array_map([$this, 'mapRowToPermission'], $stmt->fetchAll());
    }

    /**
     * {@inheritDoc}
     */
    public function syncLevelPermissions(string $levelId, array $permissionIds): void
    {
        $this->pdo->prepare('DELETE FROM level_permissions WHERE levelID = :id')
            ->execute([':id' => $levelId]);

        if (!empty($permissionIds)) {
            $stmt = $this->pdo->prepare('INSERT IGNORE INTO level_permissions (levelID, perm_id) VALUES (:levelId, :permId)');
            foreach ($permissionIds as $permId) {
                $stmt->execute([
                    ':levelId' => $levelId,
                    ':permId'  => (int) $permId,
                ]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function userHasPermission(string $userId, string $permKey): bool
    {
        // Primero verificar si el usuario es admin (type=0) — tiene todos los permisos
        $stmt = $this->pdo->prepare('
            SELECT 1
            FROM USERS u
            JOIN levels l ON l.levelsID = u.level
            WHERE u.userID = :userId
              AND l.level_type = 0
            LIMIT 1
        ');
        $stmt->execute([':userId' => $userId]);
        if ($stmt->fetchColumn()) {
            return true;
        }

        // No es admin: consultar level_permissions
        $stmt = $this->pdo->prepare('
            SELECT 1
            FROM permissions p
            JOIN level_permissions lp ON lp.perm_id = p.id
            JOIN USERS u ON u.level = lp.levelID
            WHERE u.userID = :userId
              AND p.perm_key = :permKey
            LIMIT 1
        ');
        $stmt->execute([
            ':userId'  => $userId,
            ':permKey' => $permKey,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Mapea una fila de la base de datos a una entidad Permission.
     *
     * @param array<string, mixed> $row Fila de la tabla permissions
     * @return Permission                Entidad Permission mapeada
     */
    private function mapRowToPermission(array $row): Permission
    {
        return new Permission(
            (int) $row['id'],
            $row['perm_key'],
            $row['perm_label'],
        );
    }
}
