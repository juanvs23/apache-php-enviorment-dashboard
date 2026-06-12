-- ============================================================================
-- Migración 002: auth_logs — registro de accesos al dashboard
-- ============================================================================
-- Segura para ejecutar múltiples veces (usa IF NOT EXISTS).
-- Aplica a instalaciones existentes que ya pasaron la migración inicial.
--
-- La tabla se crea automáticamente en nuevas instalaciones vía Migration.php.
-- Este archivo es para instalaciones existentes que necesitan crearla manualmente.
-- ============================================================================

CREATE TABLE IF NOT EXISTS auth_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    action     VARCHAR(20)  NOT NULL COMMENT 'login_success | login_failed | logout',
    ip_address VARCHAR(45)  NOT NULL,
    user_agent VARCHAR(512) DEFAULT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_email (email),
    KEY idx_action (action),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
