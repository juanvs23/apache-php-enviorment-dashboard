<?php

declare(strict_types=1);

namespace Dashboard\Database;

use PDO;

/**
 * Maneja el esquema inicial de la base de datos.
 *
 * Responsabilidad única: aplicar el schema DDL si las tablas no existen.
 * Se ejecuta automáticamente al conectar por primera vez.
 */
final class Migration
{
    /**
     * Aplica el schema inicial sobre la conexión recibida.
     * Es seguro ejecutarlo múltiples veces (usa IF NOT EXISTS).
     */
    public static function apply(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS levels (
                levelsID   CHAR(36)     NOT NULL,
                level_name VARCHAR(255) NOT NULL,
                level_type TINYINT      NOT NULL,
                PRIMARY KEY (levelsID),
                UNIQUE KEY uk_levels_id (levelsID)
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
    }
}
