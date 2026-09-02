-- ============================================================================
-- Fix v1.5.0 — limpieza de tabla Project y niveles duplicados
-- ============================================================================
-- 1. Elimina columna user_own_json que sobró de migración parcial
-- 2. Fusiona niveles duplicados (operator y revisor)
-- 3. Agrega UNIQUE KEY en level_name para evitar que vuelva a pasar
--
-- Los usuarios NO se ven afectados.
-- ============================================================================

-- 1. Eliminar columna fantasma que sobró de la migración parcial
ALTER TABLE `Project` DROP COLUMN `user_own_json`;

-- 2. Reasignar Samuel al operador principal (Federico usa el mismo)
--    El duplicado 'c16a42c3...' solo lo usa Samuel
UPDATE `USERS`
SET `level` = '6ce191be-64e4-11f1-9ea7-32e8d4b52ab7'
WHERE `level` = 'c16a42c3-64e4-11f1-9ea7-32e8d4b52ab7';

-- 3. Eliminar operador duplicado
--    ON DELETE CASCADE borra level_permissions automáticamente
DELETE FROM `levels`
WHERE `levelsID` = 'c16a42c3-64e4-11f1-9ea7-32e8d4b52ab7';

-- 4. Eliminar revisor duplicado (ningún usuario lo referencia)
DELETE FROM `levels`
WHERE `levelsID` = 'c16c7cf5-64e4-11f1-9ea7-32e8d4b52ab7';

-- 5. Prevenir futuros duplicados
ALTER TABLE `levels` ADD UNIQUE KEY `uk_level_name` (`level_name`);
