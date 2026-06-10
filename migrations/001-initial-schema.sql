-- ═══════════════════════════════════════════════════════════════════════
-- Dev Dashboard — Schema inicial
-- ═══════════════════════════════════════════════════════════════════════
-- Uso:
--   mysql -u root -p < migrations/001-initial-schema.sql
-- O desde MySQL:
--   source migrations/001-initial-schema.sql;
--
-- NOTA: este archivo solo crea las tablas. Los datos iniciales
-- (usuarios admin, niveles) se siembran con:
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
    UNIQUE KEY `uk_levels_id` (`levelsID`)
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
-- user_own: NULL = sin asignar
-- acept_login: 1 = muestra botones Acceder y WP Admin, 0 = ocultos
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
