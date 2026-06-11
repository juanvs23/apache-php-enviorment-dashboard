<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\Persistence;

use Dashboard\Database\Connection;
use PDO;

/**
 * Lector legacy de datos para vistas.
 *
 * Encapsula las 6 funciones globales de lectura que las vistas
 * todavía necesitan para renderizar. Usa PDO directo porque las
 * vistas esperan arrays con formato específico (no entidades).
 *
 * Migrar a repositorios + array-mapping cuando las vistas
 * se refactoricen para usar entidades directamente.
 */
final class LegacyReader
{
    /**
     * Retorna todos los usuarios con su nivel (para vistas).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllUsers(): array
    {
        try {
            return Connection::get()->query('
                SELECT u.userID, u.email, u.name, u.level, l.level_name, l.level_type
                FROM USERS u
                JOIN levels l ON l.levelsID = u.level
                ORDER BY l.level_type, u.email
            ')->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Retorna todos los niveles (para vistas).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllLevels(): array
    {
        try {
            return Connection::get()->query(
                'SELECT levelsID, level_name, level_type FROM levels ORDER BY level_type'
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Retorna todos los proyectos con su usuario asignado (para vistas).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllProjects(): array
    {
        try {
            return Connection::get()->query('
                SELECT p.id, p.project_name, p.user_own, p.acept_login,
                       u.email AS user_email, u.name AS user_name
                FROM Project p
                LEFT JOIN USERS u ON u.userID = p.user_own
                ORDER BY p.project_name
            ')->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Retorna los usuarios de tipo cliente (level_type=1).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getClientUsers(): array
    {
        try {
            $pdo = Connection::get();
            $level = $pdo->query("SELECT levelsID FROM levels WHERE level_type = 1 LIMIT 1")->fetchColumn();
            if (!$level) {
                return [];
            }
            $stmt = $pdo->prepare('SELECT userID, email, name FROM USERS WHERE level = :level ORDER BY email');
            $stmt->execute([':level' => $level]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Retorna los niveles con sus permisos asignados (para vistas).
     *
     * @return array<int, array{levelsID: string, level_name: string, level_type: int, perms: string[]}>
     */
    public function getAllLevelsWithPerms(): array
    {
        try {
            $pdo = Connection::get();
            $levels = $pdo->query('
                SELECT l.levelsID, l.level_name, l.level_type
                FROM levels l
                ORDER BY l.level_type, l.level_name
            ')->fetchAll();

            $permStmt = $pdo->prepare('
                SELECT p.perm_key
                FROM permissions p
                JOIN level_permissions lp ON lp.perm_id = p.id
                WHERE lp.levelID = :levelID
            ');
            foreach ($levels as &$lvl) {
                $permStmt->execute([':levelID' => $lvl['levelsID']]);
                $lvl['perms'] = $permStmt->fetchAll(PDO::FETCH_COLUMN);
            }

            return $levels;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Retorna el catálogo de permisos (para vistas).
     *
     * @return array<int, array{id: int, perm_key: string, perm_label: string}>
     */
    public function getAllPermissions(): array
    {
        try {
            return Connection::get()->query(
                'SELECT id, perm_key, perm_label FROM permissions ORDER BY id'
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }
}
