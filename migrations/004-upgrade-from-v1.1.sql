-- ============================================================================
-- Migración 004: Actualización desde v1.1.0 → v1.5.0
-- ============================================================================
-- Aplica todos los cambios necesarios para instalaciones existentes.
-- Compatible con MySQL 8.0 (usa INFORMATION_SCHEMA para chequeos).
-- ============================================================================

-- 1. Convertir user_own de CHAR(36) a JSON ──────────────────────────────────
--    Migrar datos existentes: cada user_own → [{"userID": uuid, "user_name": "", "is_logeable": acept_login}]

-- Agregar columna temporal JSON (solo si no existe)
SET @has_user_own_json = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'user_own_json');

SET @sql_add_col = IF(@has_user_own_json = 0,
    'ALTER TABLE `Project` ADD COLUMN `user_own_json` JSON DEFAULT NULL AFTER `user_own`',
    'SELECT 1');

PREPARE stmt FROM @sql_add_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Migrar datos existentes
UPDATE `Project`
SET `user_own_json` = JSON_ARRAY(
    JSON_OBJECT(
        'userID', `user_own`,
        'user_name', COALESCE((SELECT `name` FROM `USERS` WHERE `userID` = `Project`.`user_own`), (SELECT `email` FROM `USERS` WHERE `userID` = `Project`.`user_own`), `user_own`),
        'is_logeable', IF(`acept_login` = 1, true, false)
    )
)
WHERE `user_own` IS NOT NULL AND `user_own_json` IS NULL;

-- Eliminar FK (solo si existe)
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Project' AND CONSTRAINT_NAME = 'fk_project_user' AND CONSTRAINT_TYPE = 'FOREIGN KEY');

SET @sql_drop_fk = IF(@fk_exists > 0,
    'ALTER TABLE `Project` DROP FOREIGN KEY `fk_project_user`',
    'SELECT 1');

PREPARE stmt FROM @sql_drop_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar índice (solo si existe)
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Project' AND INDEX_NAME = 'fk_project_user');

SET @sql_drop_idx = IF(@idx_exists > 0,
    'ALTER TABLE `Project` DROP INDEX `fk_project_user`',
    'SELECT 1');

PREPARE stmt FROM @sql_drop_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar columna user_own (solo si existe)
SET @has_user_own = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'user_own' AND DATA_TYPE != 'json');

SET @sql_drop_own = IF(@has_user_own > 0,
    'ALTER TABLE `Project` DROP COLUMN `user_own`',
    'SELECT 1');

PREPARE stmt FROM @sql_drop_own;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Eliminar columna acept_login (solo si existe)
SET @has_acept = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'acept_login');

SET @sql_drop_acept = IF(@has_acept > 0,
    'ALTER TABLE `Project` DROP COLUMN `acept_login`',
    'SELECT 1');

PREPARE stmt FROM @sql_drop_acept;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Renombrar nueva columna (solo si user_own_json existe y user_own no es JSON todavía)
SET @has_json_col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'user_own_json');

SET @user_own_is_json = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'user_own' AND DATA_TYPE = 'json');

SET @sql_rename = IF(@has_json_col > 0 AND @user_own_is_json = 0,
    'ALTER TABLE `Project` CHANGE COLUMN `user_own_json` `user_own` JSON DEFAULT NULL',
    'SELECT 1');

PREPARE stmt FROM @sql_rename;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Tablas nuevas ──────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `auth_logs` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `email`      VARCHAR(255) NOT NULL,
    `action`     VARCHAR(20)  NOT NULL,
    `ip_address` VARCHAR(45)  NOT NULL,
    `user_agent` VARCHAR(512) DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_email` (`email`),
    KEY `idx_action` (`action`),
    KEY `idx_created` (`created_at`),
    KEY `idx_email_created` (`email`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `migrations` (
    `version`    VARCHAR(10)  NOT NULL,
    `applied_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Actualizar permisos: cliente ya no tiene projects.acept_login ─────────

SET @client_level = (SELECT `levelsID` FROM `levels` WHERE `level_name` = 'client' LIMIT 1);
SET @acept_perm  = (SELECT `id` FROM `permissions` WHERE `perm_key` = 'projects.acept_login' LIMIT 1);

DELETE FROM `level_permissions`
WHERE `levelID` = @client_level AND `perm_id` = @acept_perm;

-- 4. Actualizar nombres de usuarios en user_own (si no se migraron) ─────────

UPDATE `Project` p
SET `user_own` = (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'userID', u2.`userID`,
                'user_name', COALESCE(u2real.`name`, u2real.`email`),
                'is_logeable', CAST(JSON_EXTRACT(u2.entry, '$.is_logeable') AS UNSIGNED)
            )
        )
        FROM JSON_TABLE(p.`user_own`, '$[*]' COLUMNS(
            `userID` CHAR(36) PATH '$.userID',
            entry JSON PATH '$'
        )) AS u2
        JOIN `USERS` u2real ON u2real.`userID` = u2.`userID`
)
WHERE p.`user_own` IS NOT NULL;
