-- ═══════════════════════════════════════════════════════════════════════
-- Dev Dashboard — Schema inicial (compatible con phpMyAdmin)
-- ═══════════════════════════════════════════════════════════════════════
-- Ejecutar completo en phpMyAdmin: copiar y pegar en la pestaña SQL.
-- Es idempotente: se puede correr sobre una BD existente sin perder datos.
--
-- NOTA: los datos iniciales se siembran con:
--   php seed.php
-- ═══════════════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `apache-dashboard`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `apache-dashboard`;

-- ── Niveles de usuario ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `levels` (
    `levelsID`   CHAR(36)     NOT NULL,
    `level_name` VARCHAR(255) NOT NULL,
    `level_type` TINYINT      NOT NULL COMMENT '0=admin, 1=client',
    PRIMARY KEY (`levelsID`),
    UNIQUE KEY `uk_levels_id` (`levelsID`),
    UNIQUE KEY `uk_level_name` (`level_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Usuarios ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `USERS` (
    `userID`  CHAR(36)     NOT NULL,
    `email`   VARCHAR(255) NOT NULL,
    `name`    VARCHAR(255) DEFAULT NULL,
    `pass`    VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
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

-- ── Migraciones manuales (ejecutar SOLO si la columna/clave no existe) ─

-- 1. Agregar acept_login (si no existe)
-- ALTER TABLE `Project` ADD COLUMN IF NOT EXISTS `acept_login` TINYINT(1) NOT NULL DEFAULT 0 AFTER `user_own`;

-- 2. Cambiar user_own a NULLABLE (si es NOT NULL)
-- ALTER TABLE `Project` MODIFY `user_own` CHAR(36) DEFAULT NULL;

-- 3. Agregar UNIQUE KEY en level_name (si no existe)
-- ALTER TABLE `levels` ADD UNIQUE KEY `uk_level_name` (`level_name`);

-- ── Seed: niveles por defecto ─────────────────────────────────────────
-- Limpiar duplicados antes (por si el UNIQUE KEY no existe aún)
DELETE l1 FROM `levels` l1
INNER JOIN `levels` l2
    ON l1.level_name = l2.level_name AND l1.levelsID > l2.levelsID
WHERE l1.levelsID NOT IN (SELECT DISTINCT `level` FROM `USERS` WHERE `level` IS NOT NULL);

INSERT IGNORE INTO `levels` (`levelsID`, `level_name`, `level_type`)
VALUES (UUID(), 'admin', 0);

INSERT IGNORE INTO `levels` (`levelsID`, `level_name`, `level_type`)
VALUES (UUID(), 'operator', 0);

INSERT IGNORE INTO `levels` (`levelsID`, `level_name`, `level_type`)
VALUES (UUID(), 'client', 1);

INSERT IGNORE INTO `levels` (`levelsID`, `level_name`, `level_type`)
VALUES (UUID(), 'revisor', 1);

-- ── Seed: permisos ────────────────────────────────────────────────────
INSERT IGNORE INTO `permissions` (`perm_key`, `perm_label`) VALUES
('users.manage',          'Gestionar usuarios (CRUD)'),
('users.edit_same_level', 'Editar usuarios de su mismo nivel'),
('projects.manage',       'Gestionar proyectos (CRUD)'),
('projects.view_all',     'Ver todos los proyectos'),
('projects.acept_login',  'Ver botones Acceder y WP Admin siempre'),
('server.view',           'Ver información del servidor'),
('badge.admin',           'Mostrar badge de admin'),
('profile.edit',          'Editar perfil propio');

-- ── Seed: asignar permisos a niveles ──────────────────────────────────
-- admin: TODOS los permisos
INSERT IGNORE INTO `level_permissions` (`levelID`, `perm_id`)
SELECT l.levelsID, p.id
FROM `levels` l, `permissions` p
WHERE l.level_name = 'admin';

-- operator: gestión + proyectos (sin badge ni edit_same_level)
INSERT IGNORE INTO `level_permissions` (`levelID`, `perm_id`)
SELECT l.levelsID, p.id
FROM `levels` l, `permissions` p
WHERE l.level_name = 'operator'
  AND p.perm_key IN ('users.manage', 'projects.manage', 'projects.view_all',
                     'projects.acept_login', 'server.view', 'profile.edit');

-- client: solo perfil
INSERT IGNORE INTO `level_permissions` (`levelID`, `perm_id`)
SELECT l.levelsID, p.id
FROM `levels` l, `permissions` p
WHERE l.level_name = 'client'
  AND p.perm_key = 'profile.edit';

-- revisor: view-only
INSERT IGNORE INTO `level_permissions` (`levelID`, `perm_id`)
SELECT l.levelsID, p.id
FROM `levels` l, `permissions` p
WHERE l.level_name = 'revisor'
  AND p.perm_key IN ('projects.view_all', 'projects.acept_login', 'profile.edit');

SELECT '✅ Migración completada' AS resultado;
