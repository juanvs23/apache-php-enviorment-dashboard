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
        $pdo->exec("
            INSERT IGNORE INTO levels (levelsID, level_name, level_type)
            VALUES (UUID(), 'operator', 0)
        ");
        $pdo->exec("
            INSERT IGNORE INTO levels (levelsID, level_name, level_type)
            VALUES (UUID(), 'revisor', 1)
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
        $adminLevel    = $pdo->query("SELECT levelsID FROM levels WHERE level_name = 'admin' LIMIT 1")->fetchColumn();
        $clientLevel   = $pdo->query("SELECT levelsID FROM levels WHERE level_name = 'client' LIMIT 1")->fetchColumn();
        $operatorLevel = $pdo->query("SELECT levelsID FROM levels WHERE level_name = 'operator' LIMIT 1")->fetchColumn();
        $revisorLevel  = $pdo->query("SELECT levelsID FROM levels WHERE level_name = 'revisor' LIMIT 1")->fetchColumn();

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

        // Operator: management + projects (sin badge.admin ni users.edit_same_level)
        if ($operatorLevel) {
            $operatorPerms = $pdo->query("SELECT id FROM permissions WHERE perm_key IN (
                'users.manage', 'projects.manage', 'projects.view_all',
                'projects.acept_login', 'server.view', 'profile.edit'
            )")->fetchAll(PDO::FETCH_COLUMN);
            $stmt = $pdo->prepare('INSERT IGNORE INTO level_permissions (levelID, perm_id) VALUES (?, ?)');
            foreach ($operatorPerms as $pid) {
                $stmt->execute([$operatorLevel, $pid]);
            }
        }

        // Revisor: view-only (sin users.manage, sin projects.manage)
        if ($revisorLevel) {
            $revisorPerms = $pdo->query("SELECT id FROM permissions WHERE perm_key IN (
                'projects.view_all', 'projects.acept_login', 'profile.edit'
            )")->fetchAll(PDO::FETCH_COLUMN);
            $stmt = $pdo->prepare('INSERT IGNORE INTO level_permissions (levelID, perm_id) VALUES (?, ?)');
            foreach ($revisorPerms as $pid) {
                $stmt->execute([$revisorLevel, $pid]);
            }
        }

        // ── User: admin@admin / Admin123 ───────────────────────────────
        $email = 'admin@admin.com';
        $pass  = password_hash('Admin123', PASSWORD_BCRYPT);

        $pdo->prepare("
            INSERT IGNORE INTO USERS (userID, email, name, pass, level)
            VALUES (UUID(), :email, 'Admin', :pass, :level)
        ")->execute([
            ':email' => $email,
            ':pass'  => $pass,
            ':level' => $adminLevel,
        ]);

        // ── User: operator@test.com / Operator123 ──────────────────────
        if ($operatorLevel) {
            $pdo->prepare("
                INSERT IGNORE INTO USERS (userID, email, name, pass, level)
                VALUES (UUID(), :email, 'Operator', :pass, :level)
            ")->execute([
                ':email' => 'operator@test.com',
                ':pass'  => password_hash('Operator123', PASSWORD_BCRYPT),
                ':level' => $operatorLevel,
            ]);
        }

        // ── User: revisor@test.com / Revisor123 ────────────────────────
        if ($revisorLevel) {
            $pdo->prepare("
                INSERT IGNORE INTO USERS (userID, email, name, pass, level)
                VALUES (UUID(), :email, 'Revisor', :pass, :level)
            ")->execute([
                ':email' => 'revisor@test.com',
                ':pass'  => password_hash('Revisor123', PASSWORD_BCRYPT),
                ':level' => $revisorLevel,
            ]);
        }

        echo "Seed complete:\n";
        echo "  - Levels: admin, client, operator, revisor\n";
        echo "  - 8 permissions seeded\n";
        echo "  - Users: admin@admin.com, operator@test.com, revisor@test.com\n";
    }
}
