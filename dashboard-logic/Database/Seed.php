<?php

declare(strict_types=1);

namespace Dashboard\Database;

use PDO;

/**
 * Seed initial data for the dashboard database.
 *
 * Creates default levels, permissions, and users.
 * Safe to run multiple times (uses INSERT IGNORE).
 *
 * Entornos soportados vía SEED_ENV en .env:
 *   - dev (default):   contraseñas conocidas para desarrollo local
 *   - staging:         contraseñas aleatorias fuertes para VPS compartido
 *   - prod:            solo admin, sin usuarios de prueba
 *
 * ⚠️ ARCHITECTURE NOTE: This class uses PDO directly instead of Use Cases.
 * This is INTENTIONAL — Seed runs during Connection::get() auto-migration,
 * BEFORE the ServiceContainer and Use Cases are wired up. At this point
 * only PDO is guaranteed to be available. Using Use Cases here would
 * create a circular dependency (Use Cases → ServiceContainer → Connection → Seed).
 */
final class Seed
{
    /**
     * @param PDO    $pdo  Conexión a la base de datos
     * @param string $env  Entorno: 'dev' | 'staging' | 'prod'
     */
    public static function run(PDO $pdo, string $env = ''): void
    {
        $env = $env ?: ($_ENV['SEED_ENV'] ?? 'dev');

        // ── Levels (común a todos los entornos) ──────────────────────
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

        // Client: only profile.edit (no projects.acept_login — login is per-user via JSON)
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

        // ── Users — según entorno ────────────────────────────────────
        $adminPass = ($env === 'staging')
            ? bin2hex(random_bytes(8))   // Aleatorio fuerte
            : 'Admin123';                 // Desarrollo local

        $adminHash = password_hash($adminPass, PASSWORD_BCRYPT);
        $pdo->prepare("
            INSERT IGNORE INTO USERS (userID, email, name, pass, level)
            VALUES (UUID(), :email, 'Admin', :pass, :level)
        ")->execute([
            ':email' => 'admin@admin.com',
            ':pass'  => $adminHash,
            ':level' => $adminLevel,
        ]);

        if ($env === 'prod') {
            echo "Seed complete (prod):\n";
            echo "  - Levels: admin, client\n";
            echo "  - 8 permissions seeded\n";
            echo "  - Users: admin@admin.com (no test users)\n";
            return;
        }

        // ── Test users (solo dev y staging) ──────────────────────────
        $opPass  = ($env === 'staging') ? bin2hex(random_bytes(8)) : 'Operator123';
        $revPass = ($env === 'staging') ? bin2hex(random_bytes(8)) : 'Revisor123';

        if ($operatorLevel) {
            $pdo->prepare("
                INSERT IGNORE INTO USERS (userID, email, name, pass, level)
                VALUES (UUID(), :email, 'Operator', :pass, :level)
            ")->execute([
                ':email' => 'operator@test.com',
                ':pass'  => password_hash($opPass, PASSWORD_BCRYPT),
                ':level' => $operatorLevel,
            ]);
        }

        if ($revisorLevel) {
            $pdo->prepare("
                INSERT IGNORE INTO USERS (userID, email, name, pass, level)
                VALUES (UUID(), :email, 'Revisor', :pass, :level)
            ")->execute([
                ':email' => 'revisor@test.com',
                ':pass'  => password_hash($revPass, PASSWORD_BCRYPT),
                ':level' => $revisorLevel,
            ]);
        }

        echo "Seed complete ({$env}):\n";
        echo "  - Levels: admin, client, operator, revisor\n";
        echo "  - 8 permissions seeded\n";
        if ($env === 'staging') {
            echo "  - admin@admin.com / {$adminPass}\n";
            echo "  - operator@test.com / {$opPass}\n";
            echo "  - revisor@test.com / {$revPass}\n";
            echo "  ⚠️  Guardá estas contraseñas. No se volverán a mostrar.\n";
        } else {
            echo "  - Users: admin@admin.com, operator@test.com, revisor@test.com\n";
        }
    }
}
