<?php

declare(strict_types=1);

namespace Dashboard\Database;

use PDO;

/**
 * Maneja el esquema de la base de datos con migraciones versionadas.
 *
 * Cada migración se registra en la tabla `migrations`. Si una migración
 * ya fue aplicada, se saltea. Esto permite agregar nuevas tablas y columnas
 * de forma incremental sin depender solo de IF NOT EXISTS.
 *
 * Las migraciones se numeran secuencialmente (001, 002, ...) y corresponden
 * a los archivos SQL en el directorio `migrations/`.
 */
final class Migration
{
    /**
     * Aplica el schema inicial sobre la conexión recibida.
     * Es seguro ejecutarlo múltiples veces (usa IF NOT EXISTS).
     */
    public static function apply(PDO $pdo): void
    {
        // ── Tabla de control de migraciones ──────────────────────────
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS migrations (
                version     VARCHAR(10)  NOT NULL,
                applied_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (version)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        // ── Aplicar migraciones secuencialmente ─────────────────────
        $migrations = [
            '001' => 'applyV001',
            '002' => 'applyV002',
        ];

        $applied = $pdo->query("SELECT version FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($migrations as $version => $method) {
            if (in_array($version, $applied, true)) {
                continue; // Ya aplicada
            }
            self::$method($pdo);
            $pdo->prepare('INSERT INTO migrations (version) VALUES (?)')->execute([$version]);
        }
    }

    /**
     * Migración 001 — Schema inicial.
     */
    private static function applyV001(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS levels (
                levelsID   CHAR(36)     NOT NULL,
                level_name VARCHAR(255) NOT NULL,
                level_type TINYINT      NOT NULL,
                PRIMARY KEY (levelsID),
                UNIQUE KEY uk_levels_id (levelsID),
                UNIQUE KEY uk_level_name (level_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS USERS (
                userID  CHAR(36)     NOT NULL,
                email   VARCHAR(255) NOT NULL,
                name    VARCHAR(255) DEFAULT NULL,
                pass    VARCHAR(255) NOT NULL,
                level   CHAR(36)     NOT NULL,
                PRIMARY KEY (userID),
                UNIQUE KEY uk_user_id (userID),
                UNIQUE KEY uk_email (email),
                CONSTRAINT fk_users_level FOREIGN KEY (level) REFERENCES levels (levelsID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS Project (
                id           CHAR(36)      NOT NULL,
                project_name TEXT,
                user_own     CHAR(36)      DEFAULT NULL,
                acept_login  TINYINT(1)    NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY uk_projectid (id),
                KEY fk_project_user (user_own),
                CONSTRAINT fk_project_user FOREIGN KEY (user_own) REFERENCES USERS (userID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS permissions (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                perm_key   VARCHAR(50)  NOT NULL UNIQUE,
                perm_label VARCHAR(100) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS level_permissions (
                levelID CHAR(36) NOT NULL,
                perm_id INT      NOT NULL,
                PRIMARY KEY (levelID, perm_id),
                CONSTRAINT fk_lp_level FOREIGN KEY (levelID) REFERENCES levels (levelsID) ON DELETE CASCADE,
                CONSTRAINT fk_lp_perm  FOREIGN KEY (perm_id) REFERENCES permissions (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    /**
     * Migración 002 — Logs de autenticación.
     */
    private static function applyV002(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS auth_logs (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                email      VARCHAR(255) NOT NULL,
                action     VARCHAR(20)  NOT NULL,
                ip_address VARCHAR(45)  NOT NULL,
                user_agent VARCHAR(512) DEFAULT NULL,
                created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_email (email),
                KEY idx_action (action),
                KEY idx_created (created_at),
                KEY idx_email_created (email, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }
}
