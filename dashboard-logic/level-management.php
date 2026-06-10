<?php
/**
 * Gestión de niveles de usuario y permisos (solo admin).
 *
 * Responsabilidad única: CRUD de levels + asignación de permisos.
 */

use Dashboard\Database\Connection;

function get_all_levels_with_perms(): array
{
    try {
        $pdo = Connection::get();
        $levels = $pdo->query('
            SELECT l.levelsID, l.level_name, l.level_type
            FROM levels l
            ORDER BY l.level_type, l.level_name
        ')->fetchAll();

        // Cargar permisos de cada nivel
        $permStmt = $pdo->prepare('
            SELECT p.perm_key
            FROM permissions p
            JOIN level_permissions lp ON lp.perm_id = p.id
            WHERE lp.levelID = :levelID
        ');
        foreach ($levels as &$lvl) {
            $permStmt->execute([':levelID' => $lvl['levelsID']]);
            $lvl['perms'] = $permStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return $levels;
    } catch (Throwable) {
        return [];
    }
}

function get_all_permissions(): array
{
    try {
        return Connection::get()->query('SELECT id, perm_key, perm_label FROM permissions ORDER BY id')->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function handle_level_create(): array
{
    $name   = trim($_POST['level_name'] ?? '');
    $type   = (int) ($_POST['level_type'] ?? 1);
    $perms  = $_POST['perms'] ?? [];

    if ($name === '') {
        return ['success' => false, 'error' => 'El nombre del nivel es obligatorio'];
    }

    try {
        $pdo  = Connection::get();
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

        $pdo->prepare('INSERT INTO levels (levelsID, level_name, level_type) VALUES (:id, :name, :type)')
            ->execute([':id' => $uuid, ':name' => $name, ':type' => $type]);

        if (!empty($perms)) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO level_permissions (levelID, perm_id) VALUES (:levelID, :perm_id)');
            foreach ($perms as $pid) {
                $stmt->execute([':levelID' => $uuid, ':perm_id' => (int) $pid]);
            }
        }

        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al crear: ' . $e->getMessage()];
    }
}

function handle_level_update(): array
{
    $levelID = trim($_POST['levelID'] ?? '');
    $name    = trim($_POST['level_name'] ?? '');
    $perms   = $_POST['perms'] ?? [];

    if ($levelID === '' || $name === '') {
        return ['success' => false, 'error' => 'ID y nombre requeridos'];
    }

    try {
        $pdo = Connection::get();

        // Proteger el nivel admin: no se puede renombrar
        $stmt = $pdo->prepare('SELECT level_name FROM levels WHERE levelsID = :id');
        $stmt->execute([':id' => $levelID]);
        $current = $stmt->fetch();
        if ($current && $current['level_name'] === 'admin') {
            return ['success' => false, 'error' => 'El nivel admin no se puede modificar'];
        }

        $pdo->prepare('UPDATE levels SET level_name = :name WHERE levelsID = :id')
            ->execute([':name' => $name, ':id' => $levelID]);

        // Reemplazar permisos
        $pdo->prepare('DELETE FROM level_permissions WHERE levelID = :id')
            ->execute([':id' => $levelID]);

        if (!empty($perms)) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO level_permissions (levelID, perm_id) VALUES (:levelID, :perm_id)');
            foreach ($perms as $pid) {
                $stmt->execute([':levelID' => $levelID, ':perm_id' => (int) $pid]);
            }
        }

        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al actualizar: ' . $e->getMessage()];
    }
}

function handle_level_delete(): array
{
    $levelID = trim($_POST['levelID'] ?? '');
    if ($levelID === '') {
        return ['success' => false, 'error' => 'ID de nivel requerido'];
    }

    try {
        $pdo = Connection::get();

        // Proteger niveles con usuarios asignados
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM USERS WHERE level = :id');
        $stmt->execute([':id' => $levelID]);
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            return ['success' => false, 'error' => "No se puede eliminar: tiene $count usuario(s) asignados"];
        }

        // Proteger admin
        $stmt = $pdo->prepare('SELECT level_name FROM levels WHERE levelsID = :id');
        $stmt->execute([':id' => $levelID]);
        $current = $stmt->fetch();
        if ($current && $current['level_name'] === 'admin') {
            return ['success' => false, 'error' => 'El nivel admin no se puede eliminar'];
        }

        $pdo->prepare('DELETE FROM levels WHERE levelsID = :id')
            ->execute([':id' => $levelID]);

        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()];
    }
}

function process_level_action(): ?array
{
    $action = $_POST['action'] ?? '';
    return match ($action) {
        'create_level' => handle_level_create(),
        'update_level' => handle_level_update(),
        'delete_level' => handle_level_delete(),
        default => null,
    };
}
