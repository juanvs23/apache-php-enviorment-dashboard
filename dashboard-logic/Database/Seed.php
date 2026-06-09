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
        // ── Level: admin ────────────────────────────────────────────────
        $pdo->exec("
            INSERT IGNORE INTO levels (levelsID, level_name, level_type)
            VALUES (UUID(), 'admin', 0)
        ");

        // ── Fetch the admin level ID just created ───────────────────────
        $stmt = $pdo->query("SELECT levelsID FROM levels WHERE level_name = 'admin' LIMIT 1");
        $levelId = $stmt->fetchColumn();

        if (!$levelId) {
            echo "ERROR: Could not find or create 'admin' level.\n";
            return;
        }

        // ── User: admin@admin / Admin123 ─────────────────────────────────
        $email = 'admin@admin';
        $pass  = password_hash('Admin123', PASSWORD_BCRYPT);

        $pdo->prepare("
            INSERT IGNORE INTO USERS (userID, email, name, pass, level)
            VALUES (UUID(), :email, 'Admin', :pass, :level)
        ")->execute([
            ':email' => $email,
            ':pass'  => $pass,
            ':level' => $levelId,
        ]);

        echo "Seed complete:\n";
        echo "  - Level 'admin' (type 0) created\n";
        echo "  - User 'admin@admin' / 'Admin123' created\n";
    }
}
