-- ============================================================================
-- Migración v1.1.0 → v1.5.0 — compatible MySQL 8.0 desde phpMyAdmin
-- Pegar completo en la pestaña SQL y ejecutar.
-- ============================================================================

DROP PROCEDURE IF EXISTS `migrate_v1_1`;
DELIMITER //
CREATE PROCEDURE `migrate_v1_1`()
BEGIN
    DECLARE _db VARCHAR(64);
    SET _db = DATABASE();

    -- 1. Agregar columna user_own_json si no existe
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = _db AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'user_own_json') THEN
        ALTER TABLE `Project` ADD COLUMN `user_own_json` JSON DEFAULT NULL AFTER `user_own`;
    END IF;

    -- 2. Migrar datos existentes (user_own CHAR(36) → JSON array)
    UPDATE `Project`
    SET `user_own_json` = JSON_ARRAY(
        JSON_OBJECT(
            'userID', `user_own`,
            'user_name', COALESCE(
                (SELECT `name` FROM `USERS` WHERE `userID` = `Project`.`user_own`),
                (SELECT `email` FROM `USERS` WHERE `userID` = `Project`.`user_own`),
                `user_own`
            ),
            'is_logeable', IF(`acept_login` = 1, TRUE, FALSE)
        )
    )
    WHERE `user_own` IS NOT NULL AND `user_own_json` IS NULL;

    -- 3. Eliminar FK si existe
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = _db AND TABLE_NAME = 'Project' AND CONSTRAINT_NAME = 'fk_project_user') THEN
        ALTER TABLE `Project` DROP FOREIGN KEY `fk_project_user`;
    END IF;

    -- 4. Eliminar índice si existe
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = _db AND TABLE_NAME = 'Project' AND INDEX_NAME = 'fk_project_user') THEN
        ALTER TABLE `Project` DROP INDEX `fk_project_user`;
    END IF;

    -- 5. Eliminar columna user_own (solo si es CHAR, no JSON)
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = _db AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'user_own' AND DATA_TYPE != 'json') THEN
        ALTER TABLE `Project` DROP COLUMN `user_own`;
    END IF;

    -- 6. Eliminar columna acept_login si existe
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = _db AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'acept_login') THEN
        ALTER TABLE `Project` DROP COLUMN `acept_login`;
    END IF;

    -- 7. Renombrar user_own_json → user_own (solo si user_own_json existe y user_own aún no es JSON)
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = _db AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'user_own_json')
       AND NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = _db AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'user_own' AND DATA_TYPE = 'json') THEN
        ALTER TABLE `Project` CHANGE COLUMN `user_own_json` `user_own` JSON DEFAULT NULL;
    END IF;

    -- 8. Crear tablas nuevas
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

    -- 9. Quitar permiso acept_login al level client
    DELETE FROM `level_permissions`
    WHERE `levelID` = (SELECT `levelsID` FROM `levels` WHERE `level_name` = 'client' LIMIT 1)
      AND `perm_id` = (SELECT `id` FROM `permissions` WHERE `perm_key` = 'projects.acept_login' LIMIT 1);

    -- 10. Actualizar nombres de usuarios en user_own
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
END //
DELIMITER ;

CALL `migrate_v1_1`();

DROP PROCEDURE IF EXISTS `migrate_v1_1`;
