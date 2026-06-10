-- ═══════════════════════════════════════════════════════════════════════
-- Dev Dashboard — Schema inicial (idempotente, seguro para producción)
-- ═══════════════════════════════════════════════════════════════════════
-- Este archivo se puede ejecutar sobre una base de datos existente sin
-- perder datos. Todas las sentencias usan IF NOT EXISTS o verifican
-- antes de modificar.
--
-- Uso:
--   mysql -u root -p < migrations/001-initial-schema.sql
--
-- NOTA: los datos iniciales (usuarios admin, niveles) se siembran con:
--   php seed.php
-- ═══════════════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `apache-dashboard`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `apache-dashboard`;

-- ── Niveles de usuario ────────────────────────────────────────────────
-- level_type: 0 = admin, 1 = client
CREATE TABLE IF NOT EXISTS `levels` (
    `levelsID`   CHAR(36)     NOT NULL,
    `level_name` VARCHAR(255) NOT NULL,
    `level_type` TINYINT      NOT NULL,
    PRIMARY KEY (`levelsID`),
    UNIQUE KEY `uk_levels_id` (`levelsID`),
    UNIQUE KEY `uk_level_name` (`level_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Usuarios ───────────────────────────────────────────────────────────
-- pass: bcrypt hash (generado con password_hash en PHP)
CREATE TABLE IF NOT EXISTS `USERS` (
    `userID`  CHAR(36)     NOT NULL,
    `email`   VARCHAR(255) NOT NULL,
    `name`    VARCHAR(255) DEFAULT NULL,
    `pass`    VARCHAR(255) NOT NULL,
    `level`   CHAR(36)     NOT NULL,
    PRIMARY KEY (`userID`),
    UNIQUE KEY `uk_user_id` (`userID`),
    UNIQUE KEY `uk_email` (`email`),
    CONSTRAINT `fk_users_level` FOREIGN KEY (`level`) REFERENCES `levels` (`levelsID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Proyectos ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Project` (
    `id`            CHAR(36)      NOT NULL,
    `project_name`  TEXT,
    `user_own`      CHAR(36)      DEFAULT NULL,
    `acept_login`   TINYINT(1)    NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_projectid` (`id`),
    KEY `fk_project_user` (`user_own`),
    CONSTRAINT `fk_project_user` FOREIGN KEY (`user_own`) REFERENCES `USERS` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Migración: agregar columnas que puedan faltar en instalaciones viejas
-- acept_login se agregó después del schema inicial
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'apache-dashboard' AND TABLE_NAME = 'Project'
    AND COLUMN_NAME = 'acept_login');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `Project` ADD COLUMN `acept_login` TINYINT(1) NOT NULL DEFAULT 0 AFTER `user_own`',
    'SELECT "acept_login ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Cambiar user_own a NULLABLE (era NOT NULL en versiones viejas)
SET @sql = NULL;
SELECT IF(IS_NULLABLE = 'NO', 'ALTER TABLE `Project` MODIFY `user_own` CHAR(36) DEFAULT NULL', 'SELECT "user_own ya es NULLABLE" AS info')
    INTO @sql FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'apache-dashboard' AND TABLE_NAME = 'Project' AND COLUMN_NAME = 'user_own';
SET @sql = IFNULL(@sql, 'SELECT "columna user_own no encontrada" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Permisos (RBAC) ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `permissions` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `perm_key`   VARCHAR(50)  NOT NULL UNIQUE,
    `perm_label` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `level_permissions` (
    `levelID` CHAR(36) NOT NULL,
    `perm_id` INT      NOT NULL,
    PRIMARY KEY (`levelID`, `perm_id`),
    CONSTRAINT `fk_lp_level` FOREIGN KEY (`levelID`) REFERENCES `levels` (`levelsID`) ON DELETE CASCADE,
    CONSTRAINT `fk_lp_perm`  FOREIGN KEY (`perm_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Limpiar niveles duplicados ANTES de agregar UNIQUE KEY ────────────
-- (INSERT IGNORE en versiones viejas del seed podía crear duplicados)
DELETE l1 FROM levels l1
INNER JOIN levels l2
    ON l1.level_name = l2.level_name AND l1.levelsID > l2.levelsID
WHERE l1.levelsID NOT IN (SELECT DISTINCT `level` FROM USERS WHERE `level` IS NOT NULL);

-- ── Migración: agregar UNIQUE KEY en level_name ───────────────────────
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = 'apache-dashboard' AND TABLE_NAME = 'levels'
    AND INDEX_NAME = 'uk_level_name');
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `levels` ADD UNIQUE KEY `uk_level_name` (`level_name`)',
    'SELECT "uk_level_name ya existe" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Seed: niveles por defecto ─────────────────────────────────────────
INSERT IGNORE INTO `levels` (`levelsID`, `level_name`, `level_type`)
VALUES (UUID(), 'admin', 0);

INSERT IGNORE INTO `levels` (`levelsID`, `level_name`, `level_type`)
VALUES (UUID(), 'operator', 0);

INSERT IGNORE INTO `levels` (`levelsID`, `level_name`, `level_type`)
VALUES (UUID(), 'client', 1);

INSERT IGNORE INTO `levels` (`levelsID`, `level_name`, `level_type`)
VALUES (UUID(), 'revisor', 1);

SELECT '✅ Migración completada' AS resultado;
