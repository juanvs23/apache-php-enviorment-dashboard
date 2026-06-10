<?php

declare(strict_types=1);

namespace Dashboard\Database;

use PDO;

/**
 * Seed initial data for the dashboard database.
 *
 * Creates the default admin level and admin user.
 * Safe to run multiple times (uses INSERT IGNORE).
 */
final class Seed
{
    public static function run(PDO $pdo): void
    {
        // ── Levels ─────────────────────────────────────────────────────
        $pdo->exec("
            INSERT IGNORE INTO levels (levelsID, level_name, level_type)
            VALUES (UUID(), 'admin', 0)
        ");
        $pdo->exec("
            INSERT IGNORE INTO levels (levelsID, level_name, level_type)
            VALUES (UUID(), 'client', 1)
        ");

        // ── Permissions catalog ────────────────────────────────────────
        $perms = [
            ['users.manage',          'Gestionar usuarios (CRUD)'],
            ['users.edit_same_level', 'Editar usuarios de su mismo nivel'],
            ['projects.manage',       'Gestionar proyectos (CRUD)'],
            ['projects.view_all',     'Ver todos los proyectos'],
            ['projects.acept_login',  'Ver botones Acceder y WP Admin siempre'],
            ['server.view',           'Ver información del servidor'],
            ['badge.admin',           'Mostrar badge de admin'],
            ['profile.edit',          'Editar perfil propio'],
        ];
        $stmt = $pdo->prepare('INSERT IGNORE INTO permissions (perm_key, perm_label) VALUES (?, ?)');
        foreach ($perms as [$key, $label]) {
            $stmt->execute([$key, $label]);
        }

        // ── Fetch levels ───────────────────────────────────────────────
        $adminLevel  = $pdo->query("SELECT levelsID FROM levels WHERE level_name = 'admin' LIMIT 1")->fetchColumn();
        $clientLevel = $pdo->query("SELECT levelsID FROM levels WHERE level_name = 'client' LIMIT 1")->fetchColumn();

        // Admin: ALL permissions
        if ($adminLevel) {
            $permIds = $pdo->query('SELECT id FROM permissions')->fetchAll(PDO::FETCH_COLUMN);
            $stmt = $pdo->prepare('INSERT IGNORE INTO level_permissions (levelID, perm_id) VALUES (?, ?)');
            foreach ($permIds as $pid) {
                $stmt->execute([$adminLevel, $pid]);
            }
        }

        // Client: only profile.edit
        if ($clientLevel) {
            $clientPerm = $pdo->query("SELECT id FROM permissions WHERE perm_key = 'profile.edit' LIMIT 1")->fetchColumn();
            if ($clientPerm) {
                $pdo->prepare('INSERT IGNORE INTO level_permissions (levelID, perm_id) VALUES (?, ?)')
                    ->execute([$clientLevel, $clientPerm]);
            }
        }

        // ── User: admin@admin / Admin123 ───────────────────────────────
        $email = 'admin@admin';
        $pass  = password_hash('Admin123', PASSWORD_BCRYPT);

        $pdo->prepare("
            INSERT IGNORE INTO USERS (userID, email, name, pass, level)
            VALUES (UUID(), :email, 'Admin', :pass, :level)
        ")->execute([
            ':email' => $email,
            ':pass'  => $pass,
            ':level' => $adminLevel,
        ]);

        echo "Seed complete:\n";
        echo "  - Levels 'admin' and 'client' created\n";
        echo "  - 8 permissions seeded\n";
        echo "  - Admin has all permissions, client has 'profile.edit'\n";
        echo "  - User 'admin@admin' / 'Admin123' created\n";
    }
}
